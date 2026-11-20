#!/usr/bin/env bash
# =============================================================================
# Pixellap — server provisioning for Ubuntu 24.04 (run as root).
#
#   sudo bash deploy/scripts/provision.sh
#
# Idempotent: safe to run again at any time. Run it once to set the server up,
# and AGAIN after certbot has issued the certificates — it then switches Nginx
# from the temporary port-80 site to the real HTTPS sites.
#
# Optional environment variables:
#   DEPLOY_USER=deploy           Linux user that owns and runs both apps
#   SERVER_HOSTNAME=ppixelmedia  Hostname to set
#   HARDEN_SSH=1                 Key-only SSH, no root login, MaxAuthTries 3.
#                                ONLY after you proved `ssh deploy@SERVER` + sudo
#                                work in a second terminal (keep the root one open).
#
# Generated secrets (deploy's sudo password, MySQL password) are written to
# /root/pixellap-secrets.txt (chmod 600) and never printed.
# =============================================================================
set -Eeuo pipefail
trap 'echo "✗ provision.sh failed at line $LINENO" >&2' ERR

KIT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
DEPLOY_HOME="/home/${DEPLOY_USER}"
API_DIR=/var/www/pixellap-api
WEB_DIR=/var/www/pixellap-web
LOG_DIR=/var/log/pixellap
BACKUP_DIR=/var/backups/pixellap
ACME_DIR=/var/www/letsencrypt
DOMAIN=ppixelmedia.com
API_DOMAIN=api.ppixelmedia.com
PHP_VERSION=8.3
NODE_MAJOR=22
DB_NAME=pixellap
DB_USER=pixellap
SECRETS=/root/pixellap-secrets.txt
SERVER_HOSTNAME="${SERVER_HOSTNAME:-ppixelmedia}"

step() { printf '\n\033[1;35m==> %s\033[0m\n' "$*"; }
# Appends KEY=value to the secrets file (root only) unless KEY is already there.
secret_set() {
    umask 077
    touch "${SECRETS}"
    chmod 600 "${SECRETS}"
    grep -q "^$1=" "${SECRETS}" || printf '%s=%s\n' "$1" "$2" >> "${SECRETS}"
    umask 022
}
note() { printf '    %s\n' "$*"; }
warn() { printf '\033[1;33m    ! %s\033[0m\n' "$*"; }

[[ ${EUID} -eq 0 ]] || { echo "Run as root: sudo bash $0" >&2; exit 1; }
# shellcheck source=/dev/null
. /etc/os-release
[[ "${VERSION_ID:-}" == "24.04" ]] || warn "Written for Ubuntu 24.04, this is ${PRETTY_NAME:-unknown}."

export DEBIAN_FRONTEND=noninteractive

# -----------------------------------------------------------------------------
step "System packages"
apt-get update -q
# Keep existing config files on upgrades (never prompt).
apt-get full-upgrade -yq -o Dpkg::Options::=--force-confdef -o Dpkg::Options::=--force-confold
apt-get install -yq \
    ca-certificates curl gnupg git unzip rsync acl logrotate cron \
    ufw fail2ban unattended-upgrades \
    nginx certbot python3-certbot-nginx ffmpeg \
    mysql-server \
    "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli" "php${PHP_VERSION}-mysql" "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-xml" "php${PHP_VERSION}-curl" "php${PHP_VERSION}-zip" "php${PHP_VERSION}-gd" \
    "php${PHP_VERSION}-intl" "php${PHP_VERSION}-bcmath" "php${PHP_VERSION}-opcache" "php${PHP_VERSION}-readline" \
    jpegoptim optipng pngquant gifsicle webp

# -----------------------------------------------------------------------------
step "Time zone, hostname, automatic security updates"
timedatectl set-timezone Asia/Dubai
hostnamectl set-hostname "${SERVER_HOSTNAME}"
if grep -q '^127\.0\.1\.1' /etc/hosts; then
    sed -i "s/^127\.0\.1\.1.*/127.0.1.1 ${SERVER_HOSTNAME}/" /etc/hosts
else
    echo "127.0.1.1 ${SERVER_HOSTNAME}" >> /etc/hosts
fi
# Cloud images reset the hostname at boot unless told not to.
if [[ -d /etc/cloud/cloud.cfg.d ]]; then
    printf 'preserve_hostname: true\n' > /etc/cloud/cloud.cfg.d/99-pixellap-hostname.cfg
fi
printf 'APT::Periodic::Update-Package-Lists "1";\nAPT::Periodic::Unattended-Upgrade "1";\nAPT::Periodic::AutocleanInterval "7";\n' \
    > /etc/apt/apt.conf.d/20auto-upgrades
systemctl enable --now unattended-upgrades >/dev/null 2>&1
note "$(hostname), $(date '+%Z'), unattended-upgrades $(systemctl is-active unattended-upgrades)"

# Brotli is optional: the Nginx site configs include its snippet only when the module exists.
if apt-get install -yq libnginx-mod-http-brotli-filter libnginx-mod-http-brotli-static; then
    note "Brotli module installed."
else
    warn "Brotli module not available — gzip only."
fi

# -----------------------------------------------------------------------------
step "Composer"
if ! command -v composer >/dev/null 2>&1; then
    expected="$(curl -fsSL https://composer.github.io/installer.sig)"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    actual="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
    [[ "${expected}" == "${actual}" ]] || { rm -f /tmp/composer-setup.php; echo "Composer installer checksum mismatch" >&2; exit 1; }
    php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi
note "$(composer --version 2>/dev/null | head -n1)"

# -----------------------------------------------------------------------------
step "Node.js ${NODE_MAJOR} LTS (NodeSource) + PM2"
current_node_major="$(node -v 2>/dev/null | sed -E 's/^v([0-9]+).*/\1/' || true)"
if [[ "${current_node_major}" != "${NODE_MAJOR}" ]]; then
    curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" -o /tmp/nodesource_setup.sh
    bash /tmp/nodesource_setup.sh
    rm -f /tmp/nodesource_setup.sh
    apt-get install -yq nodejs
fi
command -v pm2 >/dev/null 2>&1 || npm install -g pm2
note "node $(node -v), pm2 $(pm2 -v 2>/dev/null | tail -n1)"

# -----------------------------------------------------------------------------
step "Swap (2 GB)"
if ! swapon --show=NAME --noheadings | grep -qx /swapfile; then
    [[ -f /swapfile ]] || fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile >/dev/null
    swapon /swapfile
fi
grep -q '^/swapfile ' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
printf 'vm.swappiness=10\nvm.vfs_cache_pressure=50\n' > /etc/sysctl.d/99-pixellap.conf
sysctl -q --system

# -----------------------------------------------------------------------------
step "Deploy user '${DEPLOY_USER}'"
if ! id -u "${DEPLOY_USER}" >/dev/null 2>&1; then
    adduser --disabled-password --gecos "" "${DEPLOY_USER}"
fi
# Full sudo WITH a password (the apps run as this user: an app bug must not become root).
# The few commands the deploy scripts need are passwordless (sudoers/pixellap-deploy).
usermod -aG sudo "${DEPLOY_USER}"
if ! grep -q '^DEPLOY_SUDO_PASSWORD=' "${SECRETS}" 2>/dev/null; then
    deploy_password="$(openssl rand -base64 32 | tr -d '/+=' | cut -c1-24)"
    echo "${DEPLOY_USER}:${deploy_password}" | chpasswd
    secret_set DEPLOY_SUDO_PASSWORD "${deploy_password}"
    note "sudo password for ${DEPLOY_USER} saved in ${SECRETS}."
fi
install -d -m 700 -o "${DEPLOY_USER}" -g "${DEPLOY_USER}" "${DEPLOY_HOME}/.ssh"
# Let the same SSH keys that log in as root log in as the deploy user (first run only).
if [[ -s /root/.ssh/authorized_keys && ! -s "${DEPLOY_HOME}/.ssh/authorized_keys" ]]; then
    install -m 600 -o "${DEPLOY_USER}" -g "${DEPLOY_USER}" /root/.ssh/authorized_keys "${DEPLOY_HOME}/.ssh/authorized_keys"
fi
# Read-only GitHub deploy keys: GitHub allows a key on ONE repository, so one key per repo,
# selected through host aliases in ~/.ssh/config.
for repo in pixellap-api pixellap-web; do
    key="${DEPLOY_HOME}/.ssh/${repo}_ed25519"
    [[ -f "${key}" ]] || sudo -u "${DEPLOY_USER}" ssh-keygen -q -t ed25519 -N "" -C "${repo}@$(hostname)" -f "${key}"
done
cat > "${DEPLOY_HOME}/.ssh/config" <<EOF
# Managed by provision.sh
Host github-pixellap-api
    HostName github.com
    User git
    IdentityFile ~/.ssh/pixellap-api_ed25519
    IdentitiesOnly yes

Host github-pixellap-web
    HostName github.com
    User git
    IdentityFile ~/.ssh/pixellap-web_ed25519
    IdentitiesOnly yes
EOF
chown "${DEPLOY_USER}:${DEPLOY_USER}" "${DEPLOY_HOME}/.ssh/config"
chmod 600 "${DEPLOY_HOME}/.ssh/config"
if ! sudo -u "${DEPLOY_USER}" ssh-keygen -F github.com -f "${DEPLOY_HOME}/.ssh/known_hosts" >/dev/null 2>&1; then
    ssh-keyscan -t ed25519 github.com 2>/dev/null >> "${DEPLOY_HOME}/.ssh/known_hosts"
    chown "${DEPLOY_USER}:${DEPLOY_USER}" "${DEPLOY_HOME}/.ssh/known_hosts"
fi

# -----------------------------------------------------------------------------
step "Folders"
install -d -m 755 -o "${DEPLOY_USER}" -g "${DEPLOY_USER}" "${API_DIR}" "${WEB_DIR}" "${WEB_DIR}/releases" "${WEB_DIR}/shared"
install -d -m 750 -o "${DEPLOY_USER}" -g "${DEPLOY_USER}" "${LOG_DIR}"
install -d -m 700 -o "${DEPLOY_USER}" -g "${DEPLOY_USER}" "${BACKUP_DIR}"
install -d -m 755 "${ACME_DIR}"
# Next.js on this box reaches the API through the local Nginx, not the internet.
grep -qE "^127\.0\.0\.1[[:space:]]+${API_DOMAIN}([[:space:]]|$)" /etc/hosts || echo "127.0.0.1 ${API_DOMAIN}" >> /etc/hosts

# -----------------------------------------------------------------------------
step "MySQL"
cat > /etc/mysql/mysql.conf.d/99-pixellap.cnf <<'EOF'
# Managed by provision.sh — sized for a 4 GB server shared with PHP and Node.
[mysqld]
bind-address = 127.0.0.1
mysqlx = OFF
innodb_buffer_pool_size = 512M
performance_schema = OFF
max_connections = 60
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
EOF
systemctl enable --now mysql
systemctl restart mysql
# mysql_secure_installation equivalent: root only via the local socket, no anonymous users, no test DB.
mysql <<'EOF'
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
EOF
if ! mysql -NBe "SELECT 1 FROM mysql.user WHERE user='${DB_USER}' AND host='localhost'" | grep -q 1; then
    db_password="$(openssl rand -hex 24)"
    mysql <<EOF
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${db_password}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF
    secret_set DB_DATABASE "${DB_NAME}"
    secret_set DB_USERNAME "${DB_USER}"
    secret_set DB_PASSWORD "${db_password}"
    note "Database '${DB_NAME}' and user '${DB_USER}' created. Credentials: ${SECRETS}"
else
    note "MySQL user '${DB_USER}' already exists (credentials in ${SECRETS})."
fi

# -----------------------------------------------------------------------------
step "PHP ${PHP_VERSION}-FPM"
install -m 644 "${KIT_DIR}/php/pixellap.ini" "/etc/php/${PHP_VERSION}/fpm/conf.d/99-pixellap.ini"
sed "s/^user = deploy$/user = ${DEPLOY_USER}/; s/^group = deploy$/group = ${DEPLOY_USER}/" \
    "${KIT_DIR}/php/pool-pixellap.conf" > "/etc/php/${PHP_VERSION}/fpm/pool.d/pixellap.conf"
# The default www pool is not used.
if [[ -f "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf" ]]; then
    mv "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf" "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf.disabled"
fi
"php-fpm${PHP_VERSION}" -t
systemctl enable "php${PHP_VERSION}-fpm"
systemctl restart "php${PHP_VERSION}-fpm"

# -----------------------------------------------------------------------------
step "Queue worker, cron, logrotate, sudoers"
sed "s/^User=deploy$/User=${DEPLOY_USER}/; s/^Group=deploy$/Group=${DEPLOY_USER}/" \
    "${KIT_DIR}/systemd/pixellap-queue.service" > /etc/systemd/system/pixellap-queue.service
systemctl daemon-reload
systemctl enable pixellap-queue >/dev/null
if [[ -f "${API_DIR}/artisan" && -f "${API_DIR}/.env" ]]; then
    systemctl restart pixellap-queue
else
    note "pixellap-queue enabled; it starts after the API is installed (deploy-api.sh)."
fi

sed "s/ deploy / ${DEPLOY_USER} /" "${KIT_DIR}/cron/pixellap" > /etc/cron.d/pixellap
chmod 644 /etc/cron.d/pixellap
sed "s/su deploy deploy/su ${DEPLOY_USER} ${DEPLOY_USER}/" "${KIT_DIR}/logrotate/pixellap" > /etc/logrotate.d/pixellap

sed "s/^deploy /${DEPLOY_USER} /" "${KIT_DIR}/sudoers/pixellap-deploy" > /tmp/pixellap-deploy.sudoers
visudo -cf /tmp/pixellap-deploy.sudoers >/dev/null
install -m 440 /tmp/pixellap-deploy.sudoers /etc/sudoers.d/pixellap-deploy
rm -f /tmp/pixellap-deploy.sudoers

# -----------------------------------------------------------------------------
step "PM2 boot service"
env PATH="${PATH}" pm2 startup systemd -u "${DEPLOY_USER}" --hp "${DEPLOY_HOME}" >/dev/null
note "pm2-${DEPLOY_USER}.service enabled (restores the saved process list at boot)."

# -----------------------------------------------------------------------------
step "Firewall (UFW: 22, 80, 443)"
ufw default deny incoming >/dev/null
ufw default allow outgoing >/dev/null
ufw allow 22/tcp >/dev/null
ufw allow 80/tcp >/dev/null
ufw allow 443/tcp >/dev/null
ufw --force enable >/dev/null
ufw status | sed 's/^/    /'

step "fail2ban (SSH)"
cat > /etc/fail2ban/jail.d/pixellap.local <<'EOF'
# Managed by provision.sh
[sshd]
enabled = true
backend = systemd
maxretry = 5
findtime = 10m
bantime = 1h
EOF
systemctl enable fail2ban >/dev/null
systemctl restart fail2ban

if [[ "${HARDEN_SSH:-0}" == "1" ]]; then
    step "SSH: key-only, no root login"
    # Never lock everyone out: the deploy user must have a key and sudo first.
    [[ -s "${DEPLOY_HOME}/.ssh/authorized_keys" ]] || { echo "No SSH key for ${DEPLOY_USER}: not hardening SSH." >&2; exit 1; }
    id -nG "${DEPLOY_USER}" | grep -qw sudo || { echo "${DEPLOY_USER} is not in the sudo group: not hardening SSH." >&2; exit 1; }
    # 00- on purpose: sshd keeps the FIRST value it reads, and the cloud image's
    # 50-cloud-init.conf sets PasswordAuthentication yes.
    printf '# Managed by provision.sh\nPermitRootLogin no\nPasswordAuthentication no\nKbdInteractiveAuthentication no\nMaxAuthTries 3\n' \
        > /etc/ssh/sshd_config.d/00-pixellap.conf
    rm -f /etc/ssh/sshd_config.d/99-pixellap.conf
    if [[ -f /etc/ssh/sshd_config.d/50-cloud-init.conf ]]; then
        sed -i 's/^PasswordAuthentication yes/PasswordAuthentication no/' /etc/ssh/sshd_config.d/50-cloud-init.conf
    fi
    sshd -t
    systemctl reload ssh
    sshd -T | grep -E '^(permitrootlogin|passwordauthentication|maxauthtries) ' | sed 's/^/    /'
fi

# -----------------------------------------------------------------------------
step "Nginx"
install -m 644 "${KIT_DIR}"/nginx/snippets/pixellap-acme.conf \
    "${KIT_DIR}"/nginx/snippets/pixellap-ssl.conf \
    "${KIT_DIR}"/nginx/snippets/pixellap-gzip.conf \
    "${KIT_DIR}"/nginx/snippets/pixellap-proxy.conf /etc/nginx/snippets/
if compgen -G "/etc/nginx/modules-enabled/*brotli*" >/dev/null; then
    install -m 644 "${KIT_DIR}/nginx/snippets/pixellap-brotli.conf" /etc/nginx/snippets/
else
    rm -f /etc/nginx/snippets/pixellap-brotli.conf
fi
rm -f /etc/nginx/sites-enabled/default
# Catch-all default server: requests by IP or for unknown hosts get nothing (444 / TLS handshake refused).
install -m 644 "${KIT_DIR}/nginx/pixellap-default.conf" /etc/nginx/sites-available/pixellap-default.conf
ln -sfn /etc/nginx/sites-available/pixellap-default.conf /etc/nginx/sites-enabled/pixellap-default.conf

if [[ -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" && -f "/etc/letsencrypt/live/${API_DOMAIN}/fullchain.pem" ]]; then
    for site in "${DOMAIN}.conf" "${API_DOMAIN}.conf"; do
        install -m 644 "${KIT_DIR}/nginx/${site}" "/etc/nginx/sites-available/${site}"
        ln -sfn "/etc/nginx/sites-available/${site}" "/etc/nginx/sites-enabled/${site}"
    done
    rm -f /etc/nginx/sites-enabled/pixellap-acme-bootstrap.conf
    note "HTTPS sites enabled."
    # Renewals: reload Nginx so it serves the new certificate.
    install -d /etc/letsencrypt/renewal-hooks/deploy
    printf '#!/bin/sh\nsystemctl reload nginx\n' > /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
    chmod 755 /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
else
    install -m 644 "${KIT_DIR}/nginx/acme-bootstrap.conf" /etc/nginx/sites-available/pixellap-acme-bootstrap.conf
    ln -sfn /etc/nginx/sites-available/pixellap-acme-bootstrap.conf /etc/nginx/sites-enabled/pixellap-acme-bootstrap.conf
    note "No certificates yet: temporary port-80 site enabled for Let's Encrypt."
fi
nginx -t
systemctl enable nginx >/dev/null
systemctl reload nginx || systemctl restart nginx

# -----------------------------------------------------------------------------
step "Done"
cat <<EOF

    GitHub deploy keys (add each one, read-only, to its repository:
    GitHub → repo → Settings → Deploy keys → Add deploy key):

    pixellap-api:
      $(cat "${DEPLOY_HOME}/.ssh/pixellap-api_ed25519.pub")
    pixellap-web:
      $(cat "${DEPLOY_HOME}/.ssh/pixellap-web_ed25519.pub")

    Next steps: deploy/README.md (clone → .env → certificates → first deploy).
EOF
