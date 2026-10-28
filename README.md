# On Air Media Group — onairmedia.gr

Laravel 12 · Filament 4 · PHP 8.4

## Δημοσίευση
Κάθε push στο `main` περνά τα τεστ και το GitHub φτιάχνει έτοιμη έκδοση στο branch `build` (`.github/workflows/deploy.yml`).
Ο server την κατεβάζει μόνος του κάθε λεπτό (`deploy/pull.sh` από cron) και την εγκαθιστά (`deploy/release.sh`).
Ο server δεν δέχεται εισερχόμενο SSH· τραβά από το GitHub με read-only deploy key (`~/.ssh/github_pull`, ed25519).
Ιστορικό εγκαταστάσεων: `~/apps/onairmedia-web/storage/logs/deploy.log`

- Κώδικας στον server: `~/apps/onairmedia-web` (εκτός δημόσιου φακέλου)
- Document root: `~/new.onairmedia.gr` (μόνο δημόσια αρχεία, `index.php` που δείχνει στον κώδικα)
- Ρυθμίσεις: `~/apps/onairmedia-web/.env` (δεν ανεβαίνει ποτέ στο GitHub)

## Πρώτη εγκατάσταση
1. Μετά το πρώτο deploy, συμπλήρωσε στο `.env`: `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `PREVIEW_PASSWORD`.
2. Τρέξε `bash ~/apps/onairmedia-web/deploy/release.sh` με `APP_DIR`/`WEB_DIR` ή κάνε νέο push.
3. Δημιούργησε διαχειριστή: `cd ~/apps/onairmedia-web && /opt/cpanel/ea-php84/root/usr/bin/php artisan app:create-admin`
4. Σύνδεση στο `/admin` — στην πρώτη είσοδο ζητείται ρύθμιση 2FA.
