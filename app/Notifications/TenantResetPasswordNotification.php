<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token
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
        $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        $url = route('tenant.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], absolute: true);

        return (new MailMessage)
            ->subject('Reimposta la password — '.config('app.name'))
            ->markdown('emails.tenant.reset-password', [
                'url' => $url,
                'expire' => $expire,
                'userName' => $notifiable->name,
                'tenantId' => (string) (tenant('id') ?? ''),
                'appName' => (string) config('app.name'),
            ]);
    }
}
