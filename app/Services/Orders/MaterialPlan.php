<?php

namespace App\Services\Orders;

use App\Models\AdSlot;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\Screen;
use App\Models\Station;
use App\Support\FileInspector;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

/**
 * Τι υλικό χρειάζεται μια παραγγελία, ανά μέσο: ένα «κουτί» για κάθε σποτ, banner, πινακίδα κ.λπ.,
 * με αναλυτικές οδηγίες, επιτρεπτούς τύπους αρχείων και αυτόματο έλεγχο (ποιότητα ήχου, διαστάσεις).
 * Όπου έχει αγοραστεί δημιουργικό (π.χ. παραγωγή σποτ), το κουτί ζητά κείμενο/στοιχεία αντί για έτοιμο αρχείο.
 */
final class MaterialPlan
{
    public const AUDIO = ['mp3', 'wav'];
    public const IMAGES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    public const LOGO = ['svg', 'png', 'pdf', 'ai', 'eps', 'jpg', 'jpeg'];
    public const DOCS = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];

    /** @return array<string, array<string, mixed>> κλειδί κουτιού => περιγραφή */
    public static function for(Order $order): array
    {
        $items = (array) ($order->quote?->items ?? []);
        $creative = collect($items)->where('type', 'creative')->pluck('spec.key')->filter()->all();
        $makesSpot = (bool) array_intersect($creative, ['spot_production', 'spot_production_voices']);
        $slots = [];

        // Ραδιόφωνο: ένα σποτ ανά διάρκεια (συνήθως το ίδιο παίζει σε όλους τους σταθμούς)
        $bySeconds = [];
        foreach ($items as $l) {
            if (($l['type'] ?? null) === 'radio' && ! empty($l['spec']['station_id'])) {
                $bySeconds[(int) ($l['spec']['seconds'] ?? 30)][] = (int) $l['spec']['station_id'];
            }
        }
        $names = Station::query()->whereIn('id', array_merge([], ...array_values($bySeconds)) ?: [0])->pluck('name', 'id');
        foreach ($bySeconds as $sec => $ids) {
            $for = 'Παίζει σε: '.collect($ids)->unique()->map(fn ($id) => $names[$id] ?? '')->filter()->implode(', ');
            $slots['radio:'.$sec] = $makesSpot ? [
                'title' => 'Κείμενο για το σποτ σας ('.$sec.'″)', 'subtitle' => $for, 'icon' => 'mic', 'we_make' => true, 'required' => true,
                'accept' => array_merge(self::DOCS, self::AUDIO), 'text' => 'Τι θέλετε να λέει το σποτ', 'text_required' => true,
                'text_hint' => 'π.χ. «Black Friday στο Καφέ Πλατεία: όλοι οι καφέδες στο μισό, Παρασκευή 28/11. Πλατεία Ελευθερίας 3, τηλ. 25410 12345».',
                'help' => [
                    'Το σποτ το ηχογραφούμε εμείς στο studio. Γράψτε μας το μήνυμα με δικά σας λόγια· το τελικό κείμενο το φτιάχνουμε εμείς και σας το στέλνουμε για έγκριση.',
                    'Περίπου '.(int) round($sec * 2.3).' λέξεις χωράνε σε '.$sec.'″.',
                    'Αναφέρετε ό,τι πρέπει οπωσδήποτε να ακουστεί: επωνυμία, προσφορά, διεύθυνση, τηλέφωνο, site.',
                    'Αν έχετε μουσική ή ήχο που σας αρέσει ως παράδειγμα, ανεβάστε το (προαιρετικό).',
                ],
            ] : [
                'title' => 'Ραδιοφωνικό σποτ '.$sec.'″', 'subtitle' => $for, 'icon' => 'mic', 'we_make' => false, 'required' => true,
                'accept' => self::AUDIO, 'audio' => ['seconds' => $sec], 'max_files' => 3,
                'help' => [
                    'MP3 στα 320 kbps, ή WAV 44,1 kHz / 16-bit, στερεοφωνικό.',
                    'Διάρκεια περίπου '.$sec.' δευτερόλεπτα'.(self::limit('spot_tolerance_seconds') > 0 ? ' (±'.self::limit('spot_tolerance_seconds').'″).' : '.'),
                    'Χωρίς σιωπή στην αρχή και στο τέλος.',
                    'Ομοιόμορφη, δυνατή ένταση χωρίς παραμορφώσεις (peak έως −1 dB).',
                    'Η μουσική πρέπει να είναι αδειοδοτημένη ή ελεύθερη δικαιωμάτων.',
                    'Αν έχετε δύο εκδοχές (π.χ. πρωί / απόγευμα), ανεβάστε και τις δύο και γράψτε μας ποια παίζει πότε.',
                ],
            ];
        }

        // Αναφορές στον αέρα
        foreach ($items as $l) {
            if (($l['type'] ?? null) !== 'mention') {
                continue;
            }
            $live = str_starts_with((string) $l['label'], 'Αναφορά');
            $key = $live ? 'mention:live' : 'mention:recorded';
            $slots[$key] ??= $live ? [
                'title' => 'Στοιχεία για την αναφορά του παραγωγού', 'subtitle' => 'Ζωντανή αναφορά στην εκπομπή', 'icon' => 'radio',
                'we_make' => true, 'required' => true, 'accept' => self::DOCS, 'text' => 'Τι να πει ο παραγωγός', 'text_required' => true,
                'text_hint' => 'Τα 2–3 βασικά σημεία: ποιοι είστε, τι προσφέρετε, πού σας βρίσκουν.',
                'help' => ['Ο παραγωγός μιλά με δικά του λόγια, φυσικά μέσα στην εκπομπή. Δώστε μας τα σημεία που πρέπει να αναφερθούν.'],
            ] : [
                'title' => 'Κείμενο για έτοιμη εκφώνηση', 'subtitle' => 'Το διαβάζει ο εκφωνητής όπως το γράψετε', 'icon' => 'radio',
                'we_make' => true, 'required' => true, 'accept' => self::DOCS, 'text' => 'Κείμενο εκφώνησης', 'text_required' => true,
                'text_hint' => 'Έως περίπου 50 λέξεις (20″).',
                'help' => ['Γράψτε ακριβώς το κείμενο που θα διαβαστεί, έως περίπου 50 λέξεις. Τα ονόματα και οι διευθύνσεις με τόνους, όπως προφέρονται.'],
            ];
        }

        // Banner στο thrakitoday.gr: κάθε θέση με τις δικές της διαστάσεις
        $makesBanner = in_array('banner_design', $creative, true);
        foreach ($items as $l) {
            if (($l['type'] ?? null) !== 'banner' || empty($l['spec']['slot_id']) || isset($slots['banner:'.$l['spec']['slot_id']])) {
                continue;
            }
            $ad = AdSlot::query()->find($l['spec']['slot_id']);
            $sizes = self::parseSizes((string) $ad?->dimensions);
            $sizeText = $sizes ? collect($sizes)->map(fn ($g) => ($g['label'] ? $g['label'].': ' : '').collect($g['options'])->map(fn ($o) => $o[0].'×'.$o[1])->implode(' ή ').' px')->implode(' · ') : null;
            $slots['banner:'.$l['spec']['slot_id']] = $makesBanner ? [
                'title' => 'Στοιχεία για το banner σας', 'subtitle' => 'thrakitoday.gr · '.($ad?->name ?? 'banner').($sizeText ? ' · '.$sizeText : ''), 'icon' => 'news',
                'we_make' => true, 'required' => true, 'accept' => array_merge(self::LOGO, self::IMAGES, self::DOCS), 'text' => 'Μήνυμα και σύνδεσμος του banner', 'text_required' => true,
                'text_hint' => 'Το μήνυμα (λίγες λέξεις), τυχόν προσφορά, και τη σελίδα που θα ανοίγει με το κλικ (π.χ. https://…).',
                'help' => [
                    'Το banner το σχεδιάζουμε εμείς και σας το στέλνουμε για έγκριση.',
                    'Ανεβάστε το λογότυπό σας σε καλή ανάλυση (ιδανικά SVG, PDF ή PNG με διάφανο φόντο) και, αν θέλετε, 1–3 φωτογραφίες.',
                ],
            ] : [
                'title' => 'Banner · '.($ad?->name ?? 'thrakitoday.gr'), 'subtitle' => 'thrakitoday.gr'.($sizeText ? ' · '.$sizeText : ''), 'icon' => 'news',
                'we_make' => false, 'required' => true, 'accept' => $sizes ? self::IMAGES : array_merge(self::IMAGES, ['psd', 'pdf', 'zip']),
                'image' => $sizes ? ['sizes' => $sizes] : null, 'text' => 'Σύνδεσμος (URL) που θα ανοίγει με το κλικ', 'text_required' => false,
                'text_hint' => 'https://…', 'max_files' => max(2, count($sizes) * 2),
                'help' => array_values(array_filter([
                    $sizes ? 'Διαστάσεις ακριβώς: '.$sizeText.'. Γίνονται δεκτές και οι διπλάσιες (για οθόνες υψηλής ανάλυσης).' : 'Για αυτή τη θέση θα σας στείλουμε το πρότυπο με τις ακριβείς διαστάσεις.',
                    count($sizes) > 1 ? 'Χρειάζεται ένα αρχείο για κάθε διάσταση.' : null,
                    'JPG, PNG, WEBP ή GIF (το κινούμενο GIF έως 15″).',
                    self::limit('banner_max_kb') > 0 ? 'Μέγεθος αρχείου έως '.self::limit('banner_max_kb').' KB, για να φορτώνει γρήγορα.' : null,
                    'Καθαρό μήνυμα με λίγες λέξεις και ευδιάκριτο λογότυπο. Αποφύγετε μικρά γράμματα.',
                ])),
            ];
        }

        // Πινακίδα LED
        $makesLed = in_array('led_graphic', $creative, true);
        foreach ($items as $l) {
            if (($l['type'] ?? null) !== 'led' || empty($l['spec']['screen_id']) || isset($slots['led:'.$l['spec']['screen_id']])) {
                continue;
            }
            $sc = Screen::query()->find($l['spec']['screen_id']);
            $res = self::parseSizes((string) ($sc?->resolution ?: ''))[0]['options'][0] ?? null;
            $sec = (int) ($sc?->slot_seconds ?: 10);
            $slots['led:'.$l['spec']['screen_id']] = $makesLed ? [
                'title' => 'Στοιχεία για το γραφικό της πινακίδας', 'subtitle' => ($sc?->name ?? 'Πινακίδα LED').($res ? ' · '.$res[0].'×'.$res[1].' px' : ''), 'icon' => 'screen',
                'we_make' => true, 'required' => true, 'accept' => array_merge(self::LOGO, self::IMAGES, self::DOCS), 'text' => 'Τι θέλετε να δείχνει η πινακίδα', 'text_required' => true,
                'text_hint' => 'Το μήνυμα σε λίγες λέξεις (διαβάζεται σε '.$sec.'″), τυχόν προσφορά, τηλέφωνο.',
                'help' => [
                    'Το γραφικό το σχεδιάζουμε εμείς και σας το στέλνουμε για έγκριση.',
                    'Ανεβάστε λογότυπο σε καλή ανάλυση και, αν θέλετε, 1–2 φωτογραφίες.',
                ],
            ] : [
                'title' => 'Γραφικό για την πινακίδα', 'subtitle' => ($sc?->name ?? 'Πινακίδα LED').($res ? ' · '.$res[0].'×'.$res[1].' px' : '').' · '.$sec.'″', 'icon' => 'screen',
                'we_make' => false, 'required' => true, 'accept' => array_merge(self::IMAGES, ['mp4', 'mov']),
                'image' => $res ? ['sizes' => [['label' => null, 'options' => [$res]]], 'led' => true] : null, 'max_files' => 3,
                'help' => array_values(array_filter([
                    $res ? 'Ανάλυση ακριβώς '.$res[0].'×'.$res[1].' pixels (οριζόντιο/κάθετο όπως η οθόνη).' : 'Την ακριβή ανάλυση της οθόνης θα σας τη στείλουμε· αν έχετε ήδη έτοιμο γραφικό, ανεβάστε το και το προσαρμόζουμε.',
                    'Εικόνα JPG ή PNG, ή βίντεο MP4 (H.264) διάρκειας '.$sec.'″ χωρίς ήχο.',
                    'Λίγες, μεγάλες λέξεις: ο περαστικός έχει '.$sec.' δευτερόλεπτα να το διαβάσει.',
                    'Έντονες αντιθέσεις και φωτεινά χρώματα· το λεπτό κείμενο και το γκρι χάνονται στο LED.',
                ])),
            ];
        }

        // Χορηγούμενο άρθρο
        $article = collect($items)->firstWhere('type', 'article');
        if ($article) {
            $writing = (bool) ($article['spec']['writing'] ?? false) || collect($items)->contains(fn ($l) => str_contains((string) $l['label'], 'Συγγραφή άρθρου'));
            $slots['article'] = [
                'title' => $writing ? 'Στοιχεία για το άρθρο σας' : 'Κείμενο και φωτογραφίες άρθρου', 'subtitle' => 'Χορηγούμενο άρθρο στο thrakitoday.gr', 'icon' => 'news',
                'we_make' => $writing, 'required' => true, 'accept' => array_merge(self::DOCS, self::IMAGES), 'max_files' => 8,
                'text' => $writing ? 'Τι θέλετε να γράφει το άρθρο' : 'Τίτλος ή σημείωση', 'text_required' => $writing,
                'help' => $writing ? [
                    'Το άρθρο το γράφουμε εμείς και σας το στέλνουμε για έγκριση πριν δημοσιευτεί.',
                    'Γράψτε μας για την επιχείρηση, τι θέλετε να προβληθεί, και ανεβάστε 2–5 φωτογραφίες (οριζόντιες, τουλάχιστον 1200 px πλάτος).',
                ] : [
                    'Κείμενο σε Word ή PDF, περίπου 400–700 λέξεις, με τίτλο.',
                    '2–5 φωτογραφίες οριζόντιες, τουλάχιστον 1200 px πλάτος, χωρίς κείμενο πάνω τους.',
                    'Τα άρθρα σημειώνονται ως «Χορηγούμενο», όπως ορίζει ο νόμος.',
                ],
            ];
        }

        // Πακέτα: ένα γενικό κουτί
        foreach (collect($items)->where('type', 'package') as $l) {
            $slots['package'] ??= [
                'title' => 'Υλικό για το πακέτο', 'subtitle' => $l['label'], 'icon' => 'star', 'we_make' => false, 'required' => true,
                'accept' => array_merge(self::AUDIO, self::IMAGES, self::LOGO, self::DOCS, ['mp4']), 'max_files' => 10, 'text' => 'Σημείωση', 'text_required' => false,
                'help' => ['Ανεβάστε ό,τι έχετε: σποτ (MP3 320 kbps), λογότυπο, γραφικά. Θα επικοινωνήσουμε για ό,τι λείπει.'],
            ];
        }

        // Λογότυπο: πάντα χρήσιμο, προαιρετικό
        $slots['logo'] = [
            'title' => 'Λογότυπο', 'subtitle' => 'Προαιρετικό · μας βοηθά σε όλα τα μέσα', 'icon' => 'star', 'we_make' => false, 'required' => false,
            'accept' => self::LOGO, 'max_files' => 3,
            'help' => ['Ιδανικά σε διανυσματική μορφή (SVG, PDF, AI, EPS) ή PNG με διάφανο φόντο, τουλάχιστον 1000 px.'],
        ];

        return array_map(fn ($s) => $s + ['max_mb' => \App\Models\Order::FILE_MAX_MB, 'audio' => null, 'image' => null, 'text' => null, 'text_required' => false, 'text_hint' => null, 'max_files' => 5], $slots);
    }

    /** Όρια ελέγχου υλικού από τις Ρυθμίσεις προσφορών (0 = χωρίς έλεγχο) */
    public static function limit(string $key): int
    {
        return max(0, (int) \App\Models\Setting::get($key, ['spot_tolerance_seconds' => 3, 'spot_min_kbps' => 128, 'spot_good_kbps' => 256, 'banner_max_kb' => 300][$key] ?? 0));
    }

    /**
     * «728×90 · κινητό 320×100» → [[label:null, options:[[728,90]]], [label:'κινητό', options:[[320,100]]]]
     * «300×250 / 336×280»      → [[label:null, options:[[300,250],[336,280]]]]  (οποιαδήποτε από τις δύο)
     *
     * @return list<array{label: ?string, options: list<array{0:int,1:int}>}>
     */
    public static function parseSizes(string $s): array
    {
        $groups = [];
        foreach (preg_split('/\s*[·;,]\s*/u', $s) as $part) {
            preg_match_all('/(\d{2,5})\s*[x×X]\s*(\d{2,5})/u', $part, $m, PREG_SET_ORDER);
            if (! $m) {
                continue;
            }
            $label = trim(preg_replace('/(\d{2,5})\s*[x×X]\s*(\d{2,5})|\//u', '', $part)) ?: null;
            $groups[] = ['label' => $label, 'options' => array_map(fn ($x) => [(int) $x[1], (int) $x[2]], $m)];
        }

        return $groups;
    }

    /**
     * Έλεγχος ενός αρχείου για ένα κουτί. Πετάει εξήγηση αν δεν γίνεται δεκτό, αλλιώς επιστρέφει τα στοιχεία του
     * μαζί με τυχόν προειδοποιήσεις (π.χ. 192 kbps αντί 320, διάρκεια 32″ αντί 30″).
     *
     * @return array<string, mixed>
     */
    public static function check(array $slot, UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, $slot['accept'], true)) {
            throw new InvalidArgumentException('Το «'.$file->getClientOriginalName().'» δεν ταιριάζει εδώ. Δεκτά: '.strtoupper(implode(', ', array_unique($slot['accept']))).'.');
        }
        $meta = FileInspector::inspect($file->getRealPath(), $ext);
        $warn = [];

        if ($slot['audio']) {
            $want = (int) $slot['audio']['seconds'];
            if (! $meta) {
                throw new InvalidArgumentException('Δεν μπορέσαμε να διαβάσουμε το «'.$file->getClientOriginalName().'» ως αρχείο ήχου. Ελέγξτε ότι είναι MP3 ή WAV.');
            }
            $minKbps = self::limit('spot_min_kbps');
            if ($ext === 'mp3' && $minKbps > 0 && ($meta['kbps'] ?? 0) < $minKbps) {
                throw new InvalidArgumentException('Το σποτ είναι σε πολύ χαμηλή ποιότητα ('.$meta['kbps'].' kbps) και θα ακουστεί άσχημα στον αέρα. Στείλτε το σε MP3 320 kbps ή WAV.');
            }
            if ($ext === 'mp3' && $meta['kbps'] < self::limit('spot_good_kbps')) {
                $warn[] = 'Ποιότητα '.$meta['kbps'].' kbps: δεκτό, αλλά για καλύτερο ήχο προτιμούμε 320 kbps.';
            }
            if (($meta['sample_rate'] ?? 44100) < 44100) {
                $warn[] = 'Συχνότητα '.number_format($meta['sample_rate'] / 1000, 1, ',', '').' kHz: προτιμούμε 44,1 ή 48 kHz.';
            }
            if (($meta['channels'] ?? 2) < 2) {
                $warn[] = 'Μονοφωνικό αρχείο: δεκτό, αλλά προτιμούμε στερεοφωνικό.';
            }
            // Η διάρκεια δεν απορρίπτει ποτέ: μόνο σημείωση, πέρα από την ανοχή των ρυθμίσεων
            $tol = self::limit('spot_tolerance_seconds');
            if (isset($meta['seconds']) && $tol > 0 && abs($meta['seconds'] - $want) > $tol) {
                $warn[] = 'Διάρκεια '.number_format($meta['seconds'], 1, ',', '').'″ ενώ η καμπάνια είναι για σποτ '.$want.'″.';
            }
        }

        if ($slot['image'] && in_array($ext, self::IMAGES, true)) {
            if (! $meta) {
                throw new InvalidArgumentException('Δεν μπορέσαμε να διαβάσουμε την εικόνα «'.$file->getClientOriginalName().'».');
            }
            $match = self::matchSize($slot['image']['sizes'], $meta['width'], $meta['height']);
            if ($match === null) {
                $need = collect($slot['image']['sizes'])->flatMap(fn ($g) => $g['options'])->map(fn ($o) => $o[0].'×'.$o[1])->unique()->implode(', ');
                throw new InvalidArgumentException('Η εικόνα είναι '.$meta['width'].'×'.$meta['height'].' px, ενώ εδώ χρειάζεται '.$need.' px (ή το διπλάσιο).');
            }
            $meta['fits'] = $match;
            $maxKb = self::limit('banner_max_kb');
            if (empty($slot['image']['led']) && $maxKb > 0 && $file->getSize() > $maxKb * 1024) {
                $warn[] = 'Το αρχείο είναι '.round($file->getSize() / 1024).' KB· έως '.$maxKb.' KB φορτώνει πιο γρήγορα. Μπορούμε να το συμπιέσουμε εμείς.';
            }
        } elseif ($slot['image'] && in_array($ext, ['mp4', 'mov'], true)) {
            $warn[] = 'Το βίντεο θα το ελέγξουμε εμείς για ανάλυση και διάρκεια.';
        }

        $meta['warnings'] = $warn;

        return $meta;
    }

    /** Ποια διάσταση καλύπτει (π.χ. "728x90"), δεκτό και το διπλάσιο */
    public static function matchSize(array $groups, int $w, int $h): ?string
    {
        foreach ($groups as $g) {
            foreach ($g['options'] as [$ow, $oh]) {
                if (($w === $ow && $h === $oh) || ($w === $ow * 2 && $h === $oh * 2)) {
                    return $ow.'x'.$oh;
                }
            }
        }

        return null;
    }

    /**
     * Κατάσταση κάθε κουτιού: done | missing | optional, και τι λείπει (π.χ. διάσταση κινητού).
     *
     * @return array<string, array{state: string, missing: list<string>}>
     */
    public static function status(Order $order, ?array $plan = null): array
    {
        $plan ??= self::for($order);
        $files = $order->files()->where('from', 'customer')->get()->groupBy('slot');
        $brief = (array) ($order->brief ?? []);
        $out = [];
        foreach ($plan as $key => $slot) {
            /** @var \Illuminate\Support\Collection<int, OrderFile> $fs */
            $fs = $files->get($key, collect());
            $hasText = filled($brief[$key] ?? null);
            $missing = [];
            if ($slot['text_required'] && ! $hasText) {
                $missing[] = $slot['text'];
            }
            if (! $slot['we_make']) {
                if (! empty($slot['image']['sizes'])) {
                    foreach ($slot['image']['sizes'] as $g) {
                        $ok = $fs->contains(fn ($f) => in_array($f->meta['fits'] ?? null, array_map(fn ($o) => $o[0].'x'.$o[1], $g['options']), true))
                            || $fs->contains(fn ($f) => in_array($f->extension(), ['mp4', 'mov'], true));
                        if (! $ok) {
                            $missing[] = ($g['label'] ? $g['label'].' ' : '').collect($g['options'])->map(fn ($o) => $o[0].'×'.$o[1])->implode(' ή ');
                        }
                    }
                } elseif ($fs->isEmpty()) {
                    $missing[] = 'αρχείο';
                }
            }
            $out[$key] = ['state' => $missing ? ($slot['required'] ? 'missing' : 'optional') : 'done', 'missing' => $missing];
        }

        return $out;
    }

    /** Όλα τα υποχρεωτικά κουτιά είναι έτοιμα */
    public static function complete(Order $order): bool
    {
        return ! in_array('missing', array_column(self::status($order), 'state'), true);
    }
}
