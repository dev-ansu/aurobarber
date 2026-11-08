<?php

namespace App\Notifications;

use App\Models\Barbershop;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBarbershopWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Barbershop $barbershop,
        public string $temporaryPassword,
        public string $loginUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Sua Barbearia {$this->barbershop->name} está pronta no AuroBarber! 💈")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("É um prazer ter você conosco! Sua barbearia **{$this->barbershop->name}** foi cadastrada e provisionada com sucesso na plataforma AuroBarber.")
            ->line('Abaixo estão seus dados exclusivos de acesso ao Painel de Gestão:')
            ->line("📧 **E-mail de Login:** {$notifiable->email}")
            ->line("🔑 **Senha Temporária:** `{$this->temporaryPassword}`")
            ->action('Acessar Painel da Barbearia', $this->loginUrl)
            ->line('⚠️ **Aviso de Segurança:** Esta é uma senha temporária gerada pelo sistema. No seu primeiro acesso, você será solicitado a cadastrar uma nova senha definitiva e confidencial.')
            ->line('Seja muito bem-vindo ao AuroBarber. Estamos juntos para transformar a gestão e a experiência dos seus clientes!');
    }
}
