<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantStaffCredentialsNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $plainPassword,
        public string $roleLabel,
        public ?string $loginUrl = null,
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
        $loginUrl = $this->loginUrl ?? route('tenant.login', absolute: true);

        return (new MailMessage)
            ->subject('Accesso area amministrazione — '.config('app.name'))
            ->markdown('emails.tenant.staff-credentials', [
                'userName' => $notifiable->name,
                'email' => $notifiable->email,
                'plainPassword' => $this->plainPassword,
                'roleLabel' => $this->roleLabel,
                'loginUrl' => $loginUrl,
                'tenantId' => (string) (tenant('id') ?? ''),
                'appName' => (string) config('app.name'),
            ]);
    }
}
