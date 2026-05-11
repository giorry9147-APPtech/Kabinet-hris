<div class="fi-kabinet-login-root">
    <style>
        :root {
            --kabinet-green: #1F5E3A;
            --kabinet-green-dark: #143F26;
            --kabinet-green-deep: #0a2415;
            --kabinet-gold: #D4A017;
            --kabinet-gold-soft: #E8C156;
        }
        body { overflow-x: hidden; }
        .fi-simple-main-ctn,
        .fi-simple-main { all: revert; }
        .fi-simple-main { background: transparent !important; box-shadow: none !important; max-width: none !important; padding: 0 !important; margin: 0 !important; }
        .fi-simple-layout > div[class*="absolute"][class*="end-0"] { display: none !important; }
        .fi-simple-page { display: block !important; }
        .fi-simple-page > section { display: block !important; }
        .fi-simple-header { display: none !important; }

        .fi-kabinet-login-root {
            position: fixed;
            inset: 0;
            display: grid;
            grid-template-columns: 1fr;
            background: #f8fafc;
            z-index: 50;
            overflow-y: auto;
        }
        @media (min-width: 1024px) {
            .fi-kabinet-login-root {
                grid-template-columns: 1.05fr 1fr;
            }
        }
        .fi-kabinet-left {
            position: relative;
            background:
                radial-gradient(circle at 80% 10%, rgba(212, 160, 23, 0.18) 0, transparent 45%),
                radial-gradient(circle at 10% 90%, rgba(31, 94, 58, 0.55) 0, transparent 55%),
                linear-gradient(135deg, var(--kabinet-green) 0%, var(--kabinet-green-dark) 55%, var(--kabinet-green-deep) 100%);
            color: white;
            padding: 3rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            min-height: 40vh;
        }
        @media (min-width: 1024px) {
            .fi-kabinet-left {
                padding: 4rem 4.5rem;
                min-height: 100vh;
            }
        }
        .fi-kabinet-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: linear-gradient(120deg, transparent 49.5%, rgba(212, 160, 23, 0.08) 49.5%, rgba(212, 160, 23, 0.08) 50.5%, transparent 50.5%);
            opacity: 0.4;
            pointer-events: none;
        }
        .fi-kabinet-logo-card {
            background: white;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            display: inline-flex;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            width: fit-content;
            position: relative;
            z-index: 1;
        }
        .fi-kabinet-logo-card img {
            height: 5rem;
            width: auto;
            display: block;
        }
        @media (min-width: 1024px) {
            .fi-kabinet-logo-card img { height: 5.5rem; }
        }
        .fi-kabinet-eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.28em;
            font-size: 0.75rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 2.5rem;
            position: relative;
            z-index: 1;
        }
        .fi-kabinet-title {
            font-size: 1.85rem;
            line-height: 1.15;
            font-weight: 800;
            margin-top: 0.75rem;
            letter-spacing: -0.02em;
            position: relative;
            z-index: 1;
        }
        @media (min-width: 1024px) {
            .fi-kabinet-title { font-size: 2.75rem; }
        }
        .fi-kabinet-lede {
            color: rgba(255, 255, 255, 0.78);
            margin-top: 1rem;
            line-height: 1.6;
            max-width: 36rem;
            position: relative;
            z-index: 1;
        }
        .fi-kabinet-grid {
            margin-top: 2rem;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.55rem;
            position: relative;
            z-index: 1;
        }
        @media (min-width: 640px) {
            .fi-kabinet-grid { grid-template-columns: repeat(4, 1fr); }
        }
        @media (min-width: 1024px) {
            .fi-kabinet-grid { grid-template-columns: repeat(2, 1fr); margin-top: 2.5rem; }
        }
        .fi-kabinet-pill {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.65rem 0.95rem;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.92);
            transition: all 0.2s ease;
        }
        .fi-kabinet-pill:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(212, 160, 23, 0.4);
        }
        .fi-kabinet-pill .dot {
            width: 0.45rem;
            height: 0.45rem;
            border-radius: 9999px;
            background: var(--kabinet-gold);
            flex-shrink: 0;
            box-shadow: 0 0 8px rgba(212, 160, 23, 0.6);
        }
        .fi-kabinet-foot {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1.25rem;
            margin-top: 2.5rem;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }
        .fi-kabinet-foot strong { color: rgba(255, 255, 255, 0.8); font-weight: 600; }

        .fi-kabinet-right {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
            background: white;
            min-height: 60vh;
        }
        @media (min-width: 1024px) {
            .fi-kabinet-right { min-height: 100vh; }
        }
        .fi-kabinet-card {
            width: 100%;
            max-width: 26rem;
        }
        .fi-kabinet-right-eyebrow {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--kabinet-gold);
            letter-spacing: 0.25em;
            text-transform: uppercase;
            margin-bottom: 0.75rem;
        }
        .fi-kabinet-right-title {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--kabinet-green);
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }
        @media (min-width: 1024px) {
            .fi-kabinet-right-title { font-size: 2.25rem; }
        }
        .fi-kabinet-right-sub {
            color: rgb(71, 85, 105);
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }
        .fi-kabinet-hint {
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: rgb(100, 116, 139);
            text-align: center;
            line-height: 1.5;
        }
        .fi-kabinet-hint strong {
            color: var(--kabinet-green);
            font-weight: 600;
        }
        .dark .fi-kabinet-right { background: rgb(17, 24, 39); }
        .dark .fi-kabinet-right-sub { color: rgb(148, 163, 184); }
        .dark .fi-kabinet-right-title { color: var(--kabinet-gold-soft); }
        .dark .fi-kabinet-hint { color: rgb(148, 163, 184); }
        .dark .fi-kabinet-hint strong { color: var(--kabinet-gold-soft); }
    </style>

    {{-- Linker paneel: branding --}}
    <aside class="fi-kabinet-left">
        <div>
            <div class="fi-kabinet-logo-card">
                <img src="{{ asset('kabinetlogo.png') }}" alt="Kabinet van de President van de Republiek Suriname" />
            </div>

            <div class="fi-kabinet-eyebrow">Republiek Suriname</div>
            <h1 class="fi-kabinet-title">
                HR-portaal<br />
                Kabinet van de President
            </h1>
            <p class="fi-kabinet-lede">
                Adminpaneel voor het Kabinet van de President van de Republiek Suriname —
                medewerkers, contracten, presidentiële beschikkingen, vergaderingen,
                besluiten en werkafspraken in één omgeving.
            </p>

            <div class="fi-kabinet-grid">
                <div class="fi-kabinet-pill"><span class="dot"></span> Vergaderingen</div>
                <div class="fi-kabinet-pill"><span class="dot"></span> Resoluties</div>
                <div class="fi-kabinet-pill"><span class="dot"></span> Contracten</div>
                <div class="fi-kabinet-pill"><span class="dot"></span> Certificaten</div>
                <div class="fi-kabinet-pill"><span class="dot"></span> Werkafspraken</div>
                <div class="fi-kabinet-pill"><span class="dot"></span> Notulen</div>
                <div class="fi-kabinet-pill"><span class="dot"></span> Salaris</div>
                <div class="fi-kabinet-pill"><span class="dot"></span> Audit-log</div>
            </div>
        </div>

        <div class="fi-kabinet-foot">
            <span>© {{ date('Y') }} Kabinet van de President van de Republiek Suriname</span>
            <span><strong>Vertrouwelijk</strong> — alleen voor geautoriseerd personeel</span>
        </div>
    </aside>

    {{-- Rechter paneel: formulier --}}
    <section class="fi-kabinet-right">
        <div class="fi-kabinet-card">
            <p class="fi-kabinet-right-eyebrow">Admin-inloggen</p>
            <h2 class="fi-kabinet-right-title">Welkom terug</h2>
            <p class="fi-kabinet-right-sub">
                Gebruik je Kabinet-accountgegevens om door te gaan naar het beheerpaneel.
            </p>

            <x-filament-panels::form id="form" wire:submit="authenticate">
                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </x-filament-panels::form>

            <p class="fi-kabinet-hint">
                Problemen met inloggen?<br />
                Neem contact op met <strong>ICT — Kabinet van de President</strong>.
            </p>
        </div>
    </section>
</div>
