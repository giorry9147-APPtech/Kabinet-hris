# Sprint 9 — Maritime-specifieke extensies

> **Doel**: Features die uniek zijn voor MAS als maritieme autoriteit en die een generiek HR-pakket nooit zal hebben.
>
> **Effort**: 1-2 weken.
>
> **Blocker**: Vessel-data + huidige roosters van VTS/Loodsdienst nodig.

## Wat is anders bij MAS

MAS is geen kantoor-HR. Operations werkt 24/7 (VTS, Loodsdienst), met:
- STCW-certificaten die wettelijk verplicht zijn voor specifieke functies
- Wisselende ploegen en watchkeeping-roosters
- Twee fysieke locaties: Paramaribo + Nickerie
- Soms toewijzingen aan specifieke vessels of zones

Dit zijn de extra features die dat ondersteunen.

## A. Locatie Paramaribo / Nickerie

### Waarom

Veel functies zijn duplicaten op beide locaties (VTS Paramaribo + VTS Nickerie). Nu zit dat impliciet in de naam ("Vessel Traffic Centre"). Beter expliciet maken.

### Tasks

- [ ] Nieuwe `locations` tabel: id, code, name, address, is_active
- [ ] Seed: Paramaribo (HQ), Nickerie
- [ ] `employees` tabel: `location_id` FK toevoegen (waar deze persoon primair werkt)
- [ ] `positions` tabel: `location_id` (nullable; null = beide locaties)
- [ ] Filament filters: "Per locatie" op employees + positions
- [ ] Optioneel: aparte permissie `locations.manage` voor lokale dept_heads (Nickerie-hoofd ziet alleen Nickerie-medewerkers)

## B. STCW-certificaten gekoppeld aan functie-vereisten

### Waarom

Voor sommige functies (Loods, VTS Operator, Survey Inspecteur) zijn bepaalde STCW-certs wettelijk verplicht. Nu hebben we losse `certificates` per medewerker zonder check of vereiste certs aanwezig zijn.

### Tasks

- [ ] Nieuwe pivot-tabel `position_required_certificates`:
  ```sql
  position_id, certificate_type_id, is_mandatory (bool), notes
  ```
- [ ] Filament UI bij Position-edit: tab "Vereiste certificaten" — multi-select uit certificate_types
- [ ] **Compliance-check** functie: voor elke medewerker, geef terug welke vereiste certs ontbreken/verlopen voor hun huidige functie
- [ ] Dashboard widget "X medewerkers non-compliant" (rood) — link naar lijst
- [ ] Bij `LeaveRequest` van Loods: waarschuw als BHV/STCW < 30 dagen na verlof verloopt (geen blokker, wel notice)
- [ ] Rapport: "Compliance per afdeling" (% medewerkers met alle vereiste certs)

### Voorbeeld (zou seed-data moeten zijn)

| Functie | Verplichte certs |
|---|---|
| Loods | STCW-BST, STCW-MED, IMO-PILOT, MED-OFF |
| VTS Operator | IMO-VTS, MED-OFF, BHV |
| Port State Officer | STCW-BST, ISPS-PFSO |
| Flag State Inspector | STCW-AFF, ISPS-SSO |

## C. Watchkeeping / Ploegenrooster

### Waarom

VTS-Centrum draait 24/7 in 4-ploegen-systeem (of 3, afhankelijk van bezetting). Nu staan operators als "actief" maar niet wie wanneer dienst heeft.

### Schema

- `shift_patterns` — id, name, cycle_days (bv. 4), shifts (JSON: per dag wie van welke ploeg)
- `shifts` — id, code (A/B/C/D), name (Ochtend/Middag/Nacht), start_time, end_time
- `shift_assignments` — id, employee_id, shift_id, date, status (planned/swapped/sick), notes

### Tasks

- [ ] 3 migrations + modellen
- [ ] Filament resource: rooster-builder voor maand-vooruit
- [ ] Self-service: medewerker ziet eigen rooster komende 30 dagen
- [ ] **Swap-functie**: medewerker A vraagt swap met B → beide bevestigen → dept_head keurt goed
- [ ] **Conflict-check**: niemand 2 shifts op zelfde dag, max X uur per week (CAO-regel)
- [ ] iCal-export per medewerker (importeerbaar in Google Calendar / Outlook)
- [ ] Rapport: "Onderbezetting komende week" — toont dagen met < min. operators

### Vragen voor HR / Ops

- Welke shift-patronen zijn standaard (4-shift cycle? 3?)
- Wat is de minimum-bezetting per shift (1 senior + 2 operators?)
- Mag iemand zelf een swap voorstellen of moet alles via dept_head?

## D. Vessel-koppeling (optioneel)

### Waarom

Bij incidenten of inspecties wil je weten welke MAS-medewerker bij welke vessel was betrokken. Nu zit dat alleen in losse rapport-PDFs.

### Schema (alleen als MAS al een vessel-DB heeft of wil opbouwen)

- `vessels` — id, imo_number, name, flag, type, gross_tonnage, owner
- `vessel_inspections` — id, vessel_id, inspector_employee_id, inspection_date, type (flag_state/port_state), result (pass/fail/conditional), report_path

### Tasks

- [ ] 2 migrations + modellen
- [ ] Filament resource voor vessels (kan ook later — start met handmatige toevoeging)
- [ ] Inspection-resource met file-upload
- [ ] Op employee detail-page: "Recent uitgevoerde inspecties" tab
- [ ] Search-by-vessel: "Welke MAS-mensen waren bij IMO-1234567 betrokken?"

## E. CAO-specifieke verlof-regels (parastatale arbeid)

### Waarom

Standaard 24 dagen werkt voor demo, maar parastatale CAO heeft details:
- Extra dagen op basis van leeftijd
- Extra dagen voor anciënniteit
- Bijzonder verlof bij huwelijk/begrafenis (vaste aantallen)
- Onbetaald verlof aanvraagbaar

### Tasks

- [ ] `leave_policies` tabel: rules per type (vacation/special/etc.) + age/tenure-bonussen
- [ ] Bij verlofaanvraag: backend berekent juiste saldo
- [ ] Self-service portal toont: "Vakantie: 28 dagen (24 + 4 anciënniteit)"
- [ ] Rapport voor finance: "Verlof-voorziening per medewerker per ultimo jaar"

## Volgorde-suggestie

1. **Locatie Paramaribo/Nickerie** eerst — kleine toevoeging, veel waarde voor filtering
2. **STCW-vereisten** tweede — high-impact compliance feature
3. **Watchkeeping** derde — vereist input van Operations team
4. **Vessel-koppeling** vierde — alleen als concrete behoefte
5. **CAO-verlof** vijfde — vereist juridische input

## Wat NIET in deze sprint

- Geheel ATS / inspectie-workflow systeem (PSC inspections via apart pakket)
- Vlootbeheer voor MAS' eigen schepen (Pilot launches, etc.) — apart project
- Cursussysteem (LMS) voor STCW-trainingen — koop/integreer een bestaand systeem
