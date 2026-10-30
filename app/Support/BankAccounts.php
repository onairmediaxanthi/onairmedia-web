<?php

namespace App\Support;

use App\Models\Setting;

/** Οι τραπεζικοί λογαριασμοί της εταιρείας (από τις Ρυθμίσεις προσφορών) */
final class BankAccounts
{
    /** @return list<array{bank: string, iban: string, beneficiary: ?string}> */
    public static function all(): array
    {
        return array_values(array_filter(array_map(fn ($a) => [
            'bank' => trim((string) ($a['bank'] ?? '')),
            'iban' => self::format((string) ($a['iban'] ?? '')),
            'beneficiary' => trim((string) ($a['beneficiary'] ?? '')) ?: Setting::get('company_name'),
        ], (array) Setting::get('bank_accounts', [])), fn ($a) => $a['iban'] !== ''));
    }

    /** GR16 0110 1250 0000 0001 2300 695 */
    public static function format(string $iban): string
    {
        return trim(chunk_split(strtoupper(preg_replace('/\s+/', '', $iban)), 4, ' '));
    }

    /** Έλεγχος IBAN (mod 97) */
    public static function valid(string $iban): bool
    {
        $i = strtoupper(preg_replace('/\s+/', '', $iban));
        if (! preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $i) || (str_starts_with($i, 'GR') && strlen($i) !== 27)) {
            return false;
        }
        $r = substr($i, 4).substr($i, 0, 4);
        $num = '';
        foreach (str_split($r) as $c) {
            $num .= ctype_alpha($c) ? (string) (ord($c) - 55) : $c;
        }
        $mod = 0;
        foreach (str_split($num, 7) as $chunk) {
            $mod = (int) (($mod.$chunk) % 97);
        }

        return $mod === 1;
    }
}
