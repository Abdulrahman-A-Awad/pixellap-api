#!/usr/bin/env bash
# =============================================================================
# Deploy the Laravel API + admin (run as the deploy user, never root).
#
#   bash deploy/scripts/deploy-api.sh             # pull main and deploy
#   bash deploy/scripts/deploy-api.sh --seed      # ...and run db:seed (first deploy)
#   bash deploy/scripts/deploy-api.sh --rollback  # back to the commit before the last deploy
#
# WARNING: --seed rewrites seeded content (services, pages, FAQs, UI strings...) with
# the copy in database/seeders/data — use it for the FIRST deploy only. To add new UI
# strings later: php artisan db:seed --class=UiStringSeeder --force (then revalidate-all).
#
# Any failing step aborts the deploy (set -e). The website keeps running meanwhile.
# =============================================================================
set -Eeuo pipefail
trap 'echo "✗ deploy-api failed at line $LINENO — nothing after this step ran." >&2' ERR

API_DIR="${API_DIR:-/var/www/pixellap-api}"
BRANCH="${BRANCH:-main}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"
LOCK_FILE="${LOCK_FILE:-/tmp/pixellap-deploy.lock}"
STATE_FILE="${API_DIR}/storage/app/deploy-previous-commit"

SEED=0
ROLLBACK=0
for arg in "$@"; do
    case "${arg}" in
        --seed) SEED=1 ;;
        --rollback) ROLLBACK=1 ;;
        -h | --help) sed -n '2,15p' "$0"; exit 0 ;;
        *) echo "Unknown option: ${arg}" >&2; exit 2 ;;
    esac
done

step() { printf '\n\033[1;35m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m! %s\033[0m\n' "$*" >&2; }
artisan() { php artisan "$@" --no-interaction; }

[[ ${EUID} -ne 0 ]] || { echo "Run as the deploy user, not root." >&2; exit 1; }

# One deploy at a time (shared with deploy-web.sh): wait up to 30 minutes.
exec 9>"${LOCK_FILE}"
flock -w 1800 9 || { echo "Another deploy is still running." >&2; exit 1; }

cd "${API_DIR}"
[[ -f .env ]] || { echo "${API_DIR}/.env is missing (copy .env.production.example)." >&2; exit 1; }

if [[ ${ROLLBACK} -eq 1 ]]; then
    [[ -s "${STATE_FILE}" ]] || { echo "No previous deploy recorded." >&2; exit 1; }
    target="$(cat "${STATE_FILE}")"
    step "Rolling back to ${target:0:12} (database migrations are NOT rolled back)"
    git reset --hard --quiet "${target}"
else
    step "Pulling ${BRANCH}"
    before="$(git rev-parse HEAD)"
    git checkout --quiet "${BRANCH}"
    git pull --ff-only --quiet origin "${BRANCH}"
    # Remember where we came from (for --rollback) only when something changed.
    [[ "$(git rev-parse HEAD)" == "${before}" ]] || echo "${before}" > "${STATE_FILE}"
fi
echo "Now at $(git log -1 --format='%h %s')"

step "Composer (no dev, optimized autoloader)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress

step "Caches (config, events, routes, views, Filament)"
artisan optimize
artisan filament:optimize

if [[ ${ROLLBACK} -eq 0 ]]; then
    step "Migrations"
    artisan migrate --force
fi

if [[ ${SEED} -eq 1 ]]; then
    step "Seeding (production: no default admin is created)"
    artisan db:seed --force
fi

[[ -L public/storage ]] || artisan storage:link

step "Reloading PHP-FPM (OPcache) and the queue worker"
sudo -n systemctl reload "${PHP_FPM_SERVICE}"
artisan queue:restart

step "Production checks"
if ! artisan pixellap:check-production; then
    warn "The installation is not production-ready yet (see the list above). The deploy itself succeeded."
fi

step "Refreshing the website"
if ! artisan pixellap:revalidate-all; then
    warn "The website was not refreshed (is pixellap-web running?). Run later: php artisan pixellap:revalidate-all"
fi

printf '\n\033[1;32m✓ API deployed: %s\033[0m\n' "$(git log -1 --format='%h %s')"
