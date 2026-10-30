<?php

return [
    'label' => 'Ρύθμιση',
    'modal' => [
        'heading' => 'Ρύθμιση εφαρμογής επαλήθευσης',
        'description' => <<<'BLADE'
            Χρειάζεστε μια εφαρμογή όπως το Google Authenticator (<x-filament::link href="https://itunes.apple.com/us/app/google-authenticator/id388497605" target="_blank">iOS</x-filament::link>, <x-filament::link href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Android</x-filament::link>) ή το Microsoft Authenticator.
            BLADE,
        'content' => [
            'qr_code' => [
                'instruction' => 'Σκανάρετε αυτόν τον κωδικό QR με την εφαρμογή:',
                'alt' => 'Κωδικός QR για την εφαρμογή επαλήθευσης',
            ],
            'text_code' => [
                'instruction' => 'Ή πληκτρολογήστε αυτόν τον κωδικό χειροκίνητα:',
                'messages' => ['copied' => 'Αντιγράφηκε'],
            ],
            'recovery_codes' => [
                'instruction' => 'Φυλάξτε τους παρακάτω κωδικούς ανάκτησης σε ασφαλές μέρος. Εμφανίζονται μόνο αυτή τη φορά και θα τους χρειαστείτε αν χάσετε το κινητό σας:',
            ],
        ],
        'form' => [
            'password' => [
                'label' => 'Τρέχων κωδικός πρόσβασης',
                'validation_attribute' => 'τρέχων κωδικός',
            ],
            'code' => [
                'label' => 'Πληκτρολογήστε τον 6ψήφιο κωδικό από την εφαρμογή',
                'validation_attribute' => 'κωδικός',
                'below_content' => 'Θα πληκτρολογείτε τον 6ψήφιο κωδικό της εφαρμογής σε κάθε σύνδεση ή ευαίσθητη ενέργεια.',
                'messages' => [
                    'invalid' => 'Ο κωδικός δεν είναι σωστός.',
                    'rate_limited' => 'Πολλές προσπάθειες. Δοκιμάστε ξανά σε λίγο.',
                ],
            ],
        ],
        'actions' => [
            'submit' => ['label' => 'Ενεργοποίηση'],
        ],
    ],
    'notifications' => [
        'enabled' => ['title' => 'Η εφαρμογή επαλήθευσης ενεργοποιήθηκε'],
    ],
];
