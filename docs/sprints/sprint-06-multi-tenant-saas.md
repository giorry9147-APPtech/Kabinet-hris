# Sprint 6 — Multi-Tenant SaaS Conversion

> **Doel:** MAS-HRIS ombouwen naar een multi-tenant SaaS-platform zodat één codebase meerdere klanten bedient (MAS, MRO, Kabinet van de President, en toekomstige bedrijven), elk met eigen database, eigen branding en eigen subdomein.
>
> **Trigger:** Gebruiker wil een tweede systeem voor het Kabinet van de President. Tweemaal handmatig clonen + onderhouden schaalt niet — daarom nu de fundering leggen.
>
> **Effort:** 4-6 dagen (1 dev fulltime). Eenmalige investering — daarna kost een nieuwe klant <5 minuten onboarden.

---

## 1. Acceptatiecriteria

Sprint is af wanneer alle onderstaande punten kloppen:

- [ ] `php artisan tenants:create <slug> --domain=<domain>` provisioneert in één commando: nieuwe tenant-DB, alle 28 migrations gedraaid, eerste admin-user aangemaakt, branding-defaults gezet.
- [ ] Drie tenants draaien live op de demo-omgeving: `mas-hris.vercel.app` (MAS), `mro-hris.vercel.app` (MRO), `kabinet-hris.vercel.app` (Kabinet).
- [ ] Backend draait op één Fly-app met centrale DB + drie tenant-DB's; tenant-DB switch gebeurt automatisch op basis van `X-Tenant`-header van de frontend.
- [ ] Filament admin-panel (`/admin`) toont per tenant z'n eigen logo, kleuren, ministerienaam, ministernaam — gelezen uit `tenants.settings` JSON-kolom.
- [ ] Next.js portal fetcht branding bij eerste paint via `GET /api/branding` en past CSS-vars `--color-primary` / `--color-accent` dynamisch toe.
- [ ] Super-admin panel op `/super` (op centrale DB) laat een super-user tenants aanmaken, branding bijwerken, logo uploaden, tenants suspenderen.
- [ ] Data-isolatie verifieerbaar: een ingelogde MAS-admin kan geen Kabinet-medewerkers zien, ook niet door direct in de URL te knoeien (`/admin/employees/123` van andere tenant geeft 404).
- [ ] Bestaande MAS-demo-data is gemigreerd naar `tenant_mas` DB; oude `mas-hris-backend.fly.dev/admin` blijft werken (nu als MAS-tenant).
- [ ] Geen regressies in bestaande Sprint 1-5 features: verlofaanvragen, certificaten, organogram, exports, audit-log werken nog steeds in elke tenant.

---

## 2. Architectuur

```
┌──────────────────────────────────────────────────────────────────┐
│                         Fly.io region: ams                       │
│                                                                  │
│  ┌──────────────────────┐      ┌────────────────────────────┐   │
│  │  Fly app             │      │  Fly Postgres cluster      │   │
│  │  hris-saas-backend   │─────►│  hris-saas-db              │   │
│  │  (Laravel + Filament)│      │                            │   │
│  │                      │      │  ├─ central_db             │   │
│  │  Routes:             │      │  │   • tenants             │   │
│  │   /super  → central  │      │  │   • domains             │   │
│  │   /admin  → tenant   │      │  │   • central_users       │   │
│  │   /api/*  → tenant   │      │  │   • jobs, failed_jobs   │   │
│  │                      │      │  │                         │   │
│  │  Tenancy bootstrap   │      │  ├─ tenant_mas             │   │
│  │  via X-Tenant header │      │  │   • alle 28 HRIS tabs   │   │
│  │  + InitializeTenancy │      │  ├─ tenant_mro             │   │
│  │    ByRequestData     │      │  │   • alle 28 HRIS tabs   │   │
│  └──────────────────────┘      │  └─ tenant_kabinet         │   │
│                                │      • alle 28 HRIS tabs   │   │
│                                └────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
                            ▲
                            │ HTTPS + X-Tenant: <slug>
                            │
┌───────────────────────────┴──────────────────────────────────────┐
│                          Vercel                                  │
│                                                                  │
│  Project: mas-hris        Project: mro-hris    Project: kabinet  │
│  ENV: TENANT=mas          ENV: TENANT=mro      ENV: TENANT=kabin │
│  Domain: mas-hris.        Domain: mro-hris.    Domain: kabinet-  │
│   vercel.app                vercel.app           hris.vercel.app │
│                                                                  │
│  (Alle 3 importeren uit dezelfde Git-repo, alleen env-vars       │
│   verschillen. Build-command identiek.)                          │
└──────────────────────────────────────────────────────────────────┘
```

**Keuzes onderbouwd:**

- **DB-per-tenant** boven shared-DB-met-`tenant_id` → datasoevereiniteit per overheidsklant uitlegbaar ("uw data, uw database"), per-tenant backup/restore/delete triviaal, geen risico op cross-tenant-leaks door vergeten `where('tenant_id', ...)`.
- **Eén Fly-app, één Postgres-cluster** → goedkoop (één machine, één DB-server) en de tenant-switch gebeurt in code, niet in infrastructuur.
- **Drie Vercel-projecten uit één repo** → workaround voor Vercel's "1 gratis `*.vercel.app` per project". Bij go-live met echt domein (`hris.sr` met wildcard cert) wordt dit één project met `*.hris.sr` en subdomein-based routing.
- **`X-Tenant`-header** boven host-based identification → werkt ongeacht of frontend op `vercel.app`-subdomein of custom domein draait; geen Fly-side wildcard-cert gedoe.

---

## 3. Taken — fase voor fase

### 6.1 — Stancl/Tenancy installeren + central/tenant migrations splitsen (0.5 dag)

```bash
cd backend
composer require stancl/tenancy
php artisan tenancy:install
```

Dit genereert `config/tenancy.php`, een `TenancyServiceProvider`, en migrations voor `tenants` + `domains` tabellen in de centrale DB.

**Belangrijke config-aanpassingen in `config/tenancy.php`:**

```php
'tenant_model' => \App\Models\Tenant::class,

'identification_middleware' => [
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class,
],

'bootstrappers' => [
    \Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
    \Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
    \Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
    \Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
],

'database' => [
    'central_connection' => 'central',
    'template_tenant_connection' => null,
    'prefix' => 'tenant_',
    'suffix' => '',
    'managers' => [
        'pgsql' => \Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
    ],
],

'migration_parameters' => [
    '--path' => [database_path('migrations/tenant')],
    '--realpath' => true,
],
```

**`config/database.php`** krijgt een `central`-connectie naast `pgsql`:

```php
'central' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST'),
    'database' => env('DB_CENTRAL_DATABASE', 'central'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    // ... rest identiek aan pgsql connectie
],
```

**Migrations splitsen:**

- **Blijven in `database/migrations/`** (centrale DB):
  - `0001_01_01_000000_create_users_table.php` — wordt `central_users` (super-admins die tenants beheren)
  - `0001_01_01_000001_create_cache_table.php`
  - `0001_01_01_000002_create_jobs_table.php`
  - Stancl's `tenants` + `domains` migrations (auto-aangemaakt)

- **Verhuizen naar `database/migrations/tenant/`** (per-tenant DB):
  - Alle 25 overige migrations (org_units, positions, employees, certificates, etc.)
  - Inclusief `personal_access_tokens` (Sanctum), `permissions`/`roles` (spatie), `activity_log`, `media`, `notifications`, `imports`/`exports`
  - Plus een nieuw `users` migration specifiek voor tenant-DB (medewerker-logins per tenant)

**Tenant-model `app/Models/Tenant.php`:**

```php
class Tenant extends \Stancl\Tenancy\Database\Models\Tenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'slug', 'settings', 'is_active'];
    }

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];
}
```

Migration die de extra kolommen toevoegt aan `tenants`:

```php
Schema::table('tenants', function (Blueprint $t) {
    $t->string('name')->after('id');
    $t->string('slug')->unique()->after('name');
    $t->jsonb('settings')->nullable()->after('slug');
    $t->boolean('is_active')->default(true)->after('settings');
});
```

---

### 6.2 — Filament dual-panel: `/super` (central) + `/admin` (tenant) (1 dag)

**Huidige `AdminPanelProvider.php`** wordt opgesplitst in twee providers:

**`app/Providers/Filament/SuperPanelProvider.php`** (nieuwe centrale super-admin panel):

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->id('super')
        ->path('super')
        ->brandName('HRIS SaaS — Super Admin')
        ->colors(['primary' => Color::Slate])
        ->authGuard('central')
        ->discoverResources(
            in: app_path('Filament/Super/Resources'),
            for: 'App\\Filament\\Super\\Resources'
        )
        ->middleware([/* default Filament middleware */])
        ->authMiddleware([Authenticate::class]);
}
```

Resources in `app/Filament/Super/Resources/`:
- `TenantResource` — lijst van alle tenants, create-form (slug + naam + domein), edit-form met branding-fields (logo upload, kleuren, ministernaam, tagline), action "Suspend tenant", action "Run migrations", action "Seed demo data".

**`app/Providers/Filament/AdminPanelProvider.php`** (bestaande tenant-panel, lichte aanpassing):

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('admin')
        ->brandName(fn () => tenant('settings.name') ?? 'HRIS')
        ->colors(fn () => [
            'primary' => Color::hex(tenant('settings.color_primary') ?? '#173E7A'),
            'danger' => Color::hex(tenant('settings.color_accent') ?? '#E30613'),
        ])
        ->brandLogo(fn () => tenant() ? Storage::disk('public')->url(tenant('settings.logo_path')) : null)
        ->middleware([
            // Default Filament middleware...
            InitializeTenancyByRequestData::class,
            PreventAccessFromCentralDomains::class,
        ])
        ->authMiddleware([Authenticate::class]);
}
```

**Belangrijk:** Filament boot-time closures voor `brandName`/`colors`/`brandLogo` worden per request geëvalueerd, dus de `tenant()` helper geeft de juiste waardes nadat de tenancy-middleware z'n werk heeft gedaan.

**`bootstrap/providers.php`** krijgt beide providers:

```php
return [
    App\Providers\Filament\SuperPanelProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    // ...
];
```

---

### 6.3 — Per-tenant branding-systeem (0.5 dag)

**`tenants.settings` JSON-schema:**

```json
{
    "name": "Ministerie van Regionale Ontwikkeling",
    "short_name": "MRO",
    "minister": "Miquella Huur BSc.",
    "tagline": "Samen bouwen aan de regio",
    "color_primary": "#1F5E3A",
    "color_accent": "#D4A017",
    "logo_path": "tenant-logos/mro/logo.png",
    "favicon_path": "tenant-logos/mro/favicon.ico",
    "locale": "nl",
    "timezone": "America/Paramaribo"
}
```

**Logo-opslag:** Filament `FileUpload` veld in TenantResource → bestand komt in `storage/app/public/tenant-logos/{slug}/logo.png`. Door `php artisan storage:link` (al gedaan in Sprint 5) is dit publiekelijk bereikbaar op `https://hris-saas-backend.fly.dev/storage/tenant-logos/{slug}/logo.png`.

**Branding-API endpoint** voor Next.js:

```php
// routes/api.php (tenant scope)
Route::get('/branding', function () {
    return response()->json([
        'name' => tenant('settings.name'),
        'short_name' => tenant('settings.short_name'),
        'minister' => tenant('settings.minister'),
        'tagline' => tenant('settings.tagline'),
        'color_primary' => tenant('settings.color_primary'),
        'color_accent' => tenant('settings.color_accent'),
        'logo_url' => tenant('settings.logo_path')
            ? Storage::disk('public')->url(tenant('settings.logo_path'))
            : null,
    ]);
})->middleware(InitializeTenancyByRequestData::class);
```

---

### 6.4 — Next.js tenant-aware maken (1 dag)

**`.env.local` per Vercel-project:**

```
NEXT_PUBLIC_TENANT=kabinet
NEXT_PUBLIC_API_BASE=https://hris-saas-backend.fly.dev
```

**API-client** (`frontend/src/lib/api.ts`) krijgt `X-Tenant`-header automatisch:

```typescript
export async function apiFetch(path: string, init: RequestInit = {}) {
    const headers = new Headers(init.headers);
    headers.set('X-Tenant', process.env.NEXT_PUBLIC_TENANT!);
    headers.set('Accept', 'application/json');
    if (token) headers.set('Authorization', `Bearer ${token}`);
    return fetch(`${process.env.NEXT_PUBLIC_API_BASE}/api${path}`, { ...init, headers });
}
```

**Branding-provider** (`frontend/src/app/providers/BrandingProvider.tsx`):

```tsx
export function BrandingProvider({ children }: { children: ReactNode }) {
    const [branding, setBranding] = useState<Branding | null>(null);

    useEffect(() => {
        apiFetch('/branding').then(r => r.json()).then(setBranding);
    }, []);

    useEffect(() => {
        if (!branding) return;
        document.documentElement.style.setProperty('--color-primary', branding.color_primary);
        document.documentElement.style.setProperty('--color-accent', branding.color_accent);
        document.title = `${branding.short_name} — HRIS`;
    }, [branding]);

    return <BrandingContext.Provider value={branding}>{children}</BrandingContext.Provider>;
}
```

**Tailwind config** (`frontend/tailwind.config.ts`) gebruikt CSS-vars:

```ts
extend: {
    colors: {
        primary: 'var(--color-primary)',
        accent: 'var(--color-accent)',
    },
}
```

Dashboard-header gebruikt `branding.name`, `branding.minister`, `branding.logo_url` in plaats van hardcoded MAS-waardes.

---

### 6.5 — Onboarding-command (0.5 dag)

`app/Console/Commands/TenantsCreate.php`:

```php
public function handle()
{
    $slug = $this->argument('slug');
    $name = $this->option('name') ?? $this->ask('Volledige organisatienaam?');
    $adminEmail = $this->option('admin-email') ?? $this->ask('Email super-admin?');
    $adminPassword = Str::random(16);

    $tenant = Tenant::create([
        'id' => $slug,
        'name' => $name,
        'slug' => $slug,
        'settings' => [
            'name' => $name,
            'short_name' => Str::upper($slug),
            'color_primary' => '#173E7A',
            'color_accent' => '#E30613',
            'locale' => 'nl',
            'timezone' => 'America/Paramaribo',
        ],
        'is_active' => true,
    ]);

    // Migrations draaien automatisch via Stancl event listener
    // OF: $tenant->run(fn () => Artisan::call('migrate', ['--path' => 'database/migrations/tenant']));

    $tenant->run(function () use ($adminEmail, $adminPassword) {
        $user = User::create([
            'name' => 'Admin',
            'email' => $adminEmail,
            'password' => Hash::make($adminPassword),
        ]);
        $user->assignRole('super_admin');
    });

    $this->info("✓ Tenant '{$slug}' aangemaakt");
    $this->info("  Login: {$adminEmail}");
    $this->info("  Wachtwoord: {$adminPassword}");
    $this->warn("  Sla dit wachtwoord nu op — wordt niet opnieuw getoond.");
}
```

Gebruik:

```bash
php artisan tenants:create kabinet \
    --name="Kabinet van de President" \
    --admin-email="admin@kabinet.sr"
```

---

### 6.6 — Deploy + seed de 3 tenants (1 dag)

**Stappen:**

1. Lokaal: alle code uit 6.1-6.5 werkt, MAS-tenant draait, login werkt, branding klopt.
2. Fly-secrets aanpassen: `DB_CENTRAL_DATABASE=central` toevoegen, `fly secrets set`.
3. Fly-app deploy: `fly deploy`. Entrypoint moet `php artisan migrate --database=central --force` doen vóór tenant-migrations.
4. Op productie:
   ```bash
   fly ssh console -a hris-saas-backend
   php artisan tenants:create mas --name="Maritieme Autoriteit Suriname" --admin-email="admin@mas.sr"
   php artisan tenants:create mro --name="Ministerie van Regionale Ontwikkeling" --admin-email="admin@mro.sr"
   php artisan tenants:create kabinet --name="Kabinet van de President" --admin-email="admin@kabinet.sr"
   ```
5. Voor MAS: bestaande Sprint 1-4 demo-data importeren naar `tenant_mas`-DB via aangepaste seeder die in tenant-context draait.
6. Vercel: 3 projecten aanmaken uit dezelfde repo, elk met eigen `NEXT_PUBLIC_TENANT` env-var.
7. Super-admin panel: logo's uploaden voor elke tenant, kleuren bijstellen, ministernamen invullen.

---

## 4. Codebase-impact — wat verandert er fysiek

| Locatie | Wijziging |
|---|---|
| `composer.json` | + `stancl/tenancy` |
| `config/tenancy.php` | nieuw |
| `config/database.php` | + `central` connectie |
| `database/migrations/tenant/` | nieuwe map, ~25 migrations verhuisd |
| `database/migrations/` | alleen central tabellen blijven |
| `app/Models/Tenant.php` | nieuw |
| `app/Models/CentralUser.php` | nieuw (super-admins) |
| `app/Providers/Filament/SuperPanelProvider.php` | nieuw |
| `app/Providers/Filament/AdminPanelProvider.php` | aangepast: dynamische branding |
| `app/Filament/Super/Resources/TenantResource.php` | nieuw |
| `app/Console/Commands/TenantsCreate.php` | nieuw |
| `routes/api.php` | + `/branding` endpoint, tenancy middleware op `/me/*` routes |
| `bootstrap/providers.php` | + SuperPanelProvider, + TenancyServiceProvider |
| `Dockerfile` / `entrypoint.sh` | central migrations toevoegen vóór tenant-migrations |
| `frontend/src/lib/api.ts` | + `X-Tenant` header |
| `frontend/src/app/providers/BrandingProvider.tsx` | nieuw |
| `frontend/tailwind.config.ts` | kleuren naar CSS-vars |
| `frontend/.env.example` | + `NEXT_PUBLIC_TENANT` |

---

## 5. Risico's & valkuilen

- **Filament panel-boot timing:** `tenant()` is pas beschikbaar ná tenancy-middleware. Closures voor `brandName`/`colors` werken; statische waardes niet. Test altijd via een echt request, niet in `tinker`.
- **Sanctum tokens:** standaard `personal_access_tokens`-tabel zit per tenant. Een token van MAS werkt niet voor Kabinet (goed). Maar: super-admin login (op central guard) heeft eigen token-strategie nodig.
- **Queue jobs:** met `QueueTenancyBootstrapper` weet elke job in welke tenant-context het hoort. Vergeet bij `dispatch()` niet dat `tenant()` op dat moment moet kloppen.
- **Storage `php artisan storage:link`** maakt één symlink — `FilesystemTenancyBootstrapper` zorgt dat tenant-specifieke paden onder `storage/app/public/tenant{id}/...` komen. Verifieer dat logo's daadwerkelijk per tenant gescheiden zijn.
- **`spatie/laravel-permission`** cachet permissions. Cache moet per tenant gescheiden — `CacheTenancyBootstrapper` doet dit, maar test expliciet dat MRO-rollen niet doorlekken naar Kabinet.
- **Data-migratie van bestaande MAS-data:** alle 28 huidige tabellen zitten nu in de single Fly Postgres DB. Bij omschakeling moet die DB worden hernoemd naar `tenant_mas` of de data via `pg_dump` naar de nieuwe `tenant_mas`-DB worden gekopieerd. **Backup vooraf!**
- **Vercel free-tier limit:** max 3 projecten per Git-repo onder gratis plan? Verifieer voordat we 4e tenant onboarden.

---

## 6. Vragen die beantwoord moeten worden vóór start

- **Naming:** moet de Fly-app hernoemd worden van `mas-hris-backend` naar iets neutraals (`hris-saas-backend`)? Of mag de domeinnaam blijven en alleen intern de codebase generiek worden?
- **Super-admin accounts:** wie krijgt toegang tot `/super`? Alleen jij, of ook iemand van Suriname IT?
- **Per-tenant feature toggles:** moet bv. de Maritime-uitbreiding (Sprint 10) per tenant aan/uit kunnen? Zo ja, voeg `enabled_modules` JSON-array toe aan `tenants.settings`.
- **Branding-fallback:** als een tenant geen logo heeft geüpload, wat tonen we? Default placeholder of organisatie-initialen in een gekleurd vlak?
- **Kabinet-data:** is er al ergens een organogram/functielijst van het Kabinet? Of wordt dat handmatig opgebouwd na onboarding?
- **MRO-data:** zelfde vraag. De screenshots laten 5 directoraten / 83 afdelingen / 30 functies zien — waar komen die vandaan?

---

## 7. Wat ná Sprint 6 makkelijker wordt

- **Sprint 7 (real-data-import):** importer wordt automatisch per-tenant. MAS importeert MAS-medewerkers in z'n eigen DB; Kabinet importeert in z'n eigen DB.
- **Sprint 8 (e-mail):** SMTP-credentials kunnen per tenant verschillen — zet ze in `tenants.settings.mail`.
- **Sprint 9 (nieuwe modules):** modules kunnen per tenant aan/uit via feature-flag in settings.
- **Sprint 10 (maritime extensions):** alleen aanzetten voor MAS-tenant, niet voor MRO/Kabinet.
- **Sprint 11 (security/compliance):** 2FA, audit-trails, retention-policies werken al gescheiden per tenant — geen extra werk om "alleen MAS hoeft AVG-tools" te ondersteunen.

---

## 8. Definition of Done

- [ ] Alle code op een feature-branch `feature/multi-tenant-saas`
- [ ] Lokaal end-to-end getest: 3 tenants, 3 logins, 3 verschillende dashboards
- [ ] Productie-deploy gedaan met `pg_dump`-backup van pre-conversion DB
- [ ] Bestaande MAS-demo-accounts werken nog (geen wachtwoord-reset nodig)
- [ ] Drie Vercel-projecten draaien met correcte branding
- [ ] README + ROADMAP geüpdatet
- [ ] Sprint-doc bevat post-mortem-sectie met daadwerkelijke gotchas (na uitvoering)
