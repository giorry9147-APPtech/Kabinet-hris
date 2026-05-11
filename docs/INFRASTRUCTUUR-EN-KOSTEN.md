# Infrastructuur & Kosten MAS-HRIS

**Wat is er nodig om MAS-HRIS van demo naar productie te brengen, hoeveel kost het, en hoe pakken echte SaaS-startups het aan?**

*Versie 2026-05-06 — geschreven in dollars (USD); reken voor SRD met dagkoers.*

---

## 1. De volledige stack — wat heb ik nodig?

Een productie-waardig HRIS bestaat uit ~12 bouwstenen. Voor MAS (parastataal, ~50 medewerkers, 1 entiteit, Surinaamse context) ziet de stack er zo uit:

| # | Bouwsteen | Waarvoor | Status nu |
|---|-----------|----------|-----------|
| 1 | **Applicatie-hosting (backend)** | Laravel + Filament draait ergens | Fly.io free tier |
| 2 | **Frontend-hosting** | Next.js portaal serveren | Vercel free tier |
| 3 | **Managed PostgreSQL** | De database, met automatische back-ups | Fly Postgres free tier (1 GB) |
| 4 | **Object-storage (S3-compatible)** | Geüploade bestanden (avatars, certificaten, documenten) | Fly persistent volume (1 GB) — niet S3 |
| 5 | **E-mail / transactional mail** | Verlof-meldingen, certificaat-vervalwaarschuwingen, password-resets, welkomstmails | Geen — `MAIL_MAILER=log` |
| 6 | **Domeinnaam + DNS** | `hris.mas.sr` of `mas-hris.com` ipv Fly/Vercel-URL's | Geen — gebruikt `*.fly.dev` en `*.vercel.app` |
| 7 | **SSL/TLS-certificaten** | HTTPS | Auto via Fly + Vercel (gratis) |
| 8 | **Error-tracking** | Crashes, exceptions, slow queries vangen vóór gebruikers klagen | Geen |
| 9 | **Uptime-monitoring** | Pieper als het systeem down is | Geen |
| 10 | **Off-site back-ups** | DB-dumps elke nacht, op andere locatie dan productie | Fly automatic snapshots (5 dagen) |
| 11 | **CDN** | Statische assets en images snel wereldwijd | Vercel built-in voor frontend; geen voor backend assets |
| 12 | **Logging / observability** | Wat gebeurde er rond 14:32 toen de Directeur klaagde? | Fly logs (ephemeral); Laravel log naar stderr |

**Optioneel maar gangbaar:**

| # | Bouwsteen | Waarvoor |
|---|-----------|----------|
| 13 | Redis (cache + queue) | Snellere sessies, betere queue-throughput dan database driver |
| 14 | SMS-gateway | OTP-codes, kritieke meldingen aan veldwerkers (scheepsinspecteurs offshore) |
| 15 | 2FA / Auth-provider | TOTP-2FA voor admins (kan ook self-hosted met Filament-2FA-plugin) |
| 16 | Status-page | `status.mas.sr` zodat gebruikers zelf zien of er een incident is |
| 17 | Helpdesk-tool | Tickets, support-mail, kennisbank voor eindgebruikers |
| 18 | Documentatie-platform | Handleidingen, training-video's |
| 19 | Compliance-tooling | AVG/persoonsgegevens-register, audit-rapporten, encryptie-bewijs |

---

## 2. Per bouwsteen: aanbieders en prijzen

### 2.1 Applicatie-hosting (backend)

| Aanbieder | Plan | Prijs/maand | Geschikt voor MAS? |
|-----------|------|-------------|--------------------|
| **Fly.io** (huidig) | shared-cpu-1x, 512 MB | $0 (free tier — auto-stop) tot ~$5 voor always-on | ✅ Goed voor pilot. Auto-stop = trage cold-start |
| **Render** | Web Service Standard | $7/mo | ✅ Eenvoudig, persistent disk inbegrepen |
| **Railway** | Hobby/Pro | $5-20/mo | ✅ Mooi dashboard, container-based |
| **DigitalOcean App Platform** | Basic | $5-12/mo | ✅ Stabiel, regio Amsterdam |
| **AWS Elastic Beanstalk / ECS** | t3.small | ~$15-30/mo + data | ⚠️ Overkill voor MAS-schaal |
| **Eigen VPS (Hetzner CX22)** | 2 vCPU / 4 GB | €5/mo (~$5.50) | ✅ Goedkoopst, vereist sysadmin |

**Aanbeveling MAS productie:** Fly.io always-on (shared-cpu-1x, $5/mo) of Render Standard ($7/mo).

### 2.2 Frontend-hosting

| Aanbieder | Plan | Prijs/maand |
|-----------|------|-------------|
| **Vercel** (huidig) | Hobby | $0 (geschikt voor MAS-volume) |
| **Vercel Pro** | Pro | $20/mo (per teamlid) — nodig bij commercieel gebruik |
| **Netlify** | Free → Pro | $0 → $19/mo |
| **Cloudflare Pages** | Free | $0 (uitstekend, geen team-restricties) |

**Aanbeveling:** Cloudflare Pages of Vercel Hobby blijft genoeg voor MAS. Vercel Pro pas nodig als je commercieel gaat.

### 2.3 Managed PostgreSQL

| Aanbieder | Plan | Prijs/maand | Notes |
|-----------|------|-------------|-------|
| **Fly Postgres** (huidig) | shared-cpu-1x 1GB | $0 free → ~$2-3/mo voor groter | Geen managed back-ups op free tier |
| **Supabase** | Free → Pro | $0 → $25/mo | Inclusief PostgREST API, auth, storage |
| **Neon** | Free → Launch | $0 → $19/mo | Serverless Postgres, branching voor staging |
| **Render Postgres** | Starter | $7/mo (256 MB) → $20/mo (1 GB) | Daily back-ups |
| **DigitalOcean Managed DB** | Basic | $15/mo (1 GB RAM, 10 GB disk) | Dagelijkse back-ups, point-in-time recovery |
| **AWS RDS** | db.t4g.micro | $13-20/mo + storage | Enterprise-grade |

**Aanbeveling MAS productie:** Neon Launch ($19/mo) of DigitalOcean Managed DB ($15/mo). Beide hebben echte off-site back-ups.

### 2.4 File-storage (S3-compatible)

Spatie MediaLibrary kan zo wisselen van local disk naar S3 via één env-variabele.

| Aanbieder | Prijs (storage) | Prijs (egress/bandwidth) | Voor MAS |
|-----------|-----------------|--------------------------|----------|
| **Cloudflare R2** | $0.015/GB/mo | **$0** (geen egress kosten!) | ✅ Beste prijs/kwaliteit |
| **Backblaze B2** | $0.006/GB/mo | $0.01/GB | ✅ Goedkoopst |
| **DigitalOcean Spaces** | $5/mo voor 250 GB + 1 TB egress | inbegrepen | ✅ Voorspelbare bundel |
| **AWS S3** | $0.023/GB/mo | $0.09/GB | Standaard, maar duurder |

Voor MAS (~50 medewerkers × ~10 MB documenten = ~500 MB): **R2** of **Backblaze B2** kost <$1/mo.

### 2.5 E-mail / transactional mail

| Aanbieder | Free tier | Betaald | Notes |
|-----------|-----------|---------|-------|
| **Resend** | 3.000 mails/mo | $20/mo voor 50K | Modern, mooie React-templates, beste DX |
| **Postmark** | 100 mails/mo (test) | $15/mo voor 10K | Beste deliverability, gespecialiseerd in transactional |
| **Amazon SES** | 62.000 mails/mo (vanaf EC2) | $0.10 per 1.000 | Goedkoopst, lastig op te zetten |
| **SendGrid** | 100/dag gratis | $19.95/mo voor 50K | Bekend, oudere UI |
| **Mailgun** | Geen free | $15/mo voor 10K | EU-regio mogelijk |

**Aanbeveling MAS:** start met **Resend** ($20/mo of gratis als <3K mails/mo). Voor MAS volume (~50 medewerkers × 5 mails/maand = 250 mails) blijft het ruim binnen de free tier.

### 2.6 Domeinnaam

| Provider | Prijs/jaar | Notes |
|----------|------------|-------|
| **Cloudflare Registrar** | At-cost (~$8-10 voor `.com`) | Geen markup — beste deal |
| **Namecheap** | $10-15/jaar | Eenvoudig, tweedaagse renewals |
| **`.sr` domein (SurinetNT)** | ~SRD 200/jaar | Suriname TLD — politiek-correcte keuze |

**Aanbeveling:** combinatie. `mas-hris.com` ($10/jaar) voor tech, eventueel `hris.mas.sr` als je gebruik maakt van het bestaande MAS-domein.

### 2.7 Error-tracking

| Aanbieder | Free tier | Betaald | Voor MAS |
|-----------|-----------|---------|----------|
| **Sentry** | 5.000 errors/mo | $26/mo Team plan | ✅ Industriestandaard, Laravel + Next.js plug-and-play |
| **Bugsnag** | 7.500 errors/mo | $59/mo | ✅ |
| **Honeybadger** | 30 dagen trial | $26/mo | ✅ Laravel-vriendelijk |
| **Self-hosted GlitchTip** | Server-kosten | $0 | Open-source Sentry-alternatief |

**Aanbeveling MAS:** Sentry free tier is genoeg (5K errors/maand is veel voor 50 gebruikers).

### 2.8 Uptime-monitoring

| Aanbieder | Free tier | Betaald |
|-----------|-----------|---------|
| **Better Stack (BetterUptime)** | 10 monitors @ 3-min interval | $24/mo |
| **UptimeRobot** | 50 monitors @ 5-min | $7/mo voor 1-min |
| **Pingdom** | 14-dagen trial | $15/mo |
| **Healthchecks.io** | 20 checks gratis | $5/mo |

**Aanbeveling:** UptimeRobot free of Better Stack free. Voor MAS prima.

### 2.9 Off-site back-ups

| Aanbieder | Prijs | Notes |
|-----------|-------|-------|
| **SimpleBackups.com** | $19/mo | Database + storage, off-site naar S3 |
| **Snapshooter** | $7/mo | DigitalOcean ecosystem |
| **Eigen cron + R2** | ~$1/mo | Custom shellscript, dump → R2 |

Managed DB-providers (Neon, DO) bieden dagelijkse back-ups inclusief; aparte tool dan vaak overbodig.

### 2.10 SMS-gateway (optioneel)

| Aanbieder | Prijs/SMS Suriname |
|-----------|---------------------|
| **Twilio** | ~$0.05-0.10 per SMS |
| **MessageBird/Bird** | ~$0.04-0.08 per SMS |
| **Vonage** | Vergelijkbaar |
| **Lokale SMS-aggregator** (Digicel/Telesur) | Vaak goedkoper, vergt account-set-up |

Voor 100 SMS/mo (kritieke notificaties): ~$5-10/mo.

### 2.11 Cache + queue (optioneel)

| Aanbieder | Free tier | Betaald | Voor MAS |
|-----------|-----------|---------|----------|
| **Upstash Redis** | 10K commands/dag | $0.2 per 100K | ✅ Serverless, perfect klein-tot-middelgroot |
| **Redis Cloud (Redis Labs)** | 30 MB free | $7/mo voor 250 MB | ✅ |
| **Fly Redis (geen meer)** | — | — | Niet meer beschikbaar |

Op MAS-schaal blijft database-driver voor sessions/queue prima werken. Pas overstappen naar Redis bij >500 gelijktijdige users.

### 2.12 2FA voor admins

| Optie | Prijs | Inspanning |
|-------|-------|------------|
| **Filament 2FA-plugin** (TOTP) | Gratis (open-source) | Halve dag implementatie |
| **WorkOS** (SAML/SSO) | $2.50/user/mo | Nodig bij echte SSO via MAS-AD |
| **Auth0** (OIDC + MFA) | $0 → $35/mo voor 1.000 users | Overkill voor MAS |

**Aanbeveling:** TOTP-plugin voor de 6 admins. SSO pas relevant als MAS Microsoft 365 / Azure AD heeft.

---

## 3. Drie pakketten — wat past bij MAS?

### 🟢 Pakket A — "Demo" (huidige situatie)
**Voor wie:** Jezelf tijdens ontwikkeling, demo voor stakeholders, maximaal 5 gelijktijdige test-users.

| Component | Aanbieder | Kosten/mo |
|-----------|-----------|-----------|
| Backend | Fly.io free (auto-stop) | $0 |
| Frontend | Vercel Hobby | $0 |
| Database | Fly Postgres free 1GB | $0 |
| Storage | Fly volume 1GB | $0.15 |
| E-mail | log-driver (geen mail) | $0 |
| Domein | `*.fly.dev` / `*.vercel.app` | $0 |
| Monitoring | Geen | $0 |
| **Totaal** | | **~$0** |

⚠️ **Beperkingen:** cold-starts (5-10 sec na auto-stop), geen back-ups, geen mail, geen eigen domein, geen error-tracking.

---

### 🟡 Pakket B — "Pilot" (eerste echte gebruikers, 30-100 medewerkers)
**Voor wie:** MAS gaat 6 maanden live met 1 afdeling als pilot, of een kleine parastataal van 30-100 medewerkers.

| Component | Aanbieder | Kosten/mo |
|-----------|-----------|-----------|
| Backend always-on | Fly.io shared-cpu-1x | $5 |
| Frontend | Vercel Hobby (of Cloudflare Pages) | $0 |
| Database | Neon Launch (10 GB) | $19 |
| Storage | Cloudflare R2 (5 GB) | $0.10 |
| E-mail | Resend (free tier 3K mails) | $0 |
| Domein | `hris.mas.sr` (eigen subdomein) of `.com` | $1 (~$10/jaar) |
| Error-tracking | Sentry free | $0 |
| Uptime-monitor | UptimeRobot free | $0 |
| Off-site back-ups | Inbegrepen bij Neon | $0 |
| Cloudflare CDN/WAF | Free plan | $0 |
| **Totaal** | | **~$25/maand** |

✅ Eigen domein, HTTPS, snel responsief, automatische back-ups, mail werkt, errors gemonitord.

---

### 🔴 Pakket C — "Productie volledig MAS" (500+ medewerkers, 24/7 SLA)
**Voor wie:** Volledige uitrol MAS (50 medewerkers × 5 jaar groei + audit-trail + maritieme operatie 24/7).

| Component | Aanbieder | Kosten/mo |
|-----------|-----------|-----------|
| Backend (HA, 2 machines) | Fly.io shared-cpu-2x × 2 + load-balancer | $30 |
| Frontend | Vercel Pro (team-account) | $20 |
| Database | DigitalOcean Managed DB (HA, 2 GB RAM) | $50 |
| Read replica voor rapportages | DO Read Replica | $25 |
| Storage | Cloudflare R2 (50 GB) | $1 |
| E-mail (transactional) | Resend Pro 50K mails | $20 |
| Domein + Cloudflare | Cloudflare Registrar | $1 |
| Error-tracking | Sentry Team | $26 |
| Uptime + status-page | Better Stack | $24 |
| Off-site back-ups (extra laag) | SimpleBackups | $19 |
| Redis (sessies + queue) | Upstash | $5 |
| SMS-gateway (kritieke alerts) | Twilio (~200 SMS) | $15 |
| Helpdesk-tool | Plain.com / Linear free | $0 |
| Status-page (apart) | inbegrepen Better Stack | $0 |
| 2FA | Filament-plugin (gratis) | $0 |
| **Totaal** | | **~$236/maand** |

✅ Volledig HA, 24/7 monitoring, real backups, mail + SMS, helpdesk, status-page voor gebruikers.

---

## 4. Eenmalige kosten (one-off)

Deze vergeten mensen vaak in hun budgettering:

| Item | Eenmalig |
|------|----------|
| Logo / branding refresh (als nodig) | $200-1000 |
| Datamigratie van Excel/papier | 1-2 dagen werk = SRD 5.000-10.000 (in-house) of $500-1.500 (extern) |
| Pilot-training voor HR + admins | 2-4 dagdelen = SRD 4.000-8.000 |
| User-handleidingen & training-video's | $300-800 |
| Penetration test / security audit (vóór go-live) | $1.500-5.000 |
| AVG/persoonsgegevens-register opstellen | $500-1.500 |
| Eindgebruiker-handleiding (PDF + portal-help) | $200-500 (in-house gratis) |
| **Subtotaal eenmalig** | **$3.700-13.300** |

---

## 5. Verborgen kosten — wat startups vergeten

1. **Tijd voor incidenten** — als er iets stuk gaat ben jij beschikbaar. Reserveer 4-8 uur/maand for incident response.
2. **Bandbreedte spike** — bij rapportage-export of bulk-upload kan de egress kort hoog zijn.
3. **Database-groei** — een actieve HRIS groeit ~50-200 MB/jaar door audit-logs. Reken op DB-upgrade na 3-5 jaar.
4. **Compliance-update** — wijziging in CAO of fiscaal regime = developer-uren ($50-150/uur).
5. **Developer-onderhoud** — ~5-10% van de bouwkosten per jaar voor patches, library-updates, security-fixes.
6. **Telefoon/SMS bij incident** — bij echte 24/7 SLA is een oncall-bereikbaarheid nodig (~$200-500/mo extra).
7. **Externe pen-test** — eens per jaar vóór audit (~$1.500-5.000).

---

## 6. Hoe doen echte SaaS-startups het?

### Pre-launch (0-10 klanten)
- Alle gratis tiers (Vercel, Cloudflare, Sentry free, Resend free)
- Eén persoon doet ops
- Database op Neon free of Supabase free
- Domein voor $10/jaar
- **Run-rate: $0-30/mo**

### Early traction (10-100 klanten)
- Pakket vergelijkbaar met **Pakket B** hierboven
- Postmark/Resend paid plan
- Sentry Team
- Eerste oncall-rotatie
- **Run-rate: $100-500/mo**

### Growth (100-1000 klanten)
- Pakket vergelijkbaar met **Pakket C**
- Read-replica voor analytics
- Eerste support-engineer in dienst
- Status-page publiek
- **Run-rate: $500-3.000/mo**

### Scale (1000+ klanten, multi-region)
- Cloud-native architectuur (vaak AWS/GCP managed services)
- Datastore-sharding, event-bus (Kafka/RabbitMQ)
- DevOps team van 2-4 personen
- 24/7 oncall-rotatie betaald
- Compliance-officer in dienst (SOC2, ISO 27001)
- **Run-rate: $5.000-50.000/mo**

---

## 7. Concrete advies voor MAS

### Voor de presentatie (over 1-4 weken):
**Blijf op Pakket A** ($0/mo). De demo is functioneel, de stakeholders moeten waarde zien, niet infrastructuur.

### Direct na akkoord stakeholders (eerste 3 maanden):
**Upgrade naar Pakket B** (~$25/mo + ~$5K eenmalig). Configureer:
1. Eigen domein `hris.mas.sr` of `mas-hris.com`
2. Resend-account voor mail (verlofmeldingen, certificaat-vervalwaarschuwingen)
3. Cloudflare R2 voor file-storage (vervang Fly volume)
4. Sentry voor error-tracking
5. Off-site back-up via Neon's automatische snapshots
6. Datamigratie: import echte MAS-medewerkers via sprint-06

### Bij echte productie-uitrol (na pilot, 6-12 maanden):
**Upgrade naar Pakket C** (~$236/mo + ~$10K eenmalig). Voeg toe:
1. HA-database met read-replica voor rapportages
2. Redis voor performante queue (notifications, exports)
3. SMS-gateway voor kritieke meldingen (offshore inspecteurs)
4. Status-page op `status.mas-hris.com`
5. Pen-test vóór go-live
6. AVG-compliance audit

### 5-jaar TCO voor MAS (50 medewerkers, Pakket B → C migratie in jaar 2):

| Jaar | Maandelijkse kosten | Eenmalig | Totaal jaar |
|------|---------------------|----------|-------------|
| Jaar 1 (Pilot) | $25 × 12 = $300 | $5.000 | $5.300 |
| Jaar 2 (Productie ramp-up) | $236 × 12 = $2.832 | $5.000 | $7.832 |
| Jaar 3 | $236 × 12 = $2.832 | $1.500 (pen-test) | $4.332 |
| Jaar 4 | $250 × 12 = $3.000 (groei) | $1.500 | $4.500 |
| Jaar 5 | $280 × 12 = $3.360 | $1.500 | $4.860 |
| **5-jaar totaal** | | | **~$26.800 (≈ SRD 950.000)** |

Vergelijk met enterprise HRIS (BambooHR, Workday): SRD 1.500-3.000 per medewerker per jaar = **SRD 75.000-150.000/jaar voor MAS** = SRD 375.000-750.000 over 5 jaar — en dan heb je nog steeds geen STCW/maritieme module.

**Conclusie:** maatwerk MAS-HRIS is over 5 jaar **2-5× goedkoper** dan een commerciële HRIS, met functionaliteit die specifiek op MAS is afgestemd.

---

## 8. Developer-omzet & winst voor jezelf

De cijfers hierboven gaan over wat MAS aan **infrastructuur** kwijt is. Maar jij bouwt dit ook — dat is je eigen verdienste. Hier de realistische cijfers, in twee scenario's: Surinaamse markttarieven en internationale freelance-tarieven.

### 8.1 Wat is het werk waard tot nu toe?

Sprints 1-5 + de extra's van vandaag (documents, reports, widget-fixes, infra-tuning) tellen ruwweg op tot:

| Component | Geschatte uren |
|-----------|----------------|
| Backend Laravel + 22 migraties + 11 models | 40-60 u |
| Filament admin (11+ resources, organogram, rapportages) | 40-60 u |
| Next.js portaal (8 pagina's + auth + API-client) | 30-40 u |
| Document-upload feature + admin-resource | 8-12 u |
| Deployment pipeline (Fly + Vercel + volume) | 12-20 u |
| Bugfixes, refactoring, requirements-overleg | 30-50 u |
| **Totaal tot nu toe** | **160-240 uur** |

### 8.2 Tarieven

| Markt | Junior | Mid | Senior |
|-------|--------|-----|--------|
| **Suriname (in-house)** | SRD 8.000-15.000/mo | SRD 20.000-40.000/mo | SRD 50.000+/mo |
| **Suriname (freelance)** | SRD 250-400/uur | SRD 500-800/uur | SRD 1.000-2.000/uur |
| **Internationaal remote** | $25-40/uur | $40-80/uur | $80-150/uur |
| **Caribbean/LATAM remote** | $20-35/uur | $35-60/uur | $60-100/uur |

Dit project (Laravel + Next.js + maritieme domein) verdient een **mid-to-senior tarief**. Je hebt productie-grade keuzes gemaakt (rolling deploy, volume-mounts, audit-logging, Spatie media-library), dat kun je verantwoorden.

### 8.3 Build-fee (eenmalig — voor het werk tot nu toe)

| Tarief | Uren | Totaal |
|--------|------|--------|
| SR senior freelance (SRD 1.500/uur) | 200 u | **SRD 300.000** (~$8.000) |
| Internationaal mid (€60/uur) | 200 u | **€12.000** (~$13.000) |
| Internationaal senior ($100/uur) | 200 u | **$20.000** |

**Realistisch voor MAS (Surinaamse parastatale klant):** SRD 200.000-350.000 als one-time build-fee.

### 8.4 Pricing-modellen — kies één of mix

#### Model 1: Fixed-price build + SaaS-license
- **Eenmalig:** SRD 250.000 build-fee
- **Maandelijks:** SRD 5.000-10.000 license/maintenance (dekt patches, hosting, support)
- **Voordeel:** Recurring revenue, MAS heeft voorspelbare kosten
- **Nadeel:** Bij groei van MAS verdien je niet evenredig meer

#### Model 2: Per-medewerker per maand (echte SaaS-stijl)
- **Eenmalig:** SRD 100.000-150.000 setup-fee
- **Maandelijks:** SRD 200-400 per actieve medewerker per maand
- **Voor MAS (50 medewerkers):** SRD 10.000-20.000/mo
- **Voordeel:** Schaalt mee, hoge marge bij groei
- **Nadeel:** Lastig verkopen aan parastataal die "eigenaar" wil zijn

#### Model 3: Time & materials (uur-basis)
- **Build:** ~200 u × SRD 1.500 = SRD 300.000 eenmalig
- **Onderhoud:** ~10-20 u/mo × SRD 1.500 = SRD 15.000-30.000/mo
- **Nieuwe sprints:** apart factureren per sprint (sprint 6: data-import = ~40 u = SRD 60.000)
- **Voordeel:** Eerlijk, transparant
- **Nadeel:** MAS moet uren goedkeuren, jij moet uren registreren

#### Model 4: Vast jaarcontract (parastatale-vriendelijk)
- **Eerste jaar:** SRD 400.000 (build + 12 maand support)
- **Vervolgjaren:** SRD 120.000-180.000/jaar (alleen maintenance + 2 nieuwe features per jaar)
- **Voordeel:** Past in jaarbegroting MAS, predictable voor beide partijen
- **Nadeel:** Onderhandel goed wat "een feature" is

**Aanbeveling:** Model 4 voor parastatale klant. Surinaamse overheid denkt in jaarbegrotingen, niet in maandelijkse SaaS-kosten.

### 8.5 5-jaar omzetprognose (Model 4)

| Jaar | Activiteit | Omzet (SRD) | Omzet (USD) |
|------|------------|-------------|-------------|
| Jaar 1 | Build + support eerste jaar | SRD 400.000 | ~$11.000 |
| Jaar 2 | Maintenance + 2 features (CAO-koppeling, mobiel) | SRD 180.000 | ~$5.000 |
| Jaar 3 | Maintenance + 2 features (voorraadbeheer, recruitment) | SRD 180.000 | ~$5.000 |
| Jaar 4 | Maintenance + 1 feature (BI-dashboard) | SRD 150.000 | ~$4.200 |
| Jaar 5 | Maintenance + verlenging contract | SRD 150.000 | ~$4.200 |
| **5-jaar omzet** | | **SRD 1.060.000** | **~$29.400** |

### 8.6 Marge — wat is winst, wat is kost?

Stel je gebruikt **Pakket C** (productie) en hanteert **Model 4**:

| Jaar 2 | Bedrag (SRD) |
|--------|--------------|
| **Omzet** | 180.000 |
| **− Infra-kosten** (Pakket C × 12) | -100.000 (~$2.832) |
| **− Eenmalig** (pen-test, training) | -50.000 (~$1.500) |
| **− Tools/licenties** (IDE, design, AI-credits) | -10.000 |
| **Bruto-marge** | **20.000 SRD** ≈ 11% |

🚨 **Probleem:** als jij ALLE infra-kosten uit jouw retainer betaalt, blijft er weinig over.

**Oplossingen:**
1. **MAS betaalt infra direct** (eigen Fly/Vercel/Neon-accounts op MAS-facturatie). Jij rekent alleen developer-uren. **Marge gaat naar 80-90%.**
2. **Marge bovenop infra** in je quote. SRD 180.000/jaar wordt SRD 280.000-300.000/jaar (jij regelt alles, MAS heeft één factuur).
3. **Hybride:** infra apart, jouw retainer puur developer-tijd.

**Aanbeveling:** Optie 1 of 2. Nooit zelf de infra eten — als MAS Excel-rapportages wil exporten en de DB groeit naar 10 GB, zit jij met de kosten.

### 8.7 Realistische jaaromzet bij meerdere klanten

Als jij dit als product zou herverkopen aan andere parastatalen of MKB's:

| Aantal klanten | Maandomzet (SRD 8.000/klant) | Jaaromzet |
|----------------|------------------------------|-----------|
| 1 (alleen MAS) | 8.000 | **96.000** |
| 3 klanten | 24.000 | **288.000** |
| 5 klanten | 40.000 | **480.000** |
| 10 klanten | 80.000 | **960.000** |

⚠️ Bij 5+ klanten heb je een part-time/full-time job aan support en moet je incalculeren: extra developer-uren, support-systeem, marketing, sales, contracten. Dat is een echt SaaS-bedrijf bouwen.

### 8.8 Concrete tip voor MAS-onderhandeling

Bied MAS een **drie-jarig contract** aan met:
- **Jaar 1:** SRD 400.000 (build-fee + go-live + 12 maand support + training)
- **Jaar 2:** SRD 200.000 (maintenance + 1 grote nieuwe module)
- **Jaar 3:** SRD 180.000 (maintenance + 1 module)
- **Optie tot verlenging** met dezelfde voorwaarden

Argument naar MAS: **commerciële alternatieven kosten SRD 75K-150K/jaar puur licentie**, exclusief setup, training, customisaties, of maritieme functionaliteit. Jouw aanbod = vergelijkbare prijs maar je krijgt eigenaarschap, customisatie en lokale support.

**Jouw kant van de rekening (als infra apart wordt gefactureerd):**
- Jaar 1: SRD 400.000 omzet, ~30 u/mo werk gemiddeld = SRD 1.100/u effectief
- Jaar 2-3: SRD 180-200K omzet, ~10 u/mo = SRD 1.500-1.700/u effectief
- **3-jaar totaal: ~SRD 780.000 (≈ $21.500)**

---

## 9. Quick reference — links

- Fly.io: https://fly.io/docs/about/pricing/
- Vercel: https://vercel.com/pricing
- Neon: https://neon.tech/pricing
- Supabase: https://supabase.com/pricing
- Cloudflare R2: https://developers.cloudflare.com/r2/pricing/
- Backblaze B2: https://www.backblaze.com/cloud-storage/pricing
- Resend: https://resend.com/pricing
- Postmark: https://postmarkapp.com/pricing
- Sentry: https://sentry.io/pricing/
- Better Stack: https://betterstack.com/pricing
- UptimeRobot: https://uptimerobot.com/pricing/
- DigitalOcean: https://www.digitalocean.com/pricing
- Cloudflare Registrar: https://www.cloudflare.com/products/registrar/
- Twilio (SMS): https://www.twilio.com/sms/pricing/sr
