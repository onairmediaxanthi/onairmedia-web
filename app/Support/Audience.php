<?php

namespace App\Support;

/**
 * Κατηγορίες κοινού-στόχου για κάθε σταθμό.
 * Βασισμένες στις τυπικές διαστάσεις των ερευνών ακροαματικότητας
 * (ηλικία, φύλο, κοινωνικο-οικονομική τάξη ΑΒ/Γ1/Γ2/ΔΕ, χώρος & ώρα ακρόασης)
 * και σε προφίλ που ζητούν οι τοπικοί διαφημιζόμενοι.
 * Αποθηκεύονται στο stations.audience (JSON) με τα παρακάτω κλειδιά.
 */
final class Audience
{
    public const GROUPS = [
        'ages' => [
            'label' => 'Ηλικίες',
            'help' => 'Οι ηλικίες που ακούνε κυρίως τον σταθμό.',
            'options' => [
                '15-24' => '15–24', '25-34' => '25–34', '35-44' => '35–44',
                '45-54' => '45–54', '55-64' => '55–64', '65+' => '65+',
            ],
        ],
        'class' => [
            'label' => 'Κοινωνικο-οικονομική τάξη',
            'help' => 'Η κλίμακα που χρησιμοποιούν οι έρευνες ακροαματικότητας.',
            'options' => [
                'AB' => 'ΑΒ · στελέχη, ελεύθεροι επαγγελματίες, υψηλό εισόδημα',
                'C1' => 'Γ1 · υπάλληλοι γραφείου, μεσαίο εισόδημα',
                'C2' => 'Γ2 · τεχνίτες, ειδικευμένοι εργαζόμενοι',
                'DE' => 'ΔΕ · ανειδίκευτοι, συνταξιούχοι, χαμηλό εισόδημα',
            ],
        ],
        'places' => [
            'label' => 'Πού ακούνε',
            'help' => null,
            'options' => [
                'car' => 'Στο αυτοκίνητο', 'work' => 'Στη δουλειά / στο κατάστημα',
                'home' => 'Στο σπίτι', 'online' => 'Online / εφαρμογή',
            ],
        ],
        'dayparts' => [
            'label' => 'Ώρες με τη μεγαλύτερη ακρόαση',
            'help' => null,
            'options' => [
                'morning' => 'Πρωί 06–10', 'midday' => 'Μεσημέρι 10–14', 'afternoon' => 'Απόγευμα 14–18',
                'evening' => 'Βράδυ 18–22', 'night' => 'Νύχτα 22–06', 'weekend' => 'Σαββατοκύριακο',
            ],
        ],
        'profiles' => [
            'label' => 'Προφίλ ακροατών',
            'help' => 'Ομάδες που ενδιαφέρουν τους διαφημιζόμενους.',
            'options' => [
                'students' => 'Μαθητές / φοιτητές', 'young_pros' => 'Νέοι επαγγελματίες',
                'families' => 'Οικογένειες με παιδιά', 'business' => 'Επιχειρηματίες / ελεύθεροι επαγγελματίες',
                'drivers' => 'Οδηγοί / επαγγελματίες του δρόμου', 'sports' => 'Φίλαθλοι',
                'nightlife' => 'Διασκέδαση / έξοδος', 'seniors' => 'Συνταξιούχοι',
                'rural' => 'Αγροτικός πληθυσμός', 'muslim' => 'Μουσουλμανική μειονότητα / τουρκόφωνο κοινό',
                'tourists' => 'Επισκέπτες / τουρίστες',
            ],
        ],
    ];

    public const GENDER = [
        'balanced' => 'Ισόρροπο', 'female' => 'Κυρίως γυναίκες', 'male' => 'Κυρίως άνδρες',
    ];

    /**
     * Ετικέτες για εμφάνιση στο site, ανά ομάδα.
     *
     * @return array<string, array{label:string, items:list<string>}>
     */
    public static function labelsFor(?array $audience): array
    {
        $out = [];
        foreach (self::GROUPS as $key => $group) {
            $items = array_values(array_filter(array_map(
                fn ($v) => isset($group['options'][$v]) ? explode('·', $group['options'][$v], 2)[0] : null,
                (array) ($audience[$key] ?? []),
            )));
            if ($items) {
                $out[$key] = ['label' => $group['label'], 'items' => array_map('trim', $items)];
            }
        }
        if (isset(self::GENDER[$audience['gender'] ?? ''])) {
            $out['gender'] = ['label' => 'Φύλο', 'items' => [self::GENDER[$audience['gender']]]];
        }

        return $out;
    }
}
