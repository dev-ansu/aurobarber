<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\LandingLead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LandingLeadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LandingLead|array $lead
    ) {}

    public function envelope(): Envelope
    {
        $shop = is_array($this->lead)
            ? ($this->lead['barbershop_name'] ?? 'Nova Barbearia')
            : ($this->lead->barbershop_name ?? 'Nova Barbearia');

        $city = is_array($this->lead)
            ? ($this->lead['city'] ?? null)
            : ($this->lead->city ?? null);

        $citySuffix = ! empty($city) ? " ({$city})" : '';

        $replyEmail = is_array($this->lead)
            ? ($this->lead['email'] ?? null)
            : ($this->lead->email ?? null);

        $replyName = is_array($this->lead)
            ? ($this->lead['name'] ?? null)
            : ($this->lead->name ?? null);

        $envelope = new Envelope(
            subject: "💈 Novo Lead AuroBarber: {$shop}{$citySuffix}",
        );

        if ($replyEmail && filter_var($replyEmail, FILTER_VALIDATE_EMAIL)) {
            $envelope->replyTo = [
                new Address($replyEmail, $replyName ?? 'Lead Interessado'),
            ];
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.landing-lead',
            with: [
                'lead' => $this->lead,
            ],
        );
    }
}
