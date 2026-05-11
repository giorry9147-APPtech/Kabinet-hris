# MAS HRIS — Roadmap & openstaande sprints

> Status: **Sprint 1 t/m 5 afgerond (incl. live demo deploy op Fly + Vercel).**
> Het systeem werkt end-to-end zowel lokaal als op de demo-URLs. Hieronder staat alles wat nog moet om er een productie-waardig, multi-tenant HR-SaaS-platform van te maken dat meerdere klanten (MAS, MRO, Kabinet van de President, toekomstige bedrijven) bedient vanuit één codebase.

## Wat is af

- **Backend**: Laravel 12 + PostgreSQL 17, 28 migrations, 11 modellen met relaties + audit-log + media uploads
- **Filament admin**: 11 resources (org-units, posities, medewerkers, dienstverbanden, salaris-toekenningen, salarisschalen, certificaattypes, certificaten, verlofaanvragen, assets, asset-toewijzingen) + audit-log viewer + organogram-pagina
- **API**: Sanctum tokens, `/api/auth/*` + 8× `/api/me/*` endpoints
- **Next.js portal**: 6 self-service pagina's (dashboard, dossier, verlof, certs, salaris, assets)
- **RBAC**: 6 rollen, 24 permissies, dept_head data-scoping werkt
- **Cert-expiry**: dagelijks scheduled command + Filament-bel notificaties
- **Excel-export**: Employees, Certificates, LeaveRequests
- **Echte MAS-organogram-structuur**: 38 eenheden + 49 functies + 20 demo-medewerkers
- **Visueel organogram** met avatar's per afdelingshoofd + PDF/print-export
- **Live demo**: backend op `mas-hris-backend.fly.dev`, frontend op `mas-hris.vercel.app`

## Prioriteiten — wat als eerste

### Critical (fundering voor SaaS-platform)
1. **[Sprint 6 — Multi-Tenant SaaS Conversion](sprints/sprint-06-multi-tenant-saas.md)** — ombouwen naar multi-tenant met stancl/tenancy, per-tenant branding, MAS + MRO + Kabinet als eerste drie tenants

### High (eerste 1-2 maanden na multi-tenant-conversie)
2. **[Sprint 7 — Real data + importer](sprints/sprint-07-real-data-import.md)** — echte medewerkers + Excel-import voor bulk-toevoegen (per tenant)
3. **[Sprint 8 — E-mail notificaties + mobile polish](sprints/sprint-08-notifications-mobile.md)** — mail bij verlof-beslissing, mobile-friendly portal
4. **[Sprint 11 — Security & compliance](sprints/sprint-11-security-compliance.md)** — 2FA voor admin, GDPR/AVG-tools, betere backups

### Medium (kwartaal 2)
5. **[Sprint 9 — Nieuwe modules](sprints/sprint-09-new-modules.md)** — recruitment, training, performance, onboarding (per tenant in/uit te schakelen)
6. **[Sprint 10 — Maritime-specifieke features](sprints/sprint-10-maritime-extensions.md)** — STCW-koppeling aan vessel-rosters, watchkeeping schema's, locatie Paramaribo/Nickerie (alleen voor MAS-tenant)

### Nice-to-have
- Donker thema / WCAG accessibility
- 2-talig (NL/EN) — maritieme terminologie is vaak EN
- Microsoft Azure AD SSO (i.p.v. eigen wachtwoord)
- API Swagger documentatie
- Sentry/Bugsnag voor productie-errors
- PHPUnit testdekking voor kritieke flows

## Per onderdeel — geschatte effort

| Sprint | Wat | Effort | Blocker? |
|---|---|---|---|
| 6 — Multi-Tenant SaaS | stancl/tenancy + branding-systeem + 3 tenants live | 4-6 dagen | Geen — alles in eigen hand |
| 7 — Real data | Importer + echte medewerkers per tenant | 1-2 dagen | HR-export uit huidig systeem per tenant nodig |
| 8 — Mail + mobile | E-mail + responsive | 2-3 dagen | SMTP-credentials per tenant nodig |
| 9 — Nieuwe modules | 4 modules met per-tenant feature-toggle | 2-3 weken | Scope per module bepalen met HR |
| 10 — Maritime | STCW/vessel/rosters | 1-2 weken | Vessel-data nodig (MAS) |
| 11 — Security | 2FA + GDPR | 3-5 dagen | Backup-strategie kiezen |

## Per onderwerp — quick reference

| Onderwerp | Detail-doc |
|---|---|
| Multi-tenant architectuur (DB-per-tenant, Filament dual-panel) | [sprint-06-multi-tenant-saas.md](sprints/sprint-06-multi-tenant-saas.md) |
| Per-tenant branding (logo, kleuren, ministernaam) | [sprint-06-multi-tenant-saas.md](sprints/sprint-06-multi-tenant-saas.md) |
| Tenant-onboarding via `php artisan tenants:create` | [sprint-06-multi-tenant-saas.md](sprints/sprint-06-multi-tenant-saas.md) |
| CSV/Excel import van personeel | [sprint-07-real-data-import.md](sprints/sprint-07-real-data-import.md) |
| Mail-templates verlof/cert | [sprint-08-notifications-mobile.md](sprints/sprint-08-notifications-mobile.md) |
| Mobile sidebar drawer | [sprint-08-notifications-mobile.md](sprints/sprint-08-notifications-mobile.md) |
| Vacature-workflow (recruitment) | [sprint-09-new-modules.md](sprints/sprint-09-new-modules.md) |
| Beoordelingsgesprekken | [sprint-09-new-modules.md](sprints/sprint-09-new-modules.md) |
| Trainings-budget per medewerker | [sprint-09-new-modules.md](sprints/sprint-09-new-modules.md) |
| Onboarding-checklist nieuwe hire | [sprint-09-new-modules.md](sprints/sprint-09-new-modules.md) |
| STCW koppelen aan scheepstoewijzing | [sprint-10-maritime-extensions.md](sprints/sprint-10-maritime-extensions.md) |
| Watchkeeping rooster (24/7 VTS) | [sprint-10-maritime-extensions.md](sprints/sprint-10-maritime-extensions.md) |
| Locatie Paramaribo / Nickerie | [sprint-10-maritime-extensions.md](sprints/sprint-10-maritime-extensions.md) |
| 2FA voor HR/admin | [sprint-11-security-compliance.md](sprints/sprint-11-security-compliance.md) |
| AVG / GDPR — recht op inzage/vergeten | [sprint-11-security-compliance.md](sprints/sprint-11-security-compliance.md) |
| Geautomatiseerde DB-backups | [sprint-11-security-compliance.md](sprints/sprint-11-security-compliance.md) |

## Vragen die HR / IT bij elke klant moet beantwoorden vóór elk sprint

- **Sprint 6**: per tenant — wat zijn de officiële brand-kleuren? Is er een hoog-resolutie logo (SVG of PNG ≥512px)? Naam van de minister/directeur die op het dashboard moet komen?
- **Sprint 7**: in welk formaat zit de huidige personeels-export (Excel? PDF? handmatig)? Hoeveel mensen per tenant?
- **Sprint 8**: SMTP via eigen mailserver van de tenant, of via een service (SendGrid, Postmark, AWS SES)?
- **Sprint 9**: welke modules hebben voorrang per tenant? Alle 4 of fasering?
- **Sprint 10** (alleen MAS): heeft MAS al een vessel-database? Hoe ziet het huidige watchkeeping-rooster eruit?
- **Sprint 11**: welke retention-periode voor personeelsdossiers (na uitdiensttreding)? Verschilt dit per ministerie?

## Onderhoud & dagelijkse operatie

Eens live, blijft er werk:

- `php artisan queue:work` als systemd-service draaien (anders worden mails/notificaties niet verzonden)
- `php artisan schedule:run` elke minuut via cron (anders draait `certs:check-expiry` niet)
- Dagelijkse `pg_dump` backups per tenant-DB naar off-site storage (S3 o.i.d.)
- Maandelijks: composer/npm dependencies updaten + smoke-test per tenant
- Per kwartaal: review van wie nog welke rol/permissie nodig heeft (per tenant)
- Bij nieuwe klant: `php artisan tenants:create <slug>` + branding-upload via super-admin panel → live in <5 minuten

Zie [sprint-05-deploy.md](sprints/sprint-05-deploy.md) voor concrete deploy-commando's en [sprint-06-multi-tenant-saas.md](sprints/sprint-06-multi-tenant-saas.md) voor tenant-management.
