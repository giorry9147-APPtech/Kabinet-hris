# Kostenvoorstel MAS-HRIS

**Maritieme Autoriteit Suriname — Human Resource Information System**

*Versie 1.0 — 6 mei 2026*

Bedragen in SRD (Surinaamse Dollar) en USD ($). Wisselkoers indicatief: USD 1 ≈ SRD 36.

---

## 1. Wat is er al gebouwd?

Voordat de pakketten in detail gaan, een overzicht van de huidige staat van MAS-HRIS — dit is wat MAS nu al heeft als basis:

| Onderdeel | Status |
|-----------|--------|
| Backend (Laravel 12 + Filament 3 + PostgreSQL 17) | ✅ Productie-grade |
| Admin-paneel met 11+ resources (medewerkers, dienstverband, salaris, certificaten, verlof, assets, organogram, rapportages, geüploade documenten) | ✅ Klaar |
| Medewerkersportaal (Next.js 16) met 8 pagina's (dossier, verlof, certificaten, salaris, assets, documenten, dashboard) | ✅ Klaar |
| Authenticatie & rolgebaseerde toegang (super_admin / hr_admin / dept_head / employee) | ✅ Klaar |
| Audit-log van alle mutaties | ✅ Klaar |
| 6 standaard exports (Excel/CSV) — personeel, verlof, certificaten, salaris, assets, dienstverband-mutaties | ✅ Klaar |
| Visueel organogram met 38 units, 49 functies (officiële MAS-structuur) | ✅ Klaar |
| Live demo-omgeving (Fly.io + Vercel) | ✅ Online |
| 22 database-migraties, 11+ models, soft-delete, media-library | ✅ Productie-grade |
| Surinaamse overheidsschalen (S01-S18, 12 treden, vanaf SRD 9.000) | ✅ Geseed |
| STCW-certificaat-tracking met automatische vervalwaarschuwingen | ✅ Klaar |

**Geschatte ontwikkel-uren tot nu toe:** 200 uur senior werk.

---

## 2. Drie implementatie-pakketten

### Overzicht in één oogopslag

| | 🟢 **Demo** | 🟡 **Pilot** | 🔴 **Productie** |
|---|---|---|---|
| **Voor wie** | Demo / proof-of-concept | 30-100 medewerkers, 6-maand pilot | Volledige uitrol MAS, 24/7 |
| **Eigen domein** | ❌ | ✅ | ✅ |
| **HTTPS / SSL** | ✅ (gratis) | ✅ | ✅ |
| **Automatische e-mail (verlof/certificaten)** | ❌ | ✅ | ✅ |
| **Off-site back-ups** | ❌ | ✅ (dagelijks) | ✅ (dagelijks + extra) |
| **Error-tracking** | ❌ | ✅ | ✅ |
| **Uptime-monitoring** | ❌ | ✅ | ✅ (24/7 met SMS-alert) |
| **High-availability (HA)** | ❌ | ❌ | ✅ (2 servers) |
| **SMS-notificaties** | ❌ | ❌ | ✅ |
| **Status-page voor gebruikers** | ❌ | ❌ | ✅ |
| **Helpdesk-tool** | ❌ | ❌ | ✅ |
| **Maandelijkse infra-kosten** | **SRD 0** | **SRD 900** (~$25) | **SRD 8.500** (~$236) |
| **Eenmalige setup-kosten** | SRD 0 | **SRD 180.000** | **SRD 360.000** |

---

## 3. Pakket-opbouw in detail

### 🟢 Pakket Demo — SRD 0/maand

Voor demonstratie en evaluatie. Geen eigen domein, geen e-mail, geen back-ups.

| Component | Aanbieder | SRD/maand | $/maand |
|-----------|-----------|-----------|---------|
| Backend hosting | Fly.io free tier (auto-stop) | 0 | $0 |
| Frontend hosting | Vercel Hobby | 0 | $0 |
| Database | Fly Postgres free 1GB | 0 | $0 |
| File-storage | Fly volume 1GB | 5 | $0.15 |
| **Totaal infra** | | **SRD 5** | **$0.15** |

**Beperkingen:** cold-start van 5-10 sec na inactiviteit, geen e-mail, geen eigen domein, geen automatische back-ups.

---

### 🟡 Pakket Pilot — SRD 900/maand + SRD 180.000 eenmalig

Voor een 6-maand pilot of permanente situatie tot ~100 medewerkers.

#### Maandelijkse infra-kosten

| Component | Aanbieder | SRD/maand | $/maand |
|-----------|-----------|-----------|---------|
| Backend hosting (always-on) | Fly.io shared-cpu-1x | 180 | $5 |
| Frontend hosting | Vercel Hobby (of Cloudflare Pages) | 0 | $0 |
| Database (managed, met back-ups) | Neon Launch (10 GB) | 685 | $19 |
| File-storage (S3-compatibel) | Cloudflare R2 (5 GB) | 5 | $0.15 |
| Transactional e-mail | Resend (free tier 3K mails/mo) | 0 | $0 |
| Eigen domein (.com of .sr) | Cloudflare Registrar | 30 | $0.85 |
| Error-tracking | Sentry free | 0 | $0 |
| Uptime-monitoring | UptimeRobot free | 0 | $0 |
| **Totaal infra** | | **SRD 900** | **$25** |

#### Eenmalige setup-kosten

| Item | SRD |
|------|-----|
| Build-fee (200 uur senior dev × SRD 1.500) | 300.000 |
| Korting voor parastatale klant (-40%) | -120.000 |
| Datamigratie van Excel (8 uur × SRD 1.500) | 12.000 |
| Pilot-training HR + admins (2 dagdelen × SRD 4.000) | 8.000 |
| Eindgebruiker-handleiding (PDF + portal-help) | 5.000 |
| AVG/persoonsgegevens-register opstellen | 15.000 |
| Configuratie eigen domein + SSL | 2.000 |
| **Subtotaal eenmalig** | **SRD 222.000** |
| Pakket-korting (eerste contract) | -42.000 |
| **Eenmalig totaal** | **SRD 180.000** |

#### Wat krijgt MAS?
- Volledige toegang tot admin-paneel en medewerkersportaal
- Eigen domein `hris.mas.sr` of `mas-hris.com`
- Automatische e-mailnotificaties bij verlofaanvragen, certificaat-vervalwaarschuwingen, password-resets
- Dagelijkse off-site database-back-ups
- Real-time error-tracking en uptime-monitoring
- 6 maand inbegrepen support (zie sectie 5)

---

### 🔴 Pakket Productie — SRD 8.500/maand + SRD 360.000 eenmalig

Voor volledige uitrol MAS met 24/7-beschikbaarheid, HA, SMS-notificaties en eindgebruiker-status-page.

#### Maandelijkse infra-kosten

| Component | Aanbieder | SRD/maand | $/maand |
|-----------|-----------|-----------|---------|
| Backend hosting HA (2 machines + load-balancer) | Fly.io shared-cpu-2x × 2 | 1.080 | $30 |
| Frontend hosting | Vercel Pro (team-account) | 720 | $20 |
| Database HA (2 GB RAM) | DigitalOcean Managed DB | 1.800 | $50 |
| Read-replica voor rapportages | DigitalOcean Read Replica | 900 | $25 |
| File-storage (S3-compatibel, 50 GB) | Cloudflare R2 | 36 | $1 |
| Transactional e-mail (50K mails/mo) | Resend Pro | 720 | $20 |
| Eigen domein + Cloudflare CDN | Cloudflare Registrar | 30 | $0.85 |
| Error-tracking | Sentry Team plan | 936 | $26 |
| Uptime-monitoring + status-page | Better Stack | 864 | $24 |
| Off-site back-ups (extra laag) | SimpleBackups | 685 | $19 |
| Redis (sessies + queue performance) | Upstash | 180 | $5 |
| SMS-gateway (kritieke alerts ~200/mo) | Twilio | 540 | $15 |
| Helpdesk-tool | Plain.com / Linear free | 0 | $0 |
| 2FA voor admins (TOTP-plugin) | Filament-plugin (gratis) | 0 | $0 |
| **Totaal infra** | | **SRD 8.491** | **$236** |

#### Eenmalige setup-kosten

| Item | SRD |
|------|-----|
| Build-fee (200 uur senior dev × SRD 1.500) | 300.000 |
| Migratie van Pilot naar HA-architectuur (24 uur) | 36.000 |
| Volledige datamigratie van Excel/papier (16 uur) | 24.000 |
| Training HR-team + alle afdelingshoofden (4 dagdelen) | 16.000 |
| Maritieme module configuratie (STCW + watchkeeping) | 30.000 |
| AVG/compliance-audit + persoonsgegevens-register | 25.000 |
| Penetration-test door externe partij (vóór go-live) | 60.000 |
| Status-page setup + helpdesk-tool integratie | 10.000 |
| Eindgebruiker-training (onsite, 2 dagen) | 30.000 |
| Documentatie + training-video's | 15.000 |
| 2FA-implementatie voor alle admins | 8.000 |
| Configuratie SMS-gateway + Twilio-account MAS | 6.000 |
| **Subtotaal eenmalig** | **SRD 560.000** |
| Pakket-korting (lange-termijn contract) | -200.000 |
| **Eenmalig totaal** | **SRD 360.000** |

#### Wat krijgt MAS?
- Alles uit Pakket Pilot, plus:
- 99.9% uptime-SLA (max 8 uur down per jaar)
- HA-database met automatische failover
- Read-replica voor zware rapportages (geen impact op productie-DB)
- 24/7 SMS-alerts bij incidenten
- Publiek toegankelijke status-page op `status.mas-hris.com`
- AVG-compliance audit-rapport
- Penetration-test rapport
- Helpdesk-tool voor ticket-management
- Onsite training voor HR + alle afdelingshoofden

---

## 4. Aanbevolen 3-jaar contract

In plaats van losse pakket-aankopen, biedt het volgende contract de beste prijs en voorspelbaarheid voor MAS:

| Jaar | Activiteiten | Bedrag (SRD) | Bedrag (USD) |
|------|--------------|--------------|--------------|
| **Jaar 1** | Setup Pakket Pilot + 12 maand support + training + datamigratie + 2 nieuwe modules (CAO-koppeling, mobiel-responsief portaal) | **SRD 400.000** | ~$11.000 |
| **Jaar 2** | Onderhoud + upgrade naar Pakket Productie + 1 grote nieuwe module (voorraadbeheer of recruitment) | **SRD 200.000** | ~$5.500 |
| **Jaar 3** | Onderhoud + 1 nieuwe module (BI-dashboard of performance-management) + AVG-audit | **SRD 180.000** | ~$5.000 |
| **3-jaar contract totaal** | | **SRD 780.000** | **~$21.500** |

*Infrastructuur-kosten worden apart gefactureerd via een rekening op naam van MAS (Fly.io / Vercel / Neon / etc.). MAS blijft eigenaar van alle accounts en data.*

---

### Wat zit in het 3-jaar contract?

| Categorie | Inbegrepen |
|-----------|------------|
| **Build & deploy** | Volledige codebase + deploy-pipeline + alle reeds gebouwde features |
| **Hosting-configuratie** | Fly + Vercel + database + storage opgezet en getuned |
| **Onderhoud** | Library-updates, security-patches, OS-updates, framework-upgrades |
| **Bug-fixes** | Onbeperkt voor bestaande functionaliteit |
| **Support** | E-mail / WhatsApp binnen 1 werkdag; kritieke incidenten binnen 4 uur |
| **Nieuwe modules** | 4 grote modules verdeeld over 3 jaar (CAO, voorraad, mobiel, BI of performance) |
| **Training** | HR-team + alle afdelingshoofden, in jaar 1; refresher in jaar 2 |
| **Documentatie** | Alle gebruikershandleidingen + training-video's |
| **Compliance** | AVG-register + audit-rapporten + jaarlijkse pen-test (jaar 2 en 3) |

---

## 5. Vergelijking met commerciële alternatieven

| Oplossing | 3-jaar kosten (50 medewerkers) | Maritieme functionaliteit | Surinaamse CAO-koppeling | Lokaal eigenaarschap |
|-----------|---------------------------------|----------------------------|---------------------------|----------------------|
| **MAS-HRIS (dit voorstel)** | **SRD 780.000 + SRD 200.000 infra = ~SRD 980.000** | ✅ Volledig (STCW, watchkeeping) | ✅ Op maat | ✅ MAS bezit code |
| BambooHR (USD 12 per medewerker per maand) | SRD 720.000 | ❌ | ❌ | ❌ |
| Workday (enterprise tier) | SRD 1.500.000+ | ❌ | ❌ | ❌ |
| AFAS Personeel | SRD 900.000 | ❌ | ⚠️ Nederlandse CAO | ❌ |
| Sage HR | SRD 600.000 | ❌ | ❌ | ❌ |

**MAS-voordeel:** vergelijkbare 3-jaar kosten als BambooHR / AFAS, maar MAS krijgt:
- Eigenaarschap van de code (geen vendor-lock-in)
- Maritieme module die nergens anders bestaat
- Surinaamse parastatale CAO-koppeling
- Lokale support in Suriname (zelfde tijdzone, Nederlands + Surinaams Nederlands)
- Volledige customisatie mogelijk

---

## 6. 5-jaar TCO (Total Cost of Ownership)

| Jaar | Developer (SRD) | Infra (SRD) | Eenmalige extra's (SRD) | Totaal jaar (SRD) |
|------|------------------|--------------|--------------------------|-------------------|
| Jaar 1 | 400.000 | 11.000 (pilot) | 0 | **411.000** |
| Jaar 2 | 200.000 | 102.000 (productie) | 60.000 (pen-test) | **362.000** |
| Jaar 3 | 180.000 | 102.000 | 60.000 (pen-test) | **342.000** |
| Jaar 4 | 150.000 | 110.000 (groei) | 30.000 | **290.000** |
| Jaar 5 | 150.000 | 120.000 | 30.000 | **300.000** |
| **5-jaar totaal** | **SRD 1.080.000** | **SRD 445.000** | **SRD 180.000** | **SRD 1.705.000** |

Omgerekend: ~$47.000 over 5 jaar voor een volledig op MAS afgestemde HRIS, eigendom van MAS, met maritieme functionaliteit.

---

## 7. Betalingsvoorwaarden (voorstel)

| Moment | Bedrag jaar 1 (SRD) |
|--------|---------------------|
| Bij ondertekening contract | 100.000 (25%) |
| Na livegang (binnen 6 weken na start) | 150.000 (37,5%) |
| Bij oplevering CAO-module | 75.000 (19%) |
| Bij oplevering mobiel-responsive | 75.000 (19%) |
| **Totaal jaar 1** | **400.000** |

**Jaren 2 en 3:** kwartaalfacturen vooraf (SRD 50.000 / 45.000 per kwartaal).

**Infrastructuur-kosten:** rechtstreeks gefactureerd door providers aan MAS (gemiddeld SRD 8-10K/mo).

---

## 8. Wat MAS moet leveren

Om het project succesvol te maken, vraagt dit voorstel het volgende van MAS:

1. **Single point of contact** binnen HR voor goedkeuringen en feedback (~4 uur/week jaar 1, ~2 uur/week daarna)
2. **Toegang tot bestaande personeelsdata** (Excel-bestanden, dossiers) voor migratie
3. **Beschikbaarheid HR-team** voor 4 dagdelen training in week 8-10
4. **Akkoord op AVG/persoonsgegevens-beleid** vóór livegang
5. **Goedkeuring CAO-bedragen** door personeelsdirecteur vóór CAO-modulesluit
6. **Aanlevering officiële MAS-templates** (briefpapier, e-mailhandtekening) voor de UI

---

## 9. Volgende stappen

1. **Akkoord in principe** op pakket en bedrag (1 week)
2. **Contract-opstelling** door MAS-juridische dienst (2 weken)
3. **Ondertekening + aanbetaling** (1 week)
4. **Kick-off meeting** met MAS HR + Directie (week 4)
5. **Datamigratie + livegang Pilot** (week 8)
6. **Eerste echte gebruikers actief** (week 10)

---

*Dit voorstel is geldig tot 30 juni 2026. Voor vragen: giorgioasimson@gmail.com.*

*Maritieme Autoriteit Suriname — "Striving for Excellence"*
