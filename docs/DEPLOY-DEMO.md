# MAS HRIS — Demo deployment runbook

Doel: een gratis, publiek bereikbare showcase-versie. Geen echte personeelsdata, alleen de demo-seeders.

**Stack**

| Onderdeel | Host | Plan | URL na deploy |
|---|---|---|---|
| Next.js portal | Vercel | Hobby (gratis) | `https://<projectname>.vercel.app` |
| Laravel + Filament | Fly.io | Free `shared-cpu-1x` | `https://<appname>.fly.dev` |
| PostgreSQL 17 | Fly Postgres | Free dev cluster | intern via `DATABASE_URL` |
| File uploads | container fs | ephemeral (re-seed bij deploy) | – |
| Mail | `log` driver | – | `storage/logs/laravel.log` |

**Auth-model**: Bearer tokens via `localStorage`. Geen cross-site cookies, dus CORS-config is simpel.

---

## 0. Prereqs (eenmalig op je laptop)

```pwsh
# Fly CLI
iwr https://fly.io/install.ps1 -useb | iex
fly version

# Vercel CLI
npm i -g vercel
vercel --version
```

> **Docker is NIET nodig** — `fly deploy` heeft een `--remote-only` modus die het image bouwt op Fly's builders. Mocht je Docker Desktop wel hebben, dan kun je optioneel lokaal vooraf testen (zie 1.2).

Accounts:
- Fly: <https://fly.io/app/sign-up> — vraagt om creditcard maar de free tier is gratis bij netjes onder de limieten blijven
- Vercel: <https://vercel.com/signup> — login via GitHub of email

---

## 1. Backend → Fly.io

### 1.1 Sign in

```pwsh
fly auth login
```

### 1.2 Image lokaal testen (alleen als je Docker Desktop hebt)

Vanuit `backend/`:

```pwsh
cd c:\Users\31621\Desktop\MAS-HRIS\backend
docker build -t mas-hris-backend:dev .
```

Snelle smoke-test (gebruikt SQLite in /tmp omdat we lokaal geen Postgres draaien):

```pwsh
docker run --rm -p 8080:8080 `
  -e APP_KEY=base64:dummybuildonlykeydummybuildonlykey1234 `
  -e DB_CONNECTION=sqlite `
  -e DB_DATABASE=/tmp/test.sqlite `
  -e CACHE_STORE=array `
  -e SESSION_DRIVER=array `
  -e QUEUE_CONNECTION=sync `
  mas-hris-backend:dev
```

Open `http://localhost:8080/up` → moet 200 OK met JSON geven.

Heb je geen Docker, sla dit gewoon over en gebruik `fly deploy --remote-only` in stap 1.6.

### 1.3 App aanmaken op Fly

Vanuit `backend/`:

```pwsh
fly launch --no-deploy --copy-config --name mas-hris-backend --region ams
```

- `--no-deploy` = nu nog niet deployen, eerst secrets en DB setten
- `--copy-config` = bestaande `fly.toml` gebruiken
- Region `ams` (Amsterdam) — pas eventueel aan in `fly.toml`

Als de naam `mas-hris-backend` al bezet is, kies een andere naam en update `app = "..."` in `fly.toml`.

### 1.4 Postgres aanmaken + koppelen

```pwsh
fly postgres create --name mas-hris-db --region ams --vm-size shared-cpu-1x --volume-size 1 --initial-cluster-size 1
fly postgres attach mas-hris-db --app mas-hris-backend
```

`attach` zet automatisch `DATABASE_URL` als secret op de backend-app. `entrypoint.sh` aliassed dat naar `DB_URL` voor Laravel.

### 1.5 Secrets zetten

Genereer een fresh `APP_KEY` (lokaal):

```pwsh
cd c:\Users\31621\Desktop\MAS-HRIS\backend
php artisan key:generate --show
# kopieer de output, bv. base64:abc123...==
```

Set alle secrets in één keer:

```pwsh
fly secrets set --app mas-hris-backend `
  APP_KEY="base64:DIE-HIERBOVEN-GEGENEREERDE-KEY" `
  APP_URL="https://mas-hris-backend.fly.dev" `
  FRONTEND_URL="https://mas-hris.vercel.app" `
  CORS_ALLOWED_ORIGINS="https://mas-hris.vercel.app" `
  SANCTUM_STATEFUL_DOMAINS="mas-hris.vercel.app" `
  ADMIN_EMAIL="admin@mas.sr" `
  ADMIN_PASSWORD="mas-admin-2026"
```

> Vercel-URL is nu nog een gok — pas in stap 2.4 aan met de échte URL na de eerste Vercel-deploy.

### 1.6 Deployen

Met Docker Desktop lokaal:
```pwsh
fly deploy --app mas-hris-backend
```

Zonder Docker (gebruikt Fly's remote builders):
```pwsh
fly deploy --app mas-hris-backend --remote-only
```

Eerste deploy duurt 5-8 min (composer install + image push). Bij succes:

```
✓ Machine started
✓ App deployed
```

Check de logs:

```pwsh
fly logs --app mas-hris-backend
```

Je zou moeten zien:
- `[entrypoint] Waiting for Postgres ...`
- `[entrypoint] Running migrations ...`
- `[entrypoint] Empty database detected — seeding demo data ...`
- `nginx ... start worker process`

### 1.7 Smoke-tests

```pwsh
# Health check
curl https://mas-hris-backend.fly.dev/up

# Filament admin (browser)
start https://mas-hris-backend.fly.dev/admin
# Login met ADMIN_EMAIL / ADMIN_PASSWORD

# API smoke
curl -X POST https://mas-hris-backend.fly.dev/api/auth/login `
  -H "Content-Type: application/json" `
  -d '{\"email\":\"marciano@mas.sr\",\"password\":\"mas-demo-2026\",\"device_name\":\"smoke\"}'
```

---

## 2. Frontend → Vercel

### 2.1 Sign in en project linken

Vanuit `frontend/`:

```pwsh
cd c:\Users\31621\Desktop\MAS-HRIS\frontend
vercel login
vercel link
```

- "Set up and deploy?" → **No** (we zetten eerst env vars)
- "Link to existing project?" → **No**
- Project name → `mas-hris` (of wat je wilt — bepaalt de URL `<naam>.vercel.app`)
- Directory → `.`
- Override settings? → **No**

### 2.2 Env var voor de API-URL

```pwsh
vercel env add NEXT_PUBLIC_API_URL production
# plak: https://mas-hris-backend.fly.dev/api
```

Of via dashboard: Project → Settings → Environment Variables.

### 2.3 Production deploy

```pwsh
vercel deploy --prod
```

URL komt aan het eind, bv. `https://mas-hris.vercel.app`.

### 2.4 Backend CORS bijwerken met échte Vercel-URL

Als de Vercel-URL afwijkt van wat je in 1.5 raadde:

```pwsh
fly secrets set --app mas-hris-backend `
  CORS_ALLOWED_ORIGINS="https://mas-hris.vercel.app" `
  FRONTEND_URL="https://mas-hris.vercel.app" `
  SANCTUM_STATEFUL_DOMAINS="mas-hris.vercel.app"
```

Setten van secrets triggert auto-redeploy.

### 2.5 End-to-end test

1. Open `https://mas-hris.vercel.app`
2. Login als `marciano@mas.sr` / `mas-demo-2026`
3. Klik door dossier / verlof / certs / salaris / assets
4. Open DevTools → Network → check dat requests naar `mas-hris-backend.fly.dev/api/*` een 200 geven en CORS-headers correct zijn

---

## 3. Demo-accounts

Zelfde als lokaal — `DemoEmployeeSeeder` zaait deze in:

| Email | Wachtwoord | Rol |
|---|---|---|
| `admin@mas.sr` | (wat je in `ADMIN_PASSWORD` zette) | super_admin → Filament |
| `roy@mas.sr` … `saskia@mas.sr` | `mas-demo-2026` | hr_manager / hr_admin / dept_head / finance |
| `marciano@mas.sr`, `sandra@mas.sr`, `glenn@mas.sr` | `mas-demo-2026` | employee → portal |

---

## 4. Onderhoud & demo-reset

**Logs livevolgen:**
```pwsh
fly logs --app mas-hris-backend
```

**Shell in container:**
```pwsh
fly ssh console --app mas-hris-backend
# /var/www/html # php artisan tinker
```

**Database resetten (volledige re-seed van demo):**
```pwsh
fly ssh console --app mas-hris-backend -C "php /var/www/html/artisan migrate:fresh --seed --force"
```

**Machine forceren te wakker worden** (auto-stop staat aan):
```pwsh
curl https://mas-hris-backend.fly.dev/up
```
Eerste request na slaap duurt 5-15s, daarna instant.

**Frontend her-deployen na code-change:**
```pwsh
cd frontend
vercel deploy --prod
```

**Backend her-deployen:**
```pwsh
cd backend
fly deploy --app mas-hris-backend
```

---

## 5. Bekende valkuilen

- **Eerste Fly-deploy faalt op DB-connectie**: postgres-attach soms 30s vertraagd. Run `fly deploy` opnieuw.
- **CORS-error in browser**: check dat `CORS_ALLOWED_ORIGINS` exact de Vercel-URL is incl. `https://`, geen trailing slash.
- **`tinker --execute` lekt geen output**: entrypoint-seed kan stilletjes overslaan. Check met `fly ssh console -C "php artisan tinker --execute='echo App\\\\Models\\\\User::count();'"`.
- **Filament admin leeg na deploy**: betekent dat seeders niet gerund zijn. Forceer met `fly ssh console -C "php /var/www/html/artisan db:seed --force"`.
- **File uploads verdwijnen na deploy**: container-fs is ephemeral. Voor persistentie: zet `FILESYSTEM_DISK=s3` + Cloudflare R2 secrets (zie `.env.production.example`).
- **Auto-stop vertraagt eerste request**: zet `min_machines_running = 1` in `fly.toml` als je het altijd warm wil houden (kost wel iets).

---

## 6. Wat NIET in deze demo zit

- Custom domain (`hris.mas.sr`) — kan in 5 min toegevoegd worden via `fly certs add` + `vercel domains add` + DNS.
- Echte mailverzending — `MAIL_MAILER=log`. Voor mail-flow demo: SendGrid/Postmark/Resend free tier (Sprint 7).
- Off-site DB-backup. Voor demo OK; voor productie: zie sprint-10.
- 2FA, AVG-tools, audit-export — sprint-10.
