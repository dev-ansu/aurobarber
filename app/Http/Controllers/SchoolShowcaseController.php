<?php

namespace App\Http\Controllers;

use App\Models\CommercialLead;
use App\Models\Formation;
use App\Models\School;
use App\Rules\ValidBrazilianPhone;
use App\Rules\ValidEmailDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SchoolShowcaseController extends Controller
{
    /**
     * Exibe o Portal Vitrine / Mini-site da Escola.
     */
    public function show(Request $request, ?int $schoolId = null)
    {
        $school = null;

        if ($schoolId) {
            $school = School::find($schoolId);
        }

        if (! $school && session()->has('current_school_id')) {
            $school = School::find(session('current_school_id'));
        }

        if (! $school) {
            $school = School::getHeadquarters() ?? School::first();
        }

        if (! $school) {
            return view('welcome');
        }

        $formations = Formation::query()
            ->where('active', true)
            ->where('show_in_showcase', true)
            ->with(['courses'])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        $networkUnits = $school->show_network_units
            ? School::query()->orderByRaw('is_headquarters DESC, trade_name ASC')->get()
            : collect([$school]);

        $activeCount = $school->active_students_count;
        $socialProofStudents = $school->custom_students_count_text
            ?: ($activeCount > 0 ? '+' . number_format($activeCount, 0, ',', '.') : '+1.000');

        // Registro leve de métricas de visitação da vitrine (100% LGPD)
        \App\Services\PortalTrafficTracker::log(
            $request,
            'school_showcase',
            'page_view',
            $school->id,
            null,
            'Vitrine Principal'
        );

        return view('school-showcase', [
            'school' => $school,
            'formations' => $formations,
            'networkUnits' => $networkUnits,
            'socialProofStudents' => $socialProofStudents,
        ]);
    }

    /**
     * Exibe a Página Dedicada / Landing Page de uma Formação Específica.
     */
    public function showFormation(Request $request, Formation $formation)
    {
        $school = null;

        if (session()->has('current_school_id')) {
            $school = School::find(session('current_school_id'));
        }

        if (! $school) {
            $school = School::getHeadquarters() ?? School::first();
        }

        if (! $school) {
            return redirect()->route('home');
        }

        if (! $formation->active || ! $formation->show_in_showcase) {
            abort(404, 'Formação indisponível no momento.');
        }

        $formation->load(['courses']);

        $relatedFormations = Formation::query()
            ->where('active', true)
            ->where('show_in_showcase', true)
            ->where('id', '!=', $formation->id)
            ->with(['courses'])
            ->orderBy('order')
            ->take(3)
            ->get();

        $networkUnits = $school->show_network_units
            ? School::query()->orderByRaw('is_headquarters DESC, trade_name ASC')->get()
            : collect([$school]);

        // Registro leve de visualização da formação (100% LGPD)
        \App\Services\PortalTrafficTracker::log(
            $request,
            'school_showcase',
            'formation_view',
            $school->id,
            $formation->id,
            $formation->name
        );

        return view('school-formation-detail', [
            'school' => $school,
            'formation' => $formation,
            'relatedFormations' => $relatedFormations,
            'networkUnits' => $networkUnits,
        ]);
    }

    /**
     * Recebe um Lead de pré-matrícula / interesse gerado na vitrine da escola.
     */
    public function storeLead(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
                function ($attribute, $value, $fail) {
                    $cleaned = trim((string) $value);
                    $lower = mb_strtolower($cleaned);
                    if (in_array($lower, ['teste', 'test', 'asdf', 'xxx', 'fake', 'admin', 'usuario', 'nenhum'], true)) {
                        $fail('Por favor, informe seu nome real para contato.');
                    }
                    if (str_word_count($cleaned) < 2) {
                        $fail('Por favor, informe seu nome e sobrenome completos.');
                    }
                },
            ],
            'phone' => ['required', 'string', 'max:50', new ValidBrazilianPhone()],
            'email' => ['nullable', 'email:rfc', 'max:255', new ValidEmailDomain()],
            'school_id' => ['required'],
            'formation_id' => ['nullable'],
        ], [
            'name.required' => 'Informe o seu nome completo.',
            'phone.required' => 'Informe o seu WhatsApp ou telefone com DDD.',
        ]);

        $school = School::find($validated['school_id']) ?? School::getHeadquarters() ?? School::first();
        if (! $school) {
            return redirect()->back()->with('error', 'Unidade escolar não localizada.');
        }

        $formation = null;
        if (! empty($validated['formation_id'])) {
            $formation = Formation::find($validated['formation_id']);
        }

        try {
            $formationName = $formation?->name ?? 'Geral';
            $utmSource = strtolower((string) ($request->input('utm_source') ?? $request->query('utm_source') ?? ''));
            $source = 'website';
            if (str_contains($utmSource, 'face') || str_contains($utmSource, 'insta') || str_contains($utmSource, 'meta') || str_contains($utmSource, 'tiktok')) {
                $source = 'social_media';
            }

            $campaignInfo = $utmSource ? " (Campanha UTM: {$utmSource})" : '';

            CommercialLead::create([
                'school_id' => $school->id,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'formation_id' => $formation?->id,
                'source' => $source,
                'status' => 'new',
                'notes' => "Interesse registrado através do portal público da escola na formação: {$formationName}{$campaignInfo}.",
            ]);

            // Registro de conversão de lead na vitrine
            \App\Services\PortalTrafficTracker::log(
                $request,
                'school_showcase',
                'lead_submit',
                $school->id,
                $formation?->id,
                $formation?->name ?? 'Geral'
            );
        } catch (\Throwable $e) {
            Log::warning('Não foi possível gravar o CommercialLead na vitrine: ' . $e->getMessage());
        }

        // Se a escola tiver WhatsApp configurado, redireciona o interessado com mensagem pronta
        if ($school->clean_whatsapp) {
            $courseText = $formation ? " na formação *{$formation->name}*" : '';
            $msg = "Olá! Meu nome é *{$validated['name']}*. Tenho interesse em obter mais informações sobre os cursos{$courseText}.";
            $waUrl = "https://wa.me/{$school->clean_whatsapp}?text=" . urlencode($msg);

            return redirect()->away($waUrl);
        }

        return redirect()->back()->with('success', 'Sua solicitação foi enviada com sucesso! Nossa secretaria entrará em contato em breve.');
    }
}
