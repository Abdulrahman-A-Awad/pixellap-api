# Pixellap — production deploy kit

Everything needed to run **pixellap.com** (website) and **api.pixellap.com** (API + admin) on
**one Ubuntu 24.04 server** (4 GB RAM, 2 vCPU). Follow the steps in order. Commands in grey boxes are
copied and pasted as they are; replace only the parts written `LIKE_THIS`.

```
                    ┌────────────────────── one Ubuntu 24.04 server ──────────────────────┐
 visitors ──443──▶ │ Nginx ─┬─ pixellap.com ────▶ Next.js (PM2, 127.0.0.1:3000)          │
                    │        │   www → redirect          │ server-side calls               │
                    │        └─ api.pixellap.com ──▶ PHP 8.3-FPM (Laravel, /admin) ◀──────┘
                    │                                   │                                 │
                    │   MySQL 8 · queue worker (systemd) · cron (scheduler, backups)       │
                    └──────────────────────────────────────────────────────────────────────┘
```

| What | Where on the server |
|---|---|
| API code + `.env` | `/var/www/pixellap-api` |
| Website releases | `/var/www/pixellap-web/releases/…`, live one = `/var/www/pixellap-web/current` |
| Website settings | `/var/www/pixellap-web/shared/.env.production.local` |
| Logs | `/var/log/pixellap/` (queue, PHP, website, backups) · `/var/www/pixellap-api/storage/logs/` (Laravel) · `/var/log/nginx/` |
| Backups | `/var/backups/pixellap/` (14 days) |
| Database password | `/root/pixellap-mysql.txt` |

The files in this folder:

| File | Installed as |
|---|---|
| `scripts/provision.sh` | run once as root (safe to re-run) — installs and configures everything below |
| `nginx/pixellap.com.conf`, `nginx/api.pixellap.com.conf`, `nginx/snippets/*` | `/etc/nginx/sites-available/…`, `/etc/nginx/snippets/…` |
| `php/pixellap.ini`, `php/pool-pixellap.conf` | `/etc/php/8.3/fpm/conf.d/99-pixellap.ini`, `/etc/php/8.3/fpm/pool.d/pixellap.conf` |
| `systemd/pixellap-queue.service` | `/etc/systemd/system/pixellap-queue.service` |
| `cron/pixellap` | `/etc/cron.d/pixellap` (Laravel scheduler every minute + nightly backup) |
| `logrotate/pixellap`, `sudoers/pixellap-deploy` | `/etc/logrotate.d/…`, `/etc/sudoers.d/…` |
| `scripts/deploy-api.sh`, `scripts/deploy-web.sh` | run as `deploy` for every release |
| `scripts/backup.sh` | run nightly by cron |

---

## 1. Buy the server

1. Any VPS provider (Hetzner, DigitalOcean, Vultr, Contabo…). Choose **Ubuntu 24.04 LTS**, **4 GB RAM / 2 vCPU**,
   a region close to the UAE (e.g. Frankfurt, Bahrain, Mumbai, Dubai if offered).
2. When creating it, add **your SSH public key** (on your PC: `type %USERPROFILE%\.ssh\id_ed25519.pub`;
   if you have none, run `ssh-keygen -t ed25519` first).
3. Write down the server's **IPv4 address** (called `SERVER_IP` below).
4. Check you can log in: `ssh root@SERVER_IP`.

## 2. DNS records

At the company that manages the `pixellap.com` domain, create:

| Type | Name | Value | TTL |
|---|---|---|---|
| A | `@` | `SERVER_IP` | 300 |
| A | `www` | `SERVER_IP` | 300 |
| A | `api` | `SERVER_IP` | 300 |

- Delete any old A/AAAA/CNAME records for `@`, `www` and `api` that point elsewhere.
- Only add AAAA (IPv6) records if the server really has IPv6 — a wrong AAAA makes the certificates fail.
- Keep the existing MX/TXT records (email).
- Wait until all three names answer with your IP (usually minutes): on your PC run
  `nslookup pixellap.com`, `nslookup www.pixellap.com`, `nslookup api.pixellap.com`.

## 3. Provision the server (once)

From your PC, inside the `pixellap-api` folder, copy this kit to the server and run it:

```bash
scp -r deploy root@SERVER_IP:/root/pixellap-deploy
ssh root@SERVER_IP
bash /root/pixellap-deploy/scripts/provision.sh
```

It takes 5–10 minutes and installs: Nginx (+ brotli if available), PHP 8.3-FPM with all extensions (intl, gd…),
MySQL 8 (database `pixellap` + user, password saved in `/root/pixellap-mysql.txt`), Composer, Node.js 22 LTS + PM2,
certbot, git, UFW (ports 22, 80, 443 only), fail2ban, a 2 GB swap file, the `deploy` user (same SSH keys as root),
the queue worker service, cron jobs, log rotation and a temporary web page for Let's Encrypt.

At the end it prints **two GitHub deploy keys**. Add each to its repository (read-only):
GitHub → repository → **Settings → Deploy keys → Add deploy key** → paste → *Allow write access* **off**.

- `pixellap-api_ed25519.pub` → repository `pixellap-api`
- `pixellap-web_ed25519.pub` → repository `pixellap-web`

(If both repositories are public you can skip the keys and clone with the `https://` URLs instead.)

## 4. Clone both repositories

Still logged in as root, switch to the deploy user (all app commands from now on run as `deploy`):

```bash
sudo -iu deploy
git clone git@github-pixellap-api:Abdulrahman-A-Awad/pixellap-api.git /var/www/pixellap-api
git clone git@github-pixellap-web:Abdulrahman-A-Awad/pixellap-web.git /var/www/pixellap-web/repo
exit
```

`github-pixellap-api` / `github-pixellap-web` are not typos: they pick the right deploy key (see `~/.ssh/config`).

## 5. HTTPS certificates (Let's Encrypt)

As root (replace the email — Let's Encrypt writes there before a certificate expires):

```bash
certbot certonly --webroot -w /var/www/letsencrypt --cert-name pixellap.com \
  -d pixellap.com -d www.pixellap.com -m YOU@pixellap.com --agree-tos --no-eff-email
certbot certonly --webroot -w /var/www/letsencrypt --cert-name api.pixellap.com \
  -d api.pixellap.com -m YOU@pixellap.com --agree-tos --no-eff-email

# Run provision again: it now sees the certificates and switches Nginx to the real HTTPS sites.
bash /var/www/pixellap-api/deploy/scripts/provision.sh

# Renewal is automatic (certbot.timer); prove it works:
certbot renew --dry-run
```

## 6. Settings files (.env)

Create two random secrets and keep this terminal open — both apps need the **same** values:

```bash
openssl rand -hex 32   # → SITE_KEY
openssl rand -hex 32   # → REVALIDATE_SECRET
cat /root/pixellap-mysql.txt   # → database password
```

**API** (as deploy):

```bash
sudo -iu deploy
cd /var/www/pixellap-api
cp .env.production.example .env
chmod 600 .env
nano .env
```

Fill every `CHANGE_ME`: `DB_PASSWORD`, `MAIL_*` (your SMTP provider), `REVALIDATE_SECRET`, `API_SITE_KEY`,
`CONTACT_NOTIFY_EMAIL`. Save (Ctrl+O, Enter, Ctrl+X). Then:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
php artisan key:generate --force
```

**Website** (as deploy):

```bash
cp /var/www/pixellap-web/repo/.env.production.example /var/www/pixellap-web/shared/.env.production.local
chmod 600 /var/www/pixellap-web/shared/.env.production.local
nano /var/www/pixellap-web/shared/.env.production.local   # same API_SITE_KEY and REVALIDATE_SECRET
```

> Keep a copy of both `.env` files (especially `APP_KEY`) in a password manager. Backups do not include them.

## 7. First deploy

As deploy, in this order:

```bash
# 1. API: install, migrate, and seed the starting content (roles, permissions, pages…).
#    --seed is for THIS FIRST deploy only (it would overwrite edited content later).
#    "The website was not refreshed" at the end is expected: the website is not running yet.
bash /var/www/pixellap-api/deploy/scripts/deploy-api.sh --seed

# 2. Your admin account (production never gets the default admin@pixellap.test).
cd /var/www/pixellap-api && php artisan pixellap:create-super-admin

# 3. Website: builds from the API (a few minutes), starts it under PM2.
bash /var/www/pixellap-api/deploy/scripts/deploy-web.sh
```

Then sign in at **https://api.pixellap.com/admin**, replace every `TODO:` setting (Settings page) and enter
the real content. Finally:

```bash
cd /var/www/pixellap-api && php artisan pixellap:check-production
```

It must end with **"Production checks passed."** — otherwise it lists exactly what is left
(debug on, short secrets, default admin, `TODO:` settings, missing storage link or queue tables).

## 8. Smoke test (after the first deploy and after big releases)

- [ ] `https://pixellap.com` opens (EN), `https://pixellap.com/ar` opens right-to-left.
- [ ] `http://pixellap.com`, `https://www.pixellap.com` and `http://www.pixellap.com` all end on `https://pixellap.com`.
- [ ] Services, one category page, About and Contact open in both languages; images load.
- [ ] The 3D hero shows on a desktop browser; the showreel opens and plays.
- [ ] `https://pixellap.com/sitemap.xml` and `/robots.txt` load and use `https://pixellap.com` URLs.
- [ ] Contact form: send a message → "sent" message on the page, the email arrives, the lead appears in the admin (bell icon).
- [ ] Admin: edit a short text (e.g. a FAQ), save → the website shows it within ~10 seconds.
- [ ] `https://api.pixellap.com/admin` shows the login page; `http://api.pixellap.com/admin` redirects to HTTPS.
- [ ] `https://api.pixellap.com/api/v1/site` answers **401** in a browser (the API needs the site key).
- [ ] Headers check: <https://securityheaders.com/?q=pixellap.com> shows grade A.
- [ ] TLS check: <https://www.ssllabs.com/ssltest/analyze.html?d=pixellap.com> shows A or A+.
- [ ] `sudo systemctl status pixellap-queue` → active; `pm2 status` → `pixellap-web` online.
- [ ] Next morning: `ls -lh /var/backups/pixellap` shows last night's two backup files.

## 9. Everyday deploys

After pushing to `main` on GitHub, log in and run the script of the app that changed (as deploy):

```bash
ssh deploy@SERVER_IP
bash /var/www/pixellap-api/deploy/scripts/deploy-api.sh    # API / admin changes
bash /var/www/pixellap-api/deploy/scripts/deploy-web.sh    # website changes
```

API changes that add new UI strings to `database/seeders/data/ui_strings.php` also need
`php artisan db:seed --class=UiStringSeeder --force && php artisan pixellap:revalidate-all`
(this resets edited UI strings to the seeded copy — only the UI strings, nothing else).

### Optional: deploy from GitHub Actions

Each repository has `.github/workflows/deploy.yml`, **manual only** for now (GitHub → Actions → Deploy →
Run workflow).

1. On your PC create a key just for GitHub: `ssh-keygen -t ed25519 -N "" -f pixellap-actions`
2. Put the public half on the server: append `pixellap-actions.pub` to `/home/deploy/.ssh/authorized_keys`.
3. In **both** repositories → Settings → Secrets and variables → Actions, add: `HOST` = `SERVER_IP`,
   `USER` = `deploy`, `KEY` = the full content of the private file `pixellap-actions`.
4. Run the workflow once by hand. To deploy automatically on every push to `main`, uncomment the `push:`
   lines at the top of `deploy.yml`.

## 10. Roll back

**Website** (switches back to the previous build, takes seconds):

```bash
bash /var/www/pixellap-api/deploy/scripts/deploy-web.sh --rollback
```

The last 3 builds are kept in `/var/www/pixellap-web/releases`.

**API** (back to the commit that was live before the last deploy):

```bash
bash /var/www/pixellap-api/deploy/scripts/deploy-api.sh --rollback
```

Database migrations are not undone automatically. If the bad release added a migration, first run
`php artisan migrate:rollback --step=1 --force` (inside `/var/www/pixellap-api`) **before** the rollback,
and only if that migration does not drop data you need.

A rollback is temporary: the next normal deploy brings `main` back, so fix or revert the commit on GitHub.

## 11. Backups and restore

Every night at 02:30 `scripts/backup.sh` writes to `/var/backups/pixellap/`:
`db-YYYYMMDD-HHMMSS.sql.gz` (whole database) and `storage-YYYYMMDD-HHMMSS.tar.gz` (all uploaded media).
They are kept 14 days. Run one by hand at any time: `bash /var/www/pixellap-api/deploy/scripts/backup.sh`.

**Copy them off the server** — a backup on the same disk does not survive a lost server. Simplest: once a
week from your PC: `scp -r deploy@SERVER_IP:/var/backups/pixellap ./pixellap-backups`
(or enable your VPS provider's snapshot/backup option, or sync with `rclone` to cloud storage).

### Restore

As deploy, choose the files to restore (`ls -lh /var/backups/pixellap`), then:

```bash
cd /var/www/pixellap-api
php artisan down                        # admin shows "maintenance"; the website keeps its cached pages
bash deploy/scripts/backup.sh           # safety copy of the current state first

# Database (replaces every table with the backup's content)
DB_PASS="$(grep '^DB_PASSWORD=' .env | cut -d= -f2-)"
gunzip -c /var/backups/pixellap/db-YYYYMMDD-HHMMSS.sql.gz | MYSQL_PWD="$DB_PASS" mysql -u pixellap pixellap

# Uploaded media
mv storage/app/public "storage/app/public.before-restore-$(date +%s)"
tar -xzf /var/backups/pixellap/storage-YYYYMMDD-HHMMSS.tar.gz -C storage/app

php artisan up
php artisan optimize:clear && php artisan optimize && php artisan filament:optimize
php artisan pixellap:revalidate-all
```

Check the website and the admin, then delete the `public.before-restore-*` folder.

**Test a restore once before launch** (and every few months): restore last night's backup into a scratch
database and count the rows:

```bash
sudo mysql -e "CREATE DATABASE pixellap_restore_test"
gunzip -c /var/backups/pixellap/db-LATEST.sql.gz | sudo mysql pixellap_restore_test
sudo mysql -e "SELECT COUNT(*) FROM pixellap_restore_test.services; DROP DATABASE pixellap_restore_test"
tar -tzf /var/backups/pixellap/storage-LATEST.tar.gz | head
```

## 12. Useful commands

| Task | Command |
|---|---|
| Website status / logs | `pm2 status` · `pm2 logs pixellap-web` · `tail -f /var/log/pixellap/web.error.log` |
| Restart the website | `pm2 reload pixellap-web` |
| Laravel errors | `tail -f /var/www/pixellap-api/storage/logs/laravel-$(date +%F).log` |
| Queue worker | `sudo systemctl status pixellap-queue` · `tail -f /var/log/pixellap/queue.log` |
| Failed jobs | `cd /var/www/pixellap-api && php artisan queue:failed` · `php artisan queue:retry all` |
| Refresh the whole website | `php artisan pixellap:revalidate-all` (or Admin → Dashboard → Refresh website cache) |
| Production check | `php artisan pixellap:check-production` |
| Nginx | `sudo nginx -t` · `tail -f /var/log/nginx/pixellap.com.error.log` |
| Changed `.env` of the API | `bash deploy/scripts/deploy-api.sh` (re-caches the config) |
| Changed the website `.env` | `bash deploy/scripts/deploy-web.sh` (values are baked into the build) |
| Firewall / bans | `sudo ufw status` · `sudo fail2ban-client status sshd` |

**PHP code changes need a PHP-FPM reload** (OPcache never re-reads files): the deploy script does it; after
a manual edit run `sudo systemctl reload php8.3-fpm`.
