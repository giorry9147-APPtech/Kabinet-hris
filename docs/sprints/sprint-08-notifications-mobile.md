# Sprint 7 — E-mail notificaties + mobile-friendly portal

> **Doel**: Naast Filament-bel ook automatische e-mails sturen + portal werkt prettig op mobiel.
>
> **Effort**: 2-3 dagen.
>
> **Blocker**: SMTP-credentials (mailprovider keuze).

## Vragen vóóraf

- Heeft MAS al een mailserver met SMTP-toegang?
- Zo nee: SendGrid (gratis tot 100 mails/dag), Postmark, AWS SES, of Mailgun?
- Mag het verzonden vanaf `hr-noreply@mas.sr` of een ander adres?
- Welke notificaties moeten echt naar mail (niet alleen Filament-bel)?

## Deel A — E-mail notificaties

### Use cases

| Trigger | Naar wie | Channel |
|---|---|---|
| Nieuwe verlofaanvraag ingediend | dept_head + HR | Mail + Filament-bel |
| Verlof goedgekeurd | aanvrager | Mail + Filament-bel |
| Verlof afgewezen | aanvrager (met reden) | Mail + Filament-bel |
| Certificaat verloopt < 60 dagen | medewerker + HR | Mail (1× per cert) + Filament-bel |
| Certificaat verlopen | HR | Mail (urgent) |
| Asset toegewezen | medewerker | Mail (met retour-instructie) |
| Account aangemaakt | nieuwe medewerker | Welkomst-mail met set-password link |

### Tasks

- [ ] **Mailable classes** in `app/Mail/`:
  - `LeaveRequestSubmitted` — naar dept_head + HR
  - `LeaveRequestDecided` — naar aanvrager (variant per status: approved/rejected)
  - `CertificateExpiringSoon` — naar medewerker + HR
  - `WelcomeNewEmployee` — naar nieuwe user
- [ ] **Blade-templates** in `resources/views/emails/` (MAS-stijl: blauw header, rode "Striving for Excellence" tagline)
- [ ] **Notification classes** voor de complexe gevallen (multi-channel: mail + database)
- [ ] **Config** in `.env`:
  ```env
  MAIL_MAILER=smtp
  MAIL_HOST=smtp.sendgrid.net
  MAIL_PORT=587
  MAIL_USERNAME=apikey
  MAIL_PASSWORD=<sendgrid-api-key>
  MAIL_ENCRYPTION=tls
  MAIL_FROM_ADDRESS=hr-noreply@mas.sr
  MAIL_FROM_NAME="MAS HRIS"
  ```
- [ ] **Triggers** in code:
  - `LeaveRequestObserver::created()` → dispatch `LeaveRequestSubmitted` mail
  - In `LeaveRequestResource` `approve`/`reject` actions → dispatch `LeaveRequestDecided`
  - In `certs:check-expiry` command → al Filament-notif, voeg mail-channel toe per medewerker
- [ ] **Queue alle mails** (`->onQueue('mail')`) zodat web-requests snel blijven
- [ ] **MailLog**: Spatie `mail-log` package overwegen voor compliance (wie kreeg welke mail wanneer)

### Mail-template structuur (voorbeeld)

```blade
{{-- resources/views/emails/leave-decided.blade.php --}}
<x-mail::message>
# Hallo {{ $employee->first_name }},

Je verlofaanvraag van **{{ $request->start_date->format('d-m-Y') }} t/m {{ $request->end_date->format('d-m-Y') }}** is

@if ($request->status === 'approved')
**goedgekeurd** ✅
@else
**afgewezen** ❌
@if ($request->decision_reason)
> {{ $request->decision_reason }}
@endif
@endif

Beslist door: {{ $request->approver->name }}

<x-mail::button :url="$portalUrl">Bekijk in portaal</x-mail::button>

Met vriendelijke groet,
**MAS HR**

*Striving for Excellence*
</x-mail::message>
```

### Acceptance criteria — mail

- [ ] Verlof goedkeuren → Marciano krijgt mail binnen 1 minuut
- [ ] Mail bevat MAS-branding + portal-link
- [ ] `php artisan certs:check-expiry` stuurt mails naast database-notif
- [ ] Mailtron of Mailpit lokaal voor testen, SendGrid (of vergelijkbaar) productie

## Deel B — Mobile-friendly Next.js portal

### Huidige problemen op mobiel

- Sidebar is altijd 240px breed → eet halve mobile-screen
- Tables (verlof, salaris) hebben veel kolommen → horizontaal scrollen onhandig
- Filament admin is al responsive (out-of-the-box) — geen werk nodig
- Login form werkt prima op mobile

### Tasks

- [ ] **Sidebar → drawer op < 768px**:
  - Hamburger-knop in topbar
  - Tailwind `md:flex hidden` voor desktop, drawer-overlay op mobile
  - Backdrop-click sluit drawer
- [ ] **Tables responsive**:
  - Mobile: card-layout per row (titel + 2-3 key fields visible, rest in expandable)
  - Of: scroll-x met sticky first column
  - Verlof, salaris, certificaten, assets — alle 4 aanpassen
- [ ] **Touch-friendly**:
  - Buttons min 44×44px (Apple guidelines)
  - Form inputs niet te smal
  - Geen hover-only interacties (touch heeft geen hover)
- [ ] **Viewport meta tag** check in `layout.tsx`
- [ ] **Test op echte devices** (niet alleen Chrome devtools): iPhone SE breedte (375px), iPad portrait (768px)

### Componenten om te maken

- `<MobileDrawer>` — slidet vanaf links, achter backdrop
- `<ResponsiveTable>` — switcht naar card-mode onder breakpoint
- `<MobileTopbar>` — met hamburger + logo + avatar

### Acceptance criteria — mobile

- [ ] Login + dashboard + alle 5 portal-pages werken op iPhone-breedte (375px) zonder horizontaal scrollen
- [ ] Sidebar opent/sluit met touch
- [ ] Verlof-aanvraag indienen lukt op mobile met datepickers
- [ ] Lighthouse mobile score > 90 voor performance + accessibility

## Wat NIET in deze sprint

- Push-notificaties (browser of native app) — overkill, mail volstaat
- SMS-notificaties — duur en MAS heeft het waarschijnlijk niet nodig
- Dark mode — komt in nice-to-have lijst
