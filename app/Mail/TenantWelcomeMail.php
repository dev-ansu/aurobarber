<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\License;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public License $license,
        public string $adminName,
        public string $adminEmail,
        public string $initialPassword,
        public ?string $tenantUrl = null
    ) {
        $this->tenantUrl = $tenantUrl ?: ($license->getTenantUrl() ?? url('/'));
    }

    public function envelope(): Envelope
    {
        $school = $this->license->trade_name ?: $this->license->client_name;

        return new Envelope(
            subject: "🚀 Seu ambiente Aurosoft Gestão Escolar está pronto! ({$school})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-welcome',
            with: [
                'adminName' => $this->adminName,
                'adminEmail' => $this->adminEmail,
                'initialPassword' => $this->initialPassword,
                'schoolName' => $this->license->trade_name ?: $this->license->client_name,
                'tenantUrl' => $this->tenantUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
