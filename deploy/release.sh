#!/usr/bin/env bash
# Τρέχει στον server μετά από κάθε upload.
#   APP_DIR = ο κώδικας (εκτός δημόσιου φακέλου)
#   WEB_DIR = ο document root του domain (μόνο δημόσια αρχεία)
set -euo pipefail

: "${APP_DIR:?}" "${WEB_DIR:?}"
PHP=/opt/cpanel/ea-php84/root/usr/bin/php
cd "$APP_DIR"

# 1. Μόνιμοι φάκελοι (δεν σβήνονται ποτέ από το upload)
mkdir -p storage/app/public storage/app/private storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
chmod -R u+rwX,go-w storage bootstrap/cache

# 2. Αρχείο ρυθμίσεων: την πρώτη φορά φτιάχνεται από το πρότυπο
FIRST_RUN=0
if [ ! -f .env ]; then
  cp deploy/env.staging.example .env
  chmod 600 .env
  "$PHP" artisan key:generate --force
  FIRST_RUN=1
fi

# 3. Δημόσια αρχεία → document root, χωρίς να αγγίζουμε τα αρχεία του cPanel
rsync -a --delete \
  --filter='P .htaccess' --filter='P .user.ini' --filter='P php.ini' \
  --filter='P .well-known/' --filter='P cgi-bin/' --filter='P storage' \
  --exclude='index.php' --exclude='.htaccess' --exclude='storage' \
  public/ "$WEB_DIR/"

# index.php που δείχνει στον κώδικα εκτός document root
sed "s#__DIR__.'/../#'$APP_DIR/#g" public/index.php > "$WEB_DIR/index.php.new"
mv "$WEB_DIR/index.php.new" "$WEB_DIR/index.php"

# .htaccess = κανόνες Laravel + ό,τι έχει γράψει το cPanel (PHP handler, ini)
HT="$WEB_DIR/.htaccess"
{
  cat public/.htaccess
  echo
  if [ -f "$HT" ]; then
    awk '/BEGIN cPanel-generated/{keep=1} keep{print} /END cPanel-generated/{keep=0}' "$HT"
  fi
} > "$HT.new"
mv "$HT.new" "$HT"

# Αρχεία που ανεβάζουν οι διαχειριστές (λογότυπα, slides)
ln -sfn "$APP_DIR/storage/app/public" "$WEB_DIR/storage"

# 4. Βάση και cache
if grep -qE '^DB_PASSWORD=.+' .env; then
  "$PHP" artisan migrate --force
  "$PHP" artisan optimize
  "$PHP" artisan filament:optimize || true
  echo "Release OK"
else
  "$PHP" artisan optimize:clear >/dev/null
  echo "------------------------------------------------------------"
  echo "Λείπουν τα στοιχεία βάσης. Συμπλήρωσε DB_DATABASE, DB_USERNAME,"
  echo "DB_PASSWORD στο $APP_DIR/.env (File Manager) και ξανατρέξε το deploy."
  echo "------------------------------------------------------------"
fi
[ "$FIRST_RUN" = 1 ] && echo "Πρώτη εγκατάσταση: δημιουργήθηκε νέο .env"
exit 0
