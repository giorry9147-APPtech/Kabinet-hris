# HR-portaal Kabinet President Republiek Suriname

HR Information System voor het **Kabinet van de President van de Republiek Suriname**.

Hoogste functie binnen het Kabinet is de **Kabinetschef / Directeur Kabinet (Chief of Staff)** — momenteel **Sergio Akiemboto**.

> Gekloond op 2026-05-11 vanuit MAS-HRIS als snelle fork. Eerste klant: Kabinet van de President.
> Latere ombouw naar multi-tenant SaaS (waarbij MAS, MRO en Kabinet binnen één codebase draaien) staat in `../MAS-HRIS/docs/sprints/sprint-06-multi-tenant-saas.md`.

## Stack

- **Backend:** Laravel 12 + PHP 8.3 + PostgreSQL 17
- **Auth & RBAC:** Sanctum + spatie/laravel-permission
- **Audit:** spatie/laravel-activitylog (automatisch op alle modellen)
- **Files:** spatie/laravel-medialibrary
- **Admin UI:** Filament 3 (`/admin`)
- **Self-service portal:** Next.js 16 + React 19 + Tailwind 4 + TanStack Query (`localhost:3000`)
- **Branding:** Kabinet-groen (`#1F5E3A`) + goud (`#D4A017`)

## Wat is anders dan MAS-HRIS

- **Branding:** groen + goud i.p.v. navy + crimson; "Kabinet van de President" wordmark i.p.v. MAS
- **Org-structuur:** Bureau van de President / Vicepresident / Communicatie / Protocol / Juridische Zaken / Beleid / Algemene Zaken (HR/Fin/ICT/Facilitair) / Beveiliging — geen maritieme directies meer
- **Demo-data:** 12 placeholder-medewerkers met Kabinet-functies (Kabinetschef, Adviseurs, Persvoorlichter, Protocol, Beveiligers, etc.)
- **Geen STCW/IMO/Loods certificaten** in seed-data (worden niet automatisch verwijderd uit `certificate_types`-tabel — gewoon ongebruikt voor Kabinet)
- **Geen `vessel-rosters` / watchkeeping** features (irrelevant voor Kabinet)
- **Tailwind classes `mas-blue` / `mas-red`** zijn **bewust behouden** maar de hex-waarden zijn herdefinieerd in `frontend/src/app/globals.css` — bij latere SaaS-refactor worden ze hernoemd naar generieke `--color-primary-*` / `--color-accent-*`

## Lokaal draaien

### Eenmalig

PostgreSQL 17 moet draaien. Maak een lege DB aan:

```powershell
psql -U postgres -c "CREATE DATABASE kabinet_hris;"
```

Backend dependencies:
```powershell
cd backend
composer install
php artisan key:generate
```

Pas `backend/.env` aan zodat `DB_DATABASE=kabinet_hris` (en username/password van je lokale Postgres). Zet ook `DB_CONNECTION=pgsql` (standaard staat 'ie nog op sqlite uit de example).

Frontend dependencies:
```powershell
cd frontend
npm install
```

### Backend opstarten

```powershell
cd backend
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
```

- Admin panel: <http://127.0.0.1:8000/admin>
- API: <http://127.0.0.1:8000/api>

**Login:** `admin@kabinet.sr` / `kabinet-admin-2026` (aanpasbaar via `ADMIN_EMAIL` / `ADMIN_PASSWORD` in `.env`)

### Frontend opstarten

```powershell
cd frontend
npm run dev
```

Open <http://127.0.0.1:3000>.

### Database resetten + opnieuw seeden

```powershell
cd backend
php artisan migrate:fresh --seed
```

---

## Demo-accounts

Alle medewerker-accounts: wachtwoord `kabinet-demo-2026`.

| Email | Naam | Functie | Rol |
|---|---|---|---|
| `admin@kabinet.sr` | Kabinet Administrator | — | super_admin (wachtwoord: `kabinet-admin-2026`) |
| `sergio.akiemboto@kabinet.sr` | Sergio Akiemboto | Kabinetschef / Directeur Kabinet (Chief of Staff) | super_admin |
| `soraya.doelawat@kabinet.sr` | Soraya Doelawat | Hoofd Juridische Zaken | dept_head |
| `anand.pawiroredjo@kabinet.sr` | Anand Pawiroredjo | Hoofd Communicatie | dept_head |
| `marlinde.nelson@kabinet.sr` | Marlinde Nelson | Hoofd Protocol | dept_head |
| `kenrick.sapoen@kabinet.sr` | Kenrick Sapoen | Hoofd Personeelszaken | hr_manager |
| `rianne.vreden@kabinet.sr` | Rianne Vreden | Hoofd Financiën | finance |
| `tarun.doerga@kabinet.sr` | Tarun Doerga | Hoofd ICT | employee |
| `lakshmi.algoe@kabinet.sr` | Lakshmi Algoe | HR-medewerker | employee |
| `miguel.codrington@kabinet.sr` | Miguel Codrington | Persvoorlichter | employee |
| `farah.mahabier@kabinet.sr` | Farah Mahabier | Senior Beleidsadviseur | employee |

Daarnaast 2 medewerkers zonder login (alleen Employee-records voor organogram): Quincy Boldewijn (Hoofd Beveiliging), Devika Tjon-A-Joe (Protocolmedewerker).

---

## Logo vervangen

De huidige `kabinetlogo.svg` (in zowel `backend/public/` als `frontend/public/`) is een **placeholder** — rood/goud schild met "KP"-monogram. Vervang met het echte Kabinet-logo:

1. Bewaar het echte logo als `kabinetlogo.svg` (of `.png` ≥512×512)
2. Plak het in **beide** locaties: `backend/public/kabinetlogo.svg` en `frontend/public/kabinetlogo.svg`
3. Als het bestandsformaat verandert (bv. `.png` i.p.v. `.svg`), update dan:
   - `backend/app/Providers/Filament/AdminPanelProvider.php` → `brandLogo(asset('...'))` + `favicon(asset('...'))`
   - `frontend/src/app/page.tsx` → `<Image src="/kabinetlogo.svg" ...>`

---

## Volgende stappen

- Echte medewerker-lijst importeren (zie MAS-HRIS Sprint 7 importer-pattern)
- E-mail notificaties (SMTP-config van het Kabinet)
- Op termijn: integratie in multi-tenant SaaS (MAS-HRIS sprint 6 plan)

---

## Cloud deploy (later)

Volg de MAS-HRIS deploy-handleiding (`../MAS-HRIS/docs/DEPLOY-DEMO.md`) maar vervang:
- Fly-app naam → `kabinet-hris-backend`
- Vercel-projectnaam → `kabinet-hris`
- Storage volume → `kabinet_storage` (al aangepast in `fly.toml`)
- Postgres-DB → eigen Fly Postgres cluster
- ADMIN_EMAIL/PASSWORD → eigen Kabinet-waarden via `fly secrets set`
