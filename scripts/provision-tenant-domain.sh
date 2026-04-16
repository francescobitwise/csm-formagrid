#!/usr/bin/env bash
set -euo pipefail

# Provision a custom domain for this Laravel + Nginx app.
#
# Expected usage (as root):
#   provision-tenant-domain.sh academy.cliente.it
#
# This script:
# - creates/updates an Nginx server block for the domain
# - requests/renews a Let's Encrypt certificate via certbot (nginx plugin)
# - reloads nginx
#
# You MUST adapt:
# - APP_ROOT
# - PHP_FPM_SOCKET
# - NGINX_SITES_AVAILABLE / ENABLED paths (Debian/Ubuntu default)

DOMAIN="${1:-}"
if [[ -z "${DOMAIN}" ]]; then
  echo "Missing domain argument" >&2
  exit 2
fi

if [[ "${DOMAIN}" =~ [/:] ]]; then
  echo "Domain must be a plain host (no scheme/path/port)" >&2
  exit 2
fi

APP_ROOT="/var/www/formagrid"
PUBLIC_ROOT="${APP_ROOT}/public"
PHP_FPM_SOCKET="/run/php/php8.3-fpm.sock"

NGINX_SITES_AVAILABLE="/etc/nginx/sites-available"
NGINX_SITES_ENABLED="/etc/nginx/sites-enabled"

CONF_NAME="tenant-domain-${DOMAIN}.conf"
CONF_PATH="${NGINX_SITES_AVAILABLE}/${CONF_NAME}"

cat > "${CONF_PATH}" <<EOF
server {
  listen 80;
  server_name ${DOMAIN};

  root ${PUBLIC_ROOT};
  index index.php;

  location / {
    try_files \$uri \$uri/ /index.php?\$query_string;
  }

  location ~ \.php\$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:${PHP_FPM_SOCKET};
  }

  location ~* \.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?)\$ {
    expires 30d;
    access_log off;
  }
}
EOF

ln -sf "${CONF_PATH}" "${NGINX_SITES_ENABLED}/${CONF_NAME}"

nginx -t
systemctl reload nginx

# certbot nginx plugin will edit the vhost to add 443 + redirect.
# Set email/non-interactive flags as you prefer.
if ! command -v certbot >/dev/null 2>&1; then
  echo "certbot not found. Install it first." >&2
  exit 3
fi

certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos --redirect -m "admin@${DOMAIN}" || true

nginx -t
systemctl reload nginx

echo "Provisioning done for ${DOMAIN}"

