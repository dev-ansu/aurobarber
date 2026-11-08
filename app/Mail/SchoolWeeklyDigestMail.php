<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SchoolWeeklyDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<string, mixed> $metrics
     */
    public function __construct(
        public School $school,
        public array $metrics,
        public ?string $recipientName = null
    ) {}

    public function envelope(): Envelope
    {
        $schoolName = $this->school->trade_name ?: $this->school->legal_name ?: 'Aurosoft';
        $period = $this->metrics['period_label'] ?? 'Semana';

        return new Envelope(
            subject: "📊 Resumo Semanal da Diretoria: {$schoolName} ({$period})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.school-weekly-digest',
            with: [
                'school' => $this->school,
                'metrics' => $this->metrics,
                'recipientName' => $this->recipientName ?? 'Diretor(a)',
            ],
        );
    }
}
