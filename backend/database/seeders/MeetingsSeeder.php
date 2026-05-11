<?php

namespace Database\Seeders;

use App\Models\ActionItem;
use App\Models\Decision;
use App\Models\Employee;
use App\Models\Meeting;
use Illuminate\Database\Seeder;

/**
 * Demo-vergaderingen, besluiten en werkafspraken voor de Chief of Staff-module.
 * Toont een typische week in het Kabinet — Sergio Akiemboto draait veel
 * presidentiële overleggen, besluit, en delegeert werkafspraken naar zijn staf.
 */
class MeetingsSeeder extends Seeder
{
    public function run(): void
    {
        $byEmp = fn (string $no) => Employee::where('employee_number', $no)->first();

        $sergio = $byEmp('KAB-0001'); // Kabinetschef
        $soraya = $byEmp('KAB-0002'); // Juridisch
        $anand = $byEmp('KAB-0003');  // Communicatie
        $marlinde = $byEmp('KAB-0004'); // Protocol
        $kenrick = $byEmp('KAB-0005'); // HR
        $rianne = $byEmp('KAB-0006');  // Financiën
        $tarun = $byEmp('KAB-0007');   // ICT
        $miguel = $byEmp('KAB-0009');  // Persvoorlichter
        $farah = $byEmp('KAB-0010');   // Beleid
        $quincy = $byEmp('KAB-0011');  // Beveiliging

        if (! $sergio) {
            return;
        }

        // ---- Vergadering 1: Wekelijks overleg met de President (deze week) ----
        $m1 = Meeting::updateOrCreate(
            ['meeting_number' => 'KAB-VERG-2026-W19-PRES'],
            [
                'title' => 'Wekelijks overleg met de President — week 19',
                'type' => 'presidentieel',
                'scheduled_at' => now()->next('Monday')->setTime(9, 0),
                'duration_minutes' => 90,
                'location' => 'Bureau van de President — werkkamer',
                'chair_employee_id' => $sergio->id,
                'status' => 'planned',
                'agenda' => "1. Stand van zaken lopende dossiers\n2. Voorbereiding staatsbezoek juni 2026\n3. Benoemingsvoorstellen adviseurs (3 kandidaten)\n4. Mediacampagne digitalisering\n5. Rondvraag",
                'external_attendees' => null,
                'minutes_status' => 'none',
            ]
        );
        if ($soraya) $m1->attendees()->syncWithoutDetaching([$soraya->id => ['role' => 'participant']]);
        if ($anand) $m1->attendees()->syncWithoutDetaching([$anand->id => ['role' => 'participant']]);
        if ($marlinde) $m1->attendees()->syncWithoutDetaching([$marlinde->id => ['role' => 'note_taker']]);

        // ---- Vergadering 2: Stafvergadering Kabinet (afgelopen vrijdag, met notulen) ----
        $m2 = Meeting::updateOrCreate(
            ['meeting_number' => 'KAB-VERG-2026-W18-STAF'],
            [
                'title' => 'Stafvergadering Kabinet — week 18',
                'type' => 'staf',
                'scheduled_at' => now()->subWeek()->next('Friday')->setTime(10, 0),
                'duration_minutes' => 120,
                'location' => 'Conferentiezaal Kabinet',
                'chair_employee_id' => $sergio->id,
                'status' => 'held',
                'agenda' => "1. Voortgangsrapportages directies\n2. Begrotingsuitvoering Q1\n3. Personeelsmutaties\n4. ICT-modernisering",
                'minutes_status' => 'final',
                'minutes_content' => "Vastgestelde notulen van de stafvergadering. Belangrijkste punten:\n— Q1-begrotingsuitvoering ligt op koers (98% benutting).\n— Akkoord met aantrekking 3 nieuwe adviseurs (zie besluit KAB-BES-2026-014).\n— ICT-modernisering loopt vertraging op door leveranciers.\n— Communicatie persbericht digitalisering uiterlijk 20 mei publiceren.",
                'minutes_signed_by' => 'Kabinetschef S. Akiemboto',
                'minutes_finalized_at' => now()->subWeek()->next('Friday')->addDays(3)->toDateString(),
            ]
        );
        foreach ([$soraya, $anand, $marlinde, $kenrick, $rianne, $tarun] as $emp) {
            if ($emp) $m2->attendees()->syncWithoutDetaching([$emp->id => ['role' => 'participant', 'attended' => true]]);
        }

        // ---- Vergadering 3: Sollicitatiegesprek — aantrekken nieuwe adviseur ----
        $m3 = Meeting::updateOrCreate(
            ['meeting_number' => 'KAB-VERG-2026-SOLL-001'],
            [
                'title' => 'Kennismaking — kandidaat Senior Adviseur Economische Zaken',
                'type' => 'sollicitatie',
                'scheduled_at' => now()->addDays(3)->setTime(14, 0),
                'duration_minutes' => 60,
                'location' => 'Werkkamer Kabinetschef',
                'chair_employee_id' => $sergio->id,
                'status' => 'planned',
                'agenda' => "Kennismakingsgesprek met externe kandidaat (door President voorgedragen).\n- CV bespreken\n- Visie op portefeuille\n- Beschikbaarheid en voorwaarden",
                'external_attendees' => "Dhr. R. Patel — econoom, voorheen Centrale Bank van Suriname (kandidaat)",
                'minutes_status' => 'none',
            ]
        );
        if ($kenrick) $m3->attendees()->syncWithoutDetaching([$kenrick->id => ['role' => 'participant']]);
        if ($farah) $m3->attendees()->syncWithoutDetaching([$farah->id => ['role' => 'participant']]);

        // ---- Vergadering 4: Strategisch overleg (over twee weken) ----
        $m4 = Meeting::updateOrCreate(
            ['meeting_number' => 'KAB-VERG-2026-STRAT-002'],
            [
                'title' => 'Strategisch overleg — uitvoeringsagenda 2e helft 2026',
                'type' => 'strategisch',
                'scheduled_at' => now()->addDays(12)->setTime(9, 30),
                'duration_minutes' => 180,
                'location' => 'Bureau van de President — vergaderzaal',
                'chair_employee_id' => $sergio->id,
                'status' => 'planned',
                'agenda' => "Halfjaarsplanning, prioritering dossiers, resourceallocatie.",
                'external_attendees' => "Mevr. J. Karijomenawi — Min. Buitenlandse Zaken\nDhr. F. Liauw — Bureau Statistiek",
                'minutes_status' => 'none',
            ]
        );
        foreach ([$soraya, $anand, $rianne, $farah, $marlinde] as $emp) {
            if ($emp) $m4->attendees()->syncWithoutDetaching([$emp->id => ['role' => 'participant']]);
        }

        // ---- Besluiten (uit m2 stafvergadering) ----
        Decision::updateOrCreate(
            ['decision_number' => 'KAB-BES-2026-014'],
            [
                'meeting_id' => $m2->id,
                'subject' => 'Aantrekken drie nieuwe adviseurs (economie, juridisch, communicatie)',
                'decision_text' => 'De Kabinetschef wordt gemachtigd om in samenspraak met HR en de relevante directiehoofden drie nieuwe adviseurs aan te trekken: 1) Senior Adviseur Economische Zaken, 2) Adviseur Juridische Zaken, 3) Adviseur Strategische Communicatie. Indicatieve indiensttreding uiterlijk 1 augustus 2026. Selectie via formeel sollicitatietraject; eindbenoeming bij presidentiële beschikking.',
                'decided_at' => now()->subWeek()->next('Friday')->toDateString(),
                'responsible_employee_id' => $sergio->id,
                'deadline' => now()->addMonths(2)->toDateString(),
                'priority' => 'high',
                'status' => 'in_progress',
                'notes' => 'Procesbegeleiding door Hoofd Personeelszaken (KAB-0005). Concept-resoluties klaarmaken voor President.',
            ]
        );

        Decision::updateOrCreate(
            ['decision_number' => 'KAB-BES-2026-015'],
            [
                'meeting_id' => $m2->id,
                'subject' => 'Persbericht digitalisering Kabinet — publicatie uiterlijk 20 mei',
                'decision_text' => 'Hoofd Communicatie stelt persbericht op over voortgang digitalisering Kabinet. Definitieve versie ter goedkeuring aan Kabinetschef vóór 18 mei; publicatie uiterlijk 20 mei 2026.',
                'decided_at' => now()->subWeek()->next('Friday')->toDateString(),
                'responsible_employee_id' => $anand?->id,
                'deadline' => '2026-05-20',
                'priority' => 'urgent',
                'status' => 'open',
            ]
        );

        Decision::updateOrCreate(
            ['decision_number' => 'KAB-BES-2026-016'],
            [
                'meeting_id' => $m2->id,
                'subject' => 'Aanpak vertraging ICT-modernisering',
                'decision_text' => 'Hoofd ICT presenteert binnen 2 weken een herziene planning + mitigatieplan voor leveranciersvertraging.',
                'decided_at' => now()->subWeek()->next('Friday')->toDateString(),
                'responsible_employee_id' => $tarun?->id,
                'deadline' => now()->subWeek()->next('Friday')->addDays(14)->toDateString(),
                'priority' => 'high',
                'status' => 'in_progress',
            ]
        );

        // ---- Werkafspraken (uit besluiten + standalone) ----
        $actions = [
            ['title' => 'Vacaturetekst Senior Adviseur Economische Zaken opstellen', 'assignee' => $kenrick, 'due' => now()->addDays(5), 'prio' => 'high', 'status' => 'in_progress', 'decision' => 'KAB-BES-2026-014'],
            ['title' => 'Vacaturetekst Adviseur Juridische Zaken opstellen', 'assignee' => $kenrick, 'due' => now()->addDays(7), 'prio' => 'normal', 'status' => 'open', 'decision' => 'KAB-BES-2026-014'],
            ['title' => 'Concept presidentiële beschikkingen voor 3 benoemingen voorbereiden', 'assignee' => $soraya, 'due' => now()->addDays(21), 'prio' => 'high', 'status' => 'open', 'decision' => 'KAB-BES-2026-014'],
            ['title' => 'Persbericht digitalisering eerste concept', 'assignee' => $miguel, 'due' => now()->addDays(2), 'prio' => 'urgent', 'status' => 'in_progress', 'decision' => 'KAB-BES-2026-015'],
            ['title' => 'Persbericht definitief — goedkeuring Kabinetschef', 'assignee' => $anand, 'due' => '2026-05-18', 'prio' => 'urgent', 'status' => 'open', 'decision' => 'KAB-BES-2026-015'],
            ['title' => 'ICT-mitigatieplan opstellen en presenteren', 'assignee' => $tarun, 'due' => now()->addDays(10), 'prio' => 'high', 'status' => 'open', 'decision' => 'KAB-BES-2026-016'],
            // Standalone (niet aan vergadering gekoppeld)
            ['title' => 'Voorbereiding staatsbezoek juni — protocol-briefing', 'assignee' => $marlinde, 'due' => now()->addDays(14), 'prio' => 'high', 'status' => 'in_progress', 'decision' => null],
            ['title' => 'Beveiligingsanalyse staatsbezoek juni', 'assignee' => $quincy, 'due' => now()->addDays(20), 'prio' => 'high', 'status' => 'open', 'decision' => null],
            ['title' => 'Q2-begrotingsrapportage opstellen', 'assignee' => $rianne, 'due' => now()->addMonths(1)->toDateString(), 'prio' => 'normal', 'status' => 'open', 'decision' => null],
            // Eentje die al te laat is — demo van rood-status
            ['title' => 'Achterstallige briefing intern overleg justitie', 'assignee' => $soraya, 'due' => now()->subDays(3), 'prio' => 'high', 'status' => 'in_progress', 'decision' => null],
        ];

        foreach ($actions as $i => $a) {
            if (! $a['assignee']) continue;
            $decisionId = $a['decision'] ? Decision::where('decision_number', $a['decision'])->value('id') : null;
            $meetingId = $a['decision'] ? $m2->id : null;

            ActionItem::updateOrCreate(
                ['title' => $a['title'], 'assignee_employee_id' => $a['assignee']->id],
                [
                    'meeting_id' => $meetingId,
                    'decision_id' => $decisionId,
                    'due_date' => is_string($a['due']) ? $a['due'] : $a['due']->toDateString(),
                    'priority' => $a['prio'],
                    'status' => $a['status'],
                ]
            );
        }
    }
}
