<?php

use App\Models\ContractType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('contract_types', 'document_type')) {
            Schema::table('contract_types', function (Blueprint $table) {
                $table->string('document_type', 50)->default('contract')->after('name');
                $table->index(['document_type', 'is_active']);
            });
        }

        // Garante que os registros existentes fiquem marcados como 'contract'
        \Illuminate\Support\Facades\DB::table('contract_types')
            ->whereNull('document_type')
            ->orWhere('document_type', '')
            ->update(['document_type' => 'contract']);

        // Semeia modelos padrões para rescisão, trancamento, autorização de imagem e comparecimento
        $this->seedDefaultDocumentTemplates();
    }

    public function down(): void
    {
        if (Schema::hasColumn('contract_types', 'document_type')) {
            Schema::table('contract_types', function (Blueprint $table) {
                $table->dropIndex(['document_type', 'is_active']);
                $table->dropColumn('document_type');
            });
        }
    }

    private function seedDefaultDocumentTemplates(): void
    {
        $templates = [
            [
                'document_type' => 'cancellation',
                'name' => 'Termo de Rescisão e Distrato Contratual',
                'version' => '1.0',
                'description' => 'Minuta padrão com cláusulas de cancelamento de matrícula, apuração de encargos e quitação mútua.',
                'content' => <<<'HTML'
<p>Pelo presente instrumento particular de <strong>DISTRATO E RESCISÃO DE CONTRATO DE PRESTAÇÃO DE SERVIÇOS EDUCACIONAIS</strong>, as partes acima identificadas têm entre si, justo e acordado, o encerramento do vínculo contratual sob as seguintes cláusulas:</p>

<h3>CLÁUSULA PRIMEIRA - DA RESCISÃO DO CONTRATO</h3>
<p>Fica formalmente rescindido, a partir desta data, o Contrato de Prestação de Serviços Educacionais nº <strong>{numero_contrato}</strong> referente ao curso <strong>{curso_nome}</strong>, cessando a partir deste momento a prestação de serviços educacionais presenciais e/ou virtuais, bem como o acesso às instalações físicas e plataformas da CONTRATADA.</p>

<h3>CLÁUSULA SEGUNDA - DA MOTIVAÇÃO</h3>
<p>A rescisão é operada por solicitação expressa do(a) CONTRATANTE, com a seguinte justificativa registrada: <em>"{motivo_cancelamento}"</em>.</p>

<h3>CLÁUSULA TERCEIRA - DA MULTA RESCISÓRIA E DISPOSIÇÕES FINANCEIRAS</h3>
<p>Em conformidade com as normas contratuais e com a legislação consumerista vigente (art. 9º da Lei de Usura / Decreto 22.626/33 e Código de Defesa do Consumidor):</p>
<ul>
    <li>É aplicada a taxa/multa rescisória compensatória no valor de <strong>{valor_multa}</strong> referente aos custos operacionais, pedagógicos e administrativos do cancelamento antecipado.</li>
    <li>Ficam canceladas todas as parcelas vincendas após esta data que não possuam contraprestação de serviços já executados.</li>
</ul>

<h3>CLÁUSULA QUARTA - DA QUITAÇÃO RECÍPROCA</h3>
<p>Com o adimplemento da taxa rescisória e a regularização das obrigações pendentes até a data do encerramento, as partes conferem entre si a mais ampla, geral, rasa e irrevogável quitação, nada mais tendo a reclamar em juízo ou fora dele com relação ao contrato ora rescindido.</p>
HTML,
                'is_active' => true,
            ],
            [
                'document_type' => 'suspension',
                'name' => 'Termo de Trancamento Provisório de Matrícula',
                'version' => '1.0',
                'description' => 'Minuta com cláusulas para suspensão temporária dos estudos e garantia de vaga no retorno.',
                'content' => <<<'HTML'
<p>Pelo presente <strong>TERMO DE TRANCAMENTO PROVISÓRIO DE MATRÍCULA</strong>, a CONTRATADA e o(a) CONTRATANTE acordam a suspensão temporária da prestação dos serviços educacionais regida pelas cláusulas abaixo:</p>

<h3>CLÁUSULA PRIMEIRA - DO OBJETO E PRAZO</h3>
<p>Fica autorizado o trancamento temporário da matrícula do(a) aluno(a) no curso <strong>{curso_nome}</strong>, pelo prazo improrrogável de <strong>{dias_trancamento} dias</strong>, com início em <strong>{data_inicio_trancamento}</strong> e término previsto para <strong>{data_retorno_trancamento}</strong>.</p>

<h3>CLÁUSULA SEGUNDA - DA JUSTIFICATIVA</h3>
<p>O presente trancamento é concedido a pedido do(a) CONTRATANTE sob a motivação: <em>"{motivo_trancamento}"</em>.</p>

<h3>CLÁUSULA TERCEIRA - DAS AULAS E CONDIÇÕES FINANCEIRAS</h3>
<p>Durante o período de trancamento, ficam suspensas as aulas presenciais e o cômputo de faltas. As obrigações financeiras e mensais ficam ajustadas conforme o regimento financeiro da instituição, ficando assegurada a reserva da vaga para o retorno do aluno na data estipulada.</p>

<h3>CLÁUSULA QUARTA - DO RETORNO ÀS ATIVIDADES</h3>
<p>O(A) aluno(a) compromete-se a comparecer à secretaria da escola até a data limite de <strong>{data_retorno_trancamento}</strong> para reativação do plano pedagógico e definição da turma/sala de continuação, sob pena de cancelamento definitivo por abandono caso não se manifeste no prazo.</p>
HTML,
                'is_active' => true,
            ],
            [
                'document_type' => 'image_rights',
                'name' => 'Termo de Autorização de Uso de Imagem e Voz (LGPD)',
                'version' => '1.0',
                'description' => 'Minuta de consentimento em conformidade com a Lei Geral de Proteção de Dados (Lei 13.709/2018).',
                'content' => <<<'HTML'
<p>Pelo presente <strong>TERMO DE AUTORIZAÇÃO DE USO DE IMAGEM E VOZ</strong>, elaborado em estrita conformidade com a Lei Geral de Proteção de Dados Pessoais (Lei nº 13.709/2018 - LGPD), o(a) titular/responsável autoriza a CONTRATADA a utilizar sua imagem e voz conforme as condições a seguir:</p>

<h3>CLÁUSULA PRIMEIRA - DA FINALIDADE</h3>
<p>A presente autorização é concedida a título gratuito, com finalidade exclusivamente institucional, pedagógica e informativa, compreendendo a divulgação de atividades escolares, projetos desenvolvidos, aulas práticas, eventos comemorativos e formaturas.</p>

<h3>CLÁUSULA SEGUNDA - DOS MEIOS DE DIVULGAÇÃO</h3>
<p>A divulgação poderá ocorrer por meio de redes sociais oficiais da instituição (Instagram, Facebook, LinkedIn, YouTube), website oficial, murais físicos, materiais impressos institucionais e vídeos promocionais internos da escola.</p>

<h3>CLÁUSULA TERCEIRA - DA PRESERVAÇÃO DA DIGNIDADE E DIREITOS</h3>
<p>A CONTRATADA compromete-se a não utilizar a imagem de forma vexatória, degradante ou contrária aos bons costumes, assegurando sempre o respeito aos direitos fundamentais e à intimidade do(a) aluno(a). O consentimento poderá ser revogado a qualquer tempo mediante requisição expressa na secretaria da escola.</p>
HTML,
                'is_active' => true,
            ],
            [
                'document_type' => 'attendance',
                'name' => 'Declaração de Comparecimento / Frequência',
                'version' => '1.0',
                'description' => 'Minuta para atestado de presença do aluno para fins profissionais ou de estágio.',
                'content' => <<<'HTML'
<p>Declaramos para os devidos fins a quem possa interessar que o(a) aluno(a) <strong>{aluno_nome}</strong>, portador(a) do CPF <strong>{cpf_aluno}</strong>, devidamente matriculado(a) nesta instituição de ensino no curso <strong>{curso_nome}</strong> (Contrato nº <strong>{numero_contrato}</strong>), compareceu às atividades regulares desta instituição no dia <strong>{data_comparecimento}</strong>, no período das <strong>{horario_inicio} às {horario_fim}</strong>, para fins de <strong>{finalidade_comparecimento}</strong>.</p>

<p>Por ser a expressão da verdade, firmamos a presente declaração para que produza os efeitos legais a que se destina.</p>
HTML,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $tpl) {
            $exists = \Illuminate\Support\Facades\DB::table('contract_types')
                ->where('document_type', $tpl['document_type'])
                ->exists();

            if (! $exists) {
                \Illuminate\Support\Facades\DB::table('contract_types')->insert(array_merge($tpl, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
};

