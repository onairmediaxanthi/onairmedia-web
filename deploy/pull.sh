#!/usr/bin/env bash
# Τρέχει από cron κάθε 2 λεπτά στον server (remote μέσω ssh.github.com:443).
# Αν υπάρχει νέα έκδοση στο branch `build`, την εγκαθιστά.
set -euo pipefail

APP_DIR=/home/onairmedia/apps/onairmedia-web
WEB_DIR=/home/onairmedia/new.onairmedia.gr
KEY=/home/onairmedia/.ssh/github_pull

# Ποτέ δύο εγκαταστάσεις ταυτόχρονα
exec 9>"$APP_DIR/../.deploy.lock"
flock -n 9 || exit 0

export GIT_SSH_COMMAND="ssh -i $KEY -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new -o BatchMode=yes"
cd "$APP_DIR"

git fetch -q origin build
[ "$(git rev-parse HEAD)" = "$(git rev-parse FETCH_HEAD)" ] && exit 0

mkdir -p storage/logs
{
  echo "=== $(date '+%F %T') $(git log -1 --format=%s FETCH_HEAD)"
  git reset -q --hard FETCH_HEAD
  APP_DIR="$APP_DIR" WEB_DIR="$WEB_DIR" bash deploy/release.sh
} >> storage/logs/deploy.log 2>&1
