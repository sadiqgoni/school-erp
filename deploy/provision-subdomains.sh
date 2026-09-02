#!/bin/bash
# Processes the queue that App\Support\DirectAdminSubdomainProvisioner writes
# to, creating each pending subdomain via DirectAdmin's API and pointing its
# document root at the Laravel app's public/ folder.
#
# Must run outside PHP's process tree (cron, not `php artisan ...`) — PHP's
# own network calls to the DirectAdmin API, including a `curl` subprocess
# spawned by PHP, time out on this host even over loopback. The exact same
# command run directly by cron/SSH reaches it instantly.
#
# Install with: crontab -e
#   * * * * * /home/ndslcomn/domains/ndsl.com.ng/school-erp/deploy/provision-subdomains.sh >> /dev/null 2>&1

set -euo pipefail

APP_DIR="/home/ndslcomn/domains/ndsl.com.ng/school-erp"
ENV_FILE="${APP_DIR}/.env"
QUEUE_FILE="${APP_DIR}/storage/app/directadmin-pending-subdomains.txt"
LOG_FILE="${APP_DIR}/storage/app/directadmin-provisioning.log"
PUBLIC_DIR="${APP_DIR}/public"

[ -f "$QUEUE_FILE" ] || exit 0
[ -s "$QUEUE_FILE" ] || exit 0

USERNAME=$(grep -m1 '^DIRECTADMIN_USERNAME=' "$ENV_FILE" | cut -d '=' -f2-)
LOGIN_KEY=$(grep -m1 '^DIRECTADMIN_LOGIN_KEY=' "$ENV_FILE" | cut -d '=' -f2-)
DOMAIN=$(grep -m1 '^CENTRAL_DOMAIN=' "$ENV_FILE" | cut -d '=' -f2-)
PORT=$(grep -m1 '^DIRECTADMIN_PORT=' "$ENV_FILE" | cut -d '=' -f2-)
PORT=${PORT:-2222}

[ -n "$USERNAME" ] && [ -n "$LOGIN_KEY" ] && [ -n "$DOMAIN" ] || exit 0

PROCESSING_FILE="${QUEUE_FILE}.processing.$$"
mv "$QUEUE_FILE" "$PROCESSING_FILE"
touch "$QUEUE_FILE"

while IFS= read -r SLUG; do
    [ -z "$SLUG" ] && continue

    RESPONSE=$(curl -sk -m 15 -u "${USERNAME}:${LOGIN_KEY}" \
        --data-urlencode "action=create" \
        --data-urlencode "domain=${DOMAIN}" \
        --data-urlencode "subdomain=${SLUG}" \
        "https://127.0.0.1:${PORT}/CMD_API_SUBDOMAINS")

    if echo "$RESPONSE" | grep -qi 'error=0\|exist'; then
        DOCROOT="/home/${USERNAME}/domains/${SLUG}.${DOMAIN}/public_html"

        if [ -L "$DOCROOT" ] && [ "$(readlink "$DOCROOT")" = "$PUBLIC_DIR" ]; then
            : # already correct
        else
            rm -rf "$DOCROOT"
            ln -s "$PUBLIC_DIR" "$DOCROOT"
        fi

        echo "$(date '+%F %T') OK ${SLUG}" >> "$LOG_FILE"
    else
        echo "$SLUG" >> "$QUEUE_FILE"
        echo "$(date '+%F %T') RETRY ${SLUG}: ${RESPONSE}" >> "$LOG_FILE"
    fi
done < "$PROCESSING_FILE"

rm -f "$PROCESSING_FILE"
