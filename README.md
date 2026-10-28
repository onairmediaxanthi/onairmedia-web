# On Air Media Group — onairmedia.gr

Laravel 12 · Filament 4 · PHP 8.4

## Δημοσίευση
Κάθε push στο `main` περνά τα τεστ και ανεβαίνει αυτόματα στο **new.onairmedia.gr** (`.github/workflows/deploy.yml`).

- Κώδικας στον server: `~/apps/onairmedia-web` (εκτός δημόσιου φακέλου)
- Document root: `~/new.onairmedia.gr` (μόνο δημόσια αρχεία, `index.php` που δείχνει στον κώδικα)
- Ρυθμίσεις: `~/apps/onairmedia-web/.env` (δεν ανεβαίνει ποτέ στο GitHub)
- Secret στο GitHub: `SSH_PRIVATE_KEY`

## Πρώτη εγκατάσταση
1. Μετά το πρώτο deploy, συμπλήρωσε στο `.env`: `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `PREVIEW_PASSWORD`.
2. Ξανατρέξε το deploy (Actions → Deploy → Run workflow).
3. Δημιούργησε διαχειριστή: `cd ~/apps/onairmedia-web && /opt/cpanel/ea-php84/root/usr/bin/php artisan app:create-admin`
4. Σύνδεση στο `/admin` — στην πρώτη είσοδο ζητείται ρύθμιση 2FA.
