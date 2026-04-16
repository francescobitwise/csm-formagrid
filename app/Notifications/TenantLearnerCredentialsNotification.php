<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantLearnerCredentialsNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $plainPassword
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = route('tenant.login', absolute: true);

        return (new MailMessage)
            ->subject('Accesso alla piattaforma — '.config('app.name'))
            ->markdown('emails.tenant.learner-credentials', [
                'userName' => $notifiable->name,
                'email' => $notifiable->email,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => $loginUrl,
                'tenantId' => (string) (tenant('id') ?? ''),
                'appName' => (string) config('app.name'),
            ]);
    }
}
