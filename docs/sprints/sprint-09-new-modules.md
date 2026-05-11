# Sprint 8 — Nieuwe HR-modules

> **Doel**: HRIS uitbreiden met de 4 modules die elke volwaardige HR-suite heeft maar nu nog ontbreken.
>
> **Effort**: 2-3 weken (2-4 dagen per module).
>
> **Blocker**: HR moet per module beslissen welke business-rules gelden bij MAS.

## Module 8A — Recruitment / Vacatures

> Workflow van openstelling vacature → sollicitatie → interview → aanname.

### Schema (3 nieuwe tabellen)

- `vacancies` — id, position_id, title, description, opens_at, closes_at, status (draft/open/closed/cancelled), num_hires
- `applicants` — id, first_name, last_name, email, phone, cv_path (medialibrary), source (website/referral/etc.)
- `applications` — id, vacancy_id, applicant_id, status (new/screening/interview/offer/hired/rejected), notes, decided_at, decided_by

### Tasks

- [ ] 3 migrations
- [ ] 3 modellen + relaties
- [ ] 3 Filament resources met workflow-acties (status-transitions: "Naar interview", "Aangenomen", "Afwijzen")
- [ ] **Public sollicitatie-pagina** in Next.js (`/vacatures` + `/vacatures/[id]`) met form (CV-upload)
- [ ] Bij "Aangenomen" → optie om sollicitant direct als Employee te promoten (skipped CV → dossier)
- [ ] Filament dashboard widget: "Open vacatures: X"

### Vragen voor HR

- Mag de vacature-pagina publiek zijn (op `/vacatures`)?
- Wie keurt sollicitanten goed — alleen HR, of ook dept_head van de afdeling?

## Module 8B — Trainingen & Ontwikkeling

> Bijhouden van interne/externe trainingen, kosten, certificaten ontvangen.

### Schema (3 nieuwe tabellen)

- `training_programs` — id, code, name, type (intern/extern), provider, duration_hours, default_cost, leads_to_certificate_type_id
- `training_enrollments` — id, employee_id, training_program_id, planned_date, completed_at, status (planned/completed/cancelled), cost_actual, notes
- `training_budgets` — id, year, employee_id (of org_unit_id voor afdelings-budget), amount, spent

### Tasks

- [ ] 3 migrations + modellen
- [ ] Filament resources voor trainingen + inschrijvingen
- [ ] Bij "completed" → automatisch een Certificate aanmaken als training gekoppeld is aan een cert_type
- [ ] Budget-tracking widget op dashboard: "Resterend trainingsbudget 2026: SRD X"
- [ ] Self-service: medewerker ziet eigen trainings-historiek + kan verzoek indienen

### Vragen voor HR

- Houdt MAS budget per medewerker bij of per afdeling?
- Welke maritime-trainings zijn standaard verplicht (BHV, STCW renewals)?

## Module 8C — Performance / Beoordelingen

> Jaarlijkse functioneringsgesprek + beoordeling, eventueel met SMART-doelen.

### Schema (3 nieuwe tabellen)

- `appraisal_cycles` — id, year, name (bv. "2026 Mid-year"), period_start, period_end, status
- `appraisals` — id, cycle_id, employee_id, evaluator_id (= dept_head), self_assessment, manager_assessment, overall_rating (1-5), submitted_at, signed_at
- `appraisal_goals` — id, appraisal_id, description, target_date, status (open/met/missed), notes

### Tasks

- [ ] 3 migrations + modellen
- [ ] Filament resource voor cycles + appraisals
- [ ] **Self-assessment workflow** in Next.js portal: medewerker vult zelf-evaluatie in → submit → dept_head krijgt notificatie → vult eigen evaluatie in → finalize → medewerker tekent
- [ ] PDF-export van afgeronde beoordeling (voor papieren dossier)
- [ ] Rapport: "Wie heeft nog geen beoordeling 2026?"

### Vragen voor HR

- Welke schaal? 1-5 sterren / A-E / vrij tekstveld / SMART-doelen?
- Tekent medewerker daadwerkelijk digitaal of papieren handtekening?

## Module 8D — Onboarding / Offboarding

> Checklist per nieuwe hire / bij uitdiensttreding zodat niets vergeten wordt.

### Schema (2 nieuwe tabellen)

- `checklist_templates` — id, name, type (onboarding/offboarding), items (JSON: [{label, role_responsible, days_offset}])
- `checklists` — id, template_id, employee_id, started_at, completed_at, items_state (JSON: [{label, done, done_by, done_at}])

### Tasks

- [ ] 2 migrations + modellen
- [ ] Default templates seeden:
  - **Onboarding**: ICT account aanmaken, laptop + telefoon uitgeven, BHV-cursus inplannen, foto maken, contract tekenen, payroll info verzamelen, badge maken (12+ items)
  - **Offboarding**: assets terug, accounts blokkeren, eind-gesprek, ARBO-uitschrijving, salaris-eindafrekening (8+ items)
- [ ] Filament resource: HR start checklist bij hire/exit, deelt acties uit aan dept_head/ICT
- [ ] Status-bar: "8/12 onboarding-stappen klaar voor MAS-1023"
- [ ] Notificatie bij overdue items

### Vragen voor HR

- Wie doet wat in de onboarding? (HR alleen, of ook ICT/Facility)
- Wettelijke termijnen bij uitdiensttreding (Suriname-arbeidswet)?

## Algemene tasks per module

Voor elk: migrations + models + Filament resources + tests + docs. Per module reken ~3-5 dagen.

## Volgorde-suggestie

1. **Onboarding/offboarding** eerst — meest tastbare quick-win voor HR
2. **Trainingen** tweede — link met bestaande certificaten-module
3. **Recruitment** derde — vereist publieke pagina (extra zorg ivm spam)
4. **Performance** vierde — meest gevoelig, vereist meeste discussie

## Wat NIET in deze sprint (parkeren tot later)

- Talent-management / opvolgingsplanning
- Compensation-benchmarking
- Engagement-surveys
- Workforce-planning / scenario-modellering
