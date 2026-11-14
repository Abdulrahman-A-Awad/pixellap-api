#!/usr/bin/env bash
# =============================================================================
# Nightly backup (cron, as the deploy user — see deploy/cron/pixellap):
#   - MySQL dump of the API database (gzip)
#   - storage/app/public (every uploaded image / video) as a tarball
# Kept 14 days in /var/backups/pixellap. Copy them OFF the server too (README).
#
#   bash deploy/scripts/backup.sh
# =============================================================================
set -Eeuo pipefail
trap 'echo "✗ backup failed at line $LINENO" >&2' ERR
umask 077

API_DIR="${API_DIR:-/var/www/pixellap-api}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/pixellap}"
KEEP_DAYS="${KEEP_DAYS:-14}"

# Read one KEY=value from the API .env (quotes stripped; values are never echoed).
env_value() {
    local line
    line="$(grep -E "^$1=" "${API_DIR}/.env" | tail -n1 || true)"
    line="${line#*=}"
    line="${line%\"}"
    line="${line#\"}"
    printf '%s' "${line}"
}

db_name="$(env_value DB_DATABASE)"
[[ -n "${db_name}" ]] || { echo "DB_DATABASE not found in ${API_DIR}/.env" >&2; exit 1; }

# Credentials go through a private option file, never the command line (visible in `ps`).
defaults="$(mktemp)"
trap 'rm -f "${defaults}"' EXIT
{
    echo "[client]"
    echo "user=$(env_value DB_USERNAME)"
    echo "password=\"$(env_value DB_PASSWORD)\""
    echo "host=$(env_value DB_HOST)"
    echo "port=$(env_value DB_PORT)"
} > "${defaults}"

mkdir -p "${BACKUP_DIR}"
stamp="$(date +%Y%m%d-%H%M%S)"
db_file="${BACKUP_DIR}/db-${stamp}.sql.gz"
media_file="${BACKUP_DIR}/storage-${stamp}.tar.gz"

echo "$(date '+%F %T') backup started"

mysqldump --defaults-extra-file="${defaults}" \
    --single-transaction --quick --routines --triggers --no-tablespaces \
    --default-character-set=utf8mb4 "${db_name}" | gzip -6 > "${db_file}.part"
gzip -t "${db_file}.part"
mv "${db_file}.part" "${db_file}"

tar -czf "${media_file}.part" -C "${API_DIR}/storage/app" public
gzip -t "${media_file}.part"
mv "${media_file}.part" "${media_file}"

# Retention: delete backups older than KEEP_DAYS and any leftover partial file.
find "${BACKUP_DIR}" -maxdepth 1 -type f \( -name 'db-*.sql.gz' -o -name 'storage-*.tar.gz' \) -mtime "+$(( KEEP_DAYS - 1 ))" -delete
find "${BACKUP_DIR}" -maxdepth 1 -type f -name '*.part' -mmin +60 -delete

echo "$(date '+%F %T') backup done: $(du -h "${db_file}" | cut -f1) database, $(du -h "${media_file}" | cut -f1) media"
