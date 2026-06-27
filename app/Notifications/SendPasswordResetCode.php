<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendPasswordResetCode extends Notification
{
    use Queueable;

    public function __construct(public string $code) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your password reset code'))
            ->greeting(__('Password reset'))
            ->line(__('Use the code below to reset your password. It expires in 60 minutes.'))
            ->line(new \Illuminate\Support\HtmlString('<p style="font-size:28px;font-weight:700;letter-spacing:6px;margin:16px 0;">'.e($this->code).'</p>'))
            ->line(__('If you did not request a password reset, no further action is required.'));
    }
}
