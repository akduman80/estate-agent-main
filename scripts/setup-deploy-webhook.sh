#!/usr/bin/env bash

set -Eeuo pipefail

if [[ "${EUID}" -ne 0 ]]; then
    echo "Run this setup once with sudo."
    exit 1
fi

PROJECT_ROOT="${1:-/var/www/estate-agent}"
DEPLOY_USER="${2:-ziyadeemlak}"
WEB_USER="${3:-www-data}"
DEPLOY_SCRIPT="$PROJECT_ROOT/scripts/deploy.sh"
SUDOERS_FILE="/etc/sudoers.d/estate-agent-deploy"
SERVICE_FILE="/etc/systemd/system/estate-agent-deploy.service"
TEMP_SUDOERS="$(mktemp)"
TEMP_SERVICE="$(mktemp)"

trap 'rm -f "$TEMP_SUDOERS" "$TEMP_SERVICE"' EXIT

test -d "$PROJECT_ROOT/.git"
test -f "$DEPLOY_SCRIPT"
id "$DEPLOY_USER" >/dev/null
id "$WEB_USER" >/dev/null

chown -R "$DEPLOY_USER:$WEB_USER" "$PROJECT_ROOT"
chmod 0750 "$DEPLOY_SCRIPT"

install -d -o "$DEPLOY_USER" -g "$WEB_USER" -m 2775 \
    "$PROJECT_ROOT/storage" \
    "$PROJECT_ROOT/storage/framework" \
    "$PROJECT_ROOT/storage/logs" \
    "$PROJECT_ROOT/bootstrap/cache" \
    "$PROJECT_ROOT/public/build"
chmod -R ug+rwX "$PROJECT_ROOT/storage" "$PROJECT_ROOT/bootstrap/cache" "$PROJECT_ROOT/public/build"

cat > "$TEMP_SERVICE" <<EOF
[Unit]
Description=Estate Agent automated deployment
After=network-online.target

[Service]
Type=oneshot
User=$DEPLOY_USER
Group=$WEB_USER
WorkingDirectory=$PROJECT_ROOT
Environment="DEPLOY_PATH=$PROJECT_ROOT"
Environment="DEPLOY_BRANCH=main"
ExecStart=$DEPLOY_SCRIPT
TimeoutStartSec=15min
EOF

install -o root -g root -m 0644 "$TEMP_SERVICE" "$SERVICE_FILE"
systemctl daemon-reload

printf '%s ALL=(root) NOPASSWD: /usr/bin/systemctl start --no-block estate-agent-deploy.service\n' "$WEB_USER" > "$TEMP_SUDOERS"
visudo -cf "$TEMP_SUDOERS"
install -o root -g root -m 0440 "$TEMP_SUDOERS" "$SUDOERS_FILE"

echo "Webhook deployment permissions are ready."
echo "Deploy user: $DEPLOY_USER"
echo "Web user: $WEB_USER"
echo "Script: $DEPLOY_SCRIPT"
echo "Service: estate-agent-deploy.service"
