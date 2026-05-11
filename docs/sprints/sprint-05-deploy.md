# Sprint 5 — Productie-deployment

> **Doel**: MAS HRIS draait op een server waar HR vanuit hun browser bij kan, met SSL, automatische backups en queue worker.
>
> **Effort**: 1-2 dagen (eens hosting-provider gekozen).
>
> **Blocker**: HR/directie moet kiezen — cloud-provider + datasoevereiniteit.

## Vragen vóóraf

- **Cloud of on-prem?** Parastatale = mag data buiten Suriname? Anders een server op kantoor MAS Paramaribo (vereist eigen VPN voor remote toegang).
- **Welke provider?** Aanbevolen: Hetzner Cloud (€5/maand, EU) of DigitalOcean (Frankfurt, $6/maand). AWS/GCP overkill voor deze schaal.
- **Domein**: hris.mas.sr? Subdomain via MAS DNS-beheer regelen.
- **E-mail**: zie [sprint-07-notifications-mobile.md](sprint-07-notifications-mobile.md).

## Architectuur

```
[Internet]
    ↓
[Nginx :443 SSL]
    ├── /            → Next.js portal (port 3000, pm2)
    ├── /admin       → Laravel Filament (php-fpm)
    ├── /api         → Laravel API (php-fpm)
    └── /storage     → public files (medialibrary)
    
[PostgreSQL]   [Redis (queue + cache)]   [S3-compatible storage]
```

Of simpler — alles op één VPS (4GB RAM voldoende voor MAS-omvang):
- Ubuntu 22.04 LTS
- PostgreSQL 17 lokaal
- Redis voor queue + cache
- Nginx + PHP-FPM 8.3
- Node 20+ voor Next.js (via pm2)
- Lokale `storage/` voor uploads (eerst), later naar S3 als het groeit

## Tasks

### A. Server setup
- [ ] VPS aanmaken bij gekozen provider
- [ ] DNS A-record `hris.mas.sr` → server-IP
- [ ] SSH-keys configureren, root login uitschakelen
- [ ] Ubuntu firewall: alleen 22 (SSH), 80, 443 open
- [ ] Fail2ban installeren

### B. Software stack
- [ ] PHP 8.3 + extensies (pdo_pgsql, gd, exif, intl, mbstring, zip)
- [ ] Composer 2.x
- [ ] PostgreSQL 17 — `mas_hris` DB + dedicated user
- [ ] Node 20 + pnpm of npm
- [ ] Nginx + Let's Encrypt (certbot)
- [ ] Supervisor of systemd voor queue worker

### C. App deployment
- [ ] Git clone naar `/var/www/mas-hris/`
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm ci && npm run build` (frontend)
- [ ] `.env.production` aanmaken (zie sectie hieronder)
- [ ] `php artisan migrate --force`
- [ ] `php artisan db:seed --class=RolePermissionSeeder` (rollen)
- [ ] `php artisan db:seed --class=SuperAdminSeeder` (1 admin met sterk wachtwoord)
- [ ] `php artisan db:seed --class=SalaryGradeSeeder` (overheidsschalen)
- [ ] `php artisan storage:link`
- [ ] `php artisan optimize` (config + route cache)
- [ ] `php artisan filament:assets` (Filament assets publiceren)

### D. Nginx config
- [ ] `/etc/nginx/sites-available/mas-hris.conf`:
  - `server { listen 443 ssl; server_name hris.mas.sr; ... }`
  - `/api` en `/admin` → PHP-FPM via `try_files $uri /index.php?$query_string`
  - `/` → proxy_pass naar `127.0.0.1:3000` (Next.js)
  - `/storage` → static files met cache headers
- [ ] Let's Encrypt: `certbot --nginx -d hris.mas.sr`
- [ ] HTTP → HTTPS redirect

### E. Background processes
- [ ] **Queue worker** als systemd service:
  ```ini
  [Service]
  ExecStart=/usr/bin/php /var/www/mas-hris/backend/artisan queue:work --queue=default --tries=3 --timeout=120
  Restart=always
  User=www-data
  ```
- [ ] **Schedule** via cron:
  ```
  * * * * * cd /var/www/mas-hris/backend && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] **Next.js** via pm2 of systemd:
  ```
  pm2 start "npm start" --name mas-frontend --cwd /var/www/mas-hris/frontend
  pm2 save && pm2 startup
  ```

### F. Backups
- [ ] **Database**: dagelijkse `pg_dump`:
  ```bash
  #!/bin/bash
  DATE=$(date +%F)
  pg_dump -U mas_hris mas_hris | gzip > /backups/mas_hris_$DATE.sql.gz
  find /backups -mtime +30 -delete  # 30 dagen retentie
  ```
  Cron: `0 2 * * * /usr/local/bin/backup-db.sh`
- [ ] **Off-site**: rsync naar tweede locatie of upload naar S3-bucket (Backblaze B2 is goedkoop)
- [ ] **Media files**: `tar -czf media-$DATE.tar.gz storage/app/public/media` + off-site
- [ ] **Test**: 1× per kwartaal restore-procedure uitvoeren op staging

### G. .env.production sample

```env
APP_NAME="MAS HRIS"
APP_ENV=production
APP_KEY=<generate via php artisan key:generate>
APP_DEBUG=false
APP_URL=https://hris.mas.sr
APP_FRONTEND_URL=https://hris.mas.sr

APP_LOCALE=nl
APP_TIMEZONE=America/Paramaribo

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=mas_hris
DB_USERNAME=mas_hris
DB_PASSWORD=<sterk wachtwoord>

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

FILESYSTEM_DISK=local   # of "s3" als je S3 gebruikt

MAIL_MAILER=smtp
MAIL_HOST=<provider, bv. smtp.sendgrid.net>
MAIL_PORT=587
MAIL_USERNAME=<api key user>
MAIL_PASSWORD=<api key>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hr-noreply@mas.sr
MAIL_FROM_NAME="MAS HRIS"

SANCTUM_STATEFUL_DOMAINS=hris.mas.sr

ADMIN_EMAIL=<echte HR-manager email>
ADMIN_PASSWORD=<sterk wachtwoord — daarna direct via UI wijzigen>

MAS_LEAVE_YEARLY_DAYS=24
```

## Acceptance criteria

- [ ] `https://hris.mas.sr/admin` opent met SSL-slot, login werkt, dashboard laadt
- [ ] `https://hris.mas.sr/` laat MAS-portal zien
- [ ] `php artisan certs:check-expiry` draait via cron en stuurt notificaties
- [ ] Verlofaanvraag → goedkeuring werkt end-to-end (incl. queue worker actief)
- [ ] DB-backup is gisteren-nacht gemaakt en staat ook off-site
- [ ] 5× refresh in 5 sec geeft geen 500-errors

## Bekende valkuilen

- **`storage/` permissies**: `chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache`
- **`.env` mag nooit in git**: `chmod 600 .env`
- **Queue worker vergeten te starten**: notificaties komen niet aan, niemand klaagt totdat een cert verloopt
- **`php artisan migrate` zonder `--force` in prod**: weigert te draaien
- **Filament assets** worden bij elke composer-update overschreven — herpubliceren met `php artisan filament:upgrade`

## Wat NIET in deze sprint

- Geen e-mail templates polishen → [sprint-07](sprint-07-notifications-mobile.md)
- Geen 2FA → [sprint-10](sprint-10-security-compliance.md)
- Geen S3 setup als lokale storage volstaat (< 50GB) — kan later
