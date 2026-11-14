#!/usr/bin/env bash
# =============================================================================
# Deploy the Next.js website (run as the deploy user, never root).
#
#   bash deploy/scripts/deploy-web.sh             # pull main, build a new release, switch to it
#   bash deploy/scripts/deploy-web.sh --rollback  # switch back to the previous release (seconds)
#
# Layout (/var/www/pixellap-web):
#   repo/        git checkout (only used to export releases)
#   releases/    one folder per build: 20261114-153000-a1b2c3d (the last 3 are kept)
#   shared/      .env.production.local (linked into every release)
#   current ->   releases/<live build>   (PM2 runs from here)
#
# The new build happens next to the live one: if npm ci / the build fails, nothing changes.
# If the new release does not answer after the switch, the previous one is restored.
# =============================================================================
set -Eeuo pipefail
trap 'echo "✗ deploy-web failed at line $LINENO." >&2' ERR

WEB_DIR="${WEB_DIR:-/var/www/pixellap-web}"
API_DIR="${API_DIR:-/var/www/pixellap-api}"
BRANCH="${BRANCH:-main}"
KEEP_RELEASES="${KEEP_RELEASES:-3}"
HEALTH_URL="${HEALTH_URL:-http://127.0.0.1:3000/en}"
LOCK_FILE="${LOCK_FILE:-/tmp/pixellap-deploy.lock}"
REPO="${WEB_DIR}/repo"
RELEASES="${WEB_DIR}/releases"
SHARED="${WEB_DIR}/shared"
CURRENT="${WEB_DIR}/current"
ENV_FILE="${SHARED}/.env.production.local"

ROLLBACK=0
for arg in "$@"; do
    case "${arg}" in
        --rollback) ROLLBACK=1 ;;
        -h | --help) sed -n '2,17p' "$0"; exit 0 ;;
        *) echo "Unknown option: ${arg}" >&2; exit 2 ;;
    esac
done

step() { printf '\n\033[1;35m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m! %s\033[0m\n' "$*" >&2; }

[[ ${EUID} -ne 0 ]] || { echo "Run as the deploy user, not root." >&2; exit 1; }

exec 9>"${LOCK_FILE}"
flock -w 1800 9 || { echo "Another deploy is still running." >&2; exit 1; }

# Atomically point `current` at a release folder.
switch_to() {
    ln -sfn "$1" "${WEB_DIR}/current.next"
    mv -Tf "${WEB_DIR}/current.next" "${CURRENT}"
}

reload_pm2() {
    pm2 startOrReload "${CURRENT}/ecosystem.config.cjs" --update-env >/dev/null
    pm2 save >/dev/null
}

healthy() {
    # Up to ~60 s for Next.js to start answering.
    local _
    for _ in $(seq 1 30); do
        if curl -fsS -o /dev/null --max-time 10 "${HEALTH_URL}"; then
            return 0
        fi
        sleep 2
    done
    return 1
}

revalidate() {
    step "Refreshing cached pages"
    if ! (cd "${API_DIR}" && php artisan pixellap:revalidate-all --no-interaction); then
        warn "Revalidation failed — pages refresh within the hour anyway. Retry: php ${API_DIR}/artisan pixellap:revalidate-all"
    fi
}

# Sorted release folders, oldest first.
list_releases() {
    find "${RELEASES}" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | sort
}

live="$(readlink -f "${CURRENT}" 2>/dev/null || true)"

if [[ ${ROLLBACK} -eq 1 ]]; then
    [[ -n "${live}" ]] || { echo "Nothing is deployed yet." >&2; exit 1; }
    previous=""
    while IFS= read -r name; do
        [[ "${RELEASES}/${name}" == "${live}" ]] && break
        previous="${RELEASES}/${name}"
    done < <(list_releases)
    [[ -n "${previous}" ]] || { echo "No older release to roll back to." >&2; exit 1; }
    step "Rolling back to $(basename "${previous}")"
    switch_to "${previous}"
    reload_pm2
    healthy || { echo "The previous release does not answer either — check: pm2 logs pixellap-web" >&2; exit 1; }
    revalidate
    printf '\n\033[1;32m✓ Rolled back to %s\033[0m\n' "$(basename "${previous}")"
    exit 0
fi

[[ -f "${ENV_FILE}" ]] || { echo "${ENV_FILE} is missing (copy .env.production.example)." >&2; exit 1; }

step "Pulling ${BRANCH}"
git -C "${REPO}" checkout --quiet "${BRANCH}"
git -C "${REPO}" pull --ff-only --quiet origin "${BRANCH}"
rev="$(git -C "${REPO}" rev-parse --short HEAD)"
release="${RELEASES}/$(date -u +%Y%m%d-%H%M%S)-${rev}"
echo "Building $(git -C "${REPO}" log -1 --format='%h %s')"

# A failed build must not leave a half-built folder behind.
cleanup_failed_release() { rm -rf "${release}"; }
trap 'echo "✗ deploy-web failed at line $LINENO — the live site was not changed." >&2; cleanup_failed_release' ERR

step "Exporting release $(basename "${release}")"
mkdir -p "${release}"
git -C "${REPO}" archive --format=tar HEAD | tar -x -C "${release}"
ln -s "${ENV_FILE}" "${release}/.env.production.local"
# Reuse the optimized-image cache (not the data cache: pages are rebuilt from the API).
if [[ -n "${live}" && -d "${live}/.next/cache/images" ]]; then
    mkdir -p "${release}/.next/cache"
    cp -a "${live}/.next/cache/images" "${release}/.next/cache/"
fi

step "npm ci"
cd "${release}"
npm ci --no-audit --no-fund --loglevel=error

step "npm run build (prerenders pages from the API)"
NODE_OPTIONS="--max-old-space-size=2048" NEXT_TELEMETRY_DISABLED=1 npm run build

trap 'echo "✗ deploy-web failed at line $LINENO." >&2' ERR

step "Switching to the new release"
switch_to "${release}"
reload_pm2

if ! healthy; then
    warn "The new release does not answer on ${HEALTH_URL}."
    if [[ -n "${live}" ]]; then
        warn "Restoring $(basename "${live}")."
        switch_to "${live}"
        reload_pm2
    fi
    exit 1
fi

revalidate

step "Removing old releases (keeping ${KEEP_RELEASES})"
mapfile -t all < <(list_releases)
excess=$(( ${#all[@]} - KEEP_RELEASES ))
for (( i = 0; i < excess; i++ )); do
    old="${RELEASES}/${all[i]}"
    [[ "${old}" == "$(readlink -f "${CURRENT}")" ]] && continue
    rm -rf "${old}"
    echo "removed ${all[i]}"
done

printf '\n\033[1;32m✓ Website deployed: %s\033[0m\n' "$(basename "${release}")"
