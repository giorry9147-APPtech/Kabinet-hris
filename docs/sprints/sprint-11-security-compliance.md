# Sprint 10 — Security & compliance

> **Doel**: Voldoen aan eisen van een parastatale organisatie qua security, AVG/privacy en auditeerbaarheid.
>
> **Effort**: 3-5 dagen.
>
> **Blocker**: HR + management moet retentie-beleid + 2FA-strategie vastleggen.

## Vragen vóóraf

- Welke retentieperiode geldt voor personeelsdossiers in Suriname (na uitdiensttreding)? Standaard 7 jaar.
- Geldt AVG/GDPR voor MAS? Surinaamse equivalent? (MAS opereert in EU-wateren via vlag-staat, dus relevant.)
- Verplicht 2FA voor alle admins, of alleen super_admin?
- Wie is data-controller / functionaris gegevensbescherming?

## A. Tweefactor-authenticatie (2FA / TOTP)

### Tasks

- [ ] Filament 3 plugin: `filamentphp/two-factor-authentication` of `stechstudio/filament-google-authenticator`
- [ ] Of zelf bouwen met `pragmarx/google2fa-laravel` — kost iets meer maar volledige controle
- [ ] Migration: `users.two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`
- [ ] Filament setup-flow: bij eerste login na release → "Scan QR + voer code in"
- [ ] **Verplicht** voor rollen: super_admin, hr_manager, hr_admin, finance
- [ ] **Optioneel** voor: dept_head, employee
- [ ] Recovery-codes: 8 stuks bij setup, 1× printbaar
- [ ] Reset-procedure voor verloren toestel: alleen super_admin via UI

### Acceptance

- [ ] Login met admin@mas.sr → wordt na wachtwoord gevraagd om 2FA-code
- [ ] Failed 2FA → account na 5x mis 15min lockout
- [ ] Self-service in profile-page: 2FA aanzetten/uitzetten

## B. AVG / GDPR-tools

### Recht op inzage

- [ ] Endpoint `GET /api/me/data-export` → genereert ZIP met:
  - profile.json (alle persoonsdata)
  - employment-history.json
  - salary-history.json
  - certificates.json + alle cert-files
  - leave-requests.json
  - assets.json
- [ ] Self-service knop in `/mijn-dossier`: "Download mijn data"
- [ ] Logged in audit_log

### Recht op vergeten

- [ ] HR-only actie in Filament: "Anonimiseer medewerker"
- [ ] Vervangt namen/email/telefoon/adres met "ANONYMIZED-{id}", behoudt employee_number + statistieken
- [ ] Trigger: pas mogelijk na N jaar uitdiensttreding (default 7)
- [ ] Niet hard-delete: foreign keys naar leave_requests, salary_assignments, etc. moeten intact blijven

### Toestemming voor dataverwerking

- [ ] Bij eerste login: prompt "Toestemming dataverwerking" met link naar privacy-statement
- [ ] Geregistreerd in `consent_log` tabel (timestamp, ip, version van privacy-doc)

## C. Geautomatiseerde backups + restore-test

> Sprint 5 deploy heeft basic pg_dump backup. Hier maken we het robuuster.

### Tasks

- [ ] Backup-script naar **2 locaties**: lokaal + off-site (S3 / Backblaze B2)
- [ ] Encrypt backups met GPG vóór upload
- [ ] **Wekelijkse restore-test**: cron-script restoret naar staging-DB en checkt of `php artisan tinker --execute='echo Employee::count()'` consistent is
- [ ] Backup-monitoring: als 25 uur geen backup → mail naar admin
- [ ] Retentie: dagelijks 30 dagen, wekelijks 12 maanden, maandelijks 5 jaar

## D. Audit-rapporten

> We hebben al activity_log. Hier maken we rapporten die compliance-friendly zijn.

### Tasks

- [ ] **Per-medewerker rapport**: alle wijzigingen op deze persoon over een periode (PDF)
  - Wie heeft het salaris gewijzigd? Wanneer?
  - Wie heeft welke verlofaanvraag goedgekeurd?
- [ ] **Per-actor rapport**: wat heeft user X gedaan in maand Y (voor compliance-audits)
- [ ] **Toegangs-rapport**: wie heeft welke gevoelige data ingezien (salary.view, employees.view_sensitive)
- [ ] PDF-export voor accountant / interne audit
- [ ] Quarterly auto-mail naar HR-manager met audit-summary

## E. Beveiligings-hardening

### Tasks

- [ ] Rate-limiting op login-endpoint: max 5 failed pogingen per IP per minuut
- [ ] Password policy: min 12 tekens + 1 hoofdletter + 1 cijfer + 1 special (configureerbaar)
- [ ] Session-timeout: 8 uur inactief → logout
- [ ] CSP (Content Security Policy) headers via Spatie's `laravel-csp`
- [ ] HTTPS-only cookies (al voor in `.env.production`)
- [ ] HSTS header in Nginx
- [ ] Sensitive data logging filter: nooit wachtwoord/token in laravel.log
- [ ] Dependency-scanning: `composer audit` + `npm audit` als CI-stap
- [ ] Sentry of Bugsnag voor productie-errors (zonder PII te loggen)

## F. Permissies-review

### Tasks

- [ ] Maak een Filament-pagina "Toegangs-overzicht": tabel van rol × permissie matrix
- [ ] Per permissie: wie heeft het via welke rol
- [ ] Notificeer als een gebruiker een rol krijgt met "sensitive" permissies (employees.view_sensitive)
- [ ] Quarterly review-prompt aan super_admin: "Bevestig dat deze 12 mensen nog hun rollen nodig hebben"

## G. Disaster recovery plan (DRP) document

### Inhoud

- [ ] Wie is system-owner / contact bij outage
- [ ] RTO (Recovery Time Objective): hoeveel uur uitval acceptabel
- [ ] RPO (Recovery Point Objective): hoeveel data-verlies acceptabel
- [ ] Stap-voor-stap restore-procedure (vanuit pg_dump + medialibrary backup)
- [ ] Contact-lijst hosting-provider, registrar, mail-provider
- [ ] Halfjaarlijkse drill: simuleer outage, restore op staging, meet hoe lang het duurde

## Acceptance criteria

- [ ] Admin login vereist 2FA na deze sprint
- [ ] HR kan via UI "Anonimiseer medewerker" actie uitvoeren (na bevestigingsdialog)
- [ ] Self-service "Download mijn data" werkt en genereert ZIP < 10 sec
- [ ] Backup van vannacht staat in 2 locaties + restore-test in laatste week was groen
- [ ] Pen-test (basis: `nikto`, `nmap`, `sqlmap`) op staging vindt geen kritieke issues
- [ ] DRP-document in `docs/DRP.md`

## Wat NIET in deze sprint

- Volledig SOC 2 / ISO 27001 traject (ander project, kost weken/maanden)
- WAF (web application firewall) — overkill voor MAS-omvang, Cloudflare gratis tier volstaat
- Custom IAM systeem — gebruik gewoon spatie/permission
