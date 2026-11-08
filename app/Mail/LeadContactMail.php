<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadContactMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array{name: string, school_name: string, email: string, phone: string, city?: string|null, students_count?: string|null, message?: string|null, ip?: string|null} $data
     */
    public function __construct(
        public array $data
    ) {}

    public function envelope(): Envelope
    {
        $school = $this->data['school_name'] ?? 'Nova Escola';
        $city = ! empty($this->data['city']) ? " ({$this->data['city']})" : '';

        return new Envelope(
            subject: "🚀 Novo Lead Aurosoft: {$school}{$city}",
            replyTo: [
                new Address($this->data['email'], $this->data['name']),
            ]
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lead-contact',
            with: [
                'lead' => $this->data,
            ],
        );
    }
}
