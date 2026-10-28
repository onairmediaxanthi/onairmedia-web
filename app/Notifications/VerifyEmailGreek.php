<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailGreek extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Επιβεβαιώστε το email σας — On Air Media Group')
            ->greeting('Καλώς ήρθατε!')
            ->line('Ευχαριστούμε για την εγγραφή της επιχείρησής σας στο On Air Media Group.')
            ->line('Πατήστε το κουμπί για να επιβεβαιώσετε το email σας. Μετά θα βλέπετε τιμές και θα μπορείτε να κλείνετε καμπάνιες.')
            ->action('Επιβεβαίωση email', $url)
            ->line('Ο σύνδεσμος ισχύει για 24 ώρες. Αν δεν κάνατε εσείς την εγγραφή, αγνοήστε αυτό το μήνυμα.')
            ->salutation('Η ομάδα του On Air Media Group');
    }
}
