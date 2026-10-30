<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordGreek extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Νέος κωδικός πρόσβασης — On Air Media Group')
            ->greeting('Γεια σας,')
            ->line('Ζητήθηκε αλλαγή κωδικού για τον λογαριασμό σας.')
            ->action('Ορισμός νέου κωδικού', $url)
            ->line('Ο σύνδεσμος ισχύει για 60 λεπτά. Αν δεν το ζητήσατε εσείς, δεν χρειάζεται να κάνετε τίποτα: ο κωδικός σας δεν αλλάζει.')
            ->salutation('Η ομάδα του On Air Media Group');
    }

    protected function resetUrl($notifiable): string
    {
        return route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
    }
}
