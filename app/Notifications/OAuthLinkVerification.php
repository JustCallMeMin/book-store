<?php

namespace App\Notifications;

use App\Models\OAuthVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OAuthLinkVerification extends Notification implements ShouldQueue
{
    use Queueable;

    protected OAuthVerification $verification;

    public function __construct(OAuthVerification $verification)
    {
        $this->verification = $verification;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = url("/api/auth/{$this->verification->provider}/verify/{$this->verification->token}");

        return (new MailMessage)
            ->subject('Xác thực liên kết tài khoản ' . ucfirst($this->verification->provider))
            ->markdown('emails.oauth-link-verification', [
                'user' => $notifiable,
                'verification' => $this->verification,
                'verificationUrl' => $verificationUrl
            ]);
    }
} 