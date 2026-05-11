<?php

namespace App\Console\Commands;

use App\Models\ActionItem;
use App\Models\Contract;
use App\Models\Decision;
use App\Models\Employee;
use App\Models\EmploymentRecord;
use App\Models\Meeting;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Models\Resolution;
use App\Models\SalaryAssignment;
use App\Models\SalaryGrade;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Eenmalige (maar idempotente) installatie van Sergio Akiemboto als Kabinetschef.
 *
 * Veilig om meerdere keren te draaien — gebruikt updateOrCreate overal. Werkt op:
 *   1) verse DB        — maakt Sergio + bijbehorende contract/resolutie aan
 *   2) DB met Rodney   — hernoemt KAB-0001-record + user naar Sergio
 *   3) DB met Sergio   — no-op (alleen status-bevestiging)
 *
 * Voer op productie uit via:
 *   fly ssh console --app kabinet-hris-backend -C "php /var/www/html/artisan kabinet:install-chief-of-staff --with-demo"
 */
class InstallChiefOfStaff extends Command
{
    protected $signature = 'kabinet:install-chief-of-staff
                            {--with-demo : Installeer ook de demo-vergaderingen, besluiten en werkafspraken voor het dashboard}';

    protected $description = 'Installeert Sergio Akiemboto als Kabinetschef / Chief of Staff op positie BVP-001. Idempotent — veilig om meerdere keren te draaien.';

    public function handle(): int
    {
        $this->info('=== Kabinetschef installer (Sergio Akiemboto) ===');
        $this->newLine();

        return DB::transaction(function () {
            // 1. Positie
            $position = Position::where('code', 'BVP-001')->first();
            if (! $position) {
                $this->error('Positie BVP-001 niet gevonden — was de Kabinet HRIS al gemigreerd + geseed?');
                return self::FAILURE;
            }
            $position->update([
                'title' => 'Kabinetschef / Directeur Kabinet (Chief of Staff)',
                'status' => 'occupied',
            ]);
            $this->line('<fg=green>✓</> Positie BVP-001: '.$position->title);

            // 2. Medewerker
            $employee = Employee::updateOrCreate(
                ['employee_number' => 'KAB-0001'],
                [
                    'first_name' => 'Sergio',
                    'last_name' => 'Akiemboto',
                    'email' => 'sergio.akiemboto@kabinet.sr',
                    'gender' => 'm',
                    'nationality' => 'Surinaams',
                    'current_position_id' => $position->id,
                    'status' => 'active',
                    'joined_at' => '2020-07-16',
                ]
            );
            $this->line('<fg=green>✓</> Medewerker KAB-0001: '.$employee->first_name.' '.$employee->last_name);

            // 3. Employment record
            EmploymentRecord::updateOrCreate(
                ['employee_id' => $employee->id, 'position_id' => $position->id, 'start_date' => '2020-07-16'],
                ['status' => 'active', 'reason' => 'hire']
            );

            // 4. Salaris
            $grade = SalaryGrade::where('schaal', 18)->where('trede', 10)->first();
            if ($grade) {
                SalaryAssignment::updateOrCreate(
                    ['employee_id' => $employee->id, 'salary_grade_id' => $grade->id, 'start_date' => '2020-07-16'],
                    ['base_amount' => $grade->base_amount, 'allowances' => round($grade->base_amount * 0.1, 2), 'currency' => 'SRD']
                );
                $this->line('<fg=green>✓</> Salarisinschaling: Schaal 18 Trede 10');
            } else {
                $this->warn('  Geen Schaal 18 Trede 10 gevonden — salarisinschaling overgeslagen.');
            }

            // 5. User-account
            $user = $this->installUser($employee);

            // 6. Contract
            Contract::updateOrCreate(
                ['contract_number' => 'CONT-2020-0001'],
                [
                    'employee_id' => $employee->id,
                    'type' => 'vast',
                    'title' => 'Kabinetschef / Directeur Kabinet',
                    'start_date' => '2020-07-16',
                    'end_date' => null,
                    'signed_at' => '2020-07-10',
                    'notice_period_days' => null,
                    'monthly_amount' => 28500.00,
                    'currency' => 'SRD',
                    'status' => 'active',
                ]
            );
            $this->line('<fg=green>✓</> Contract CONT-2020-0001 (vast, Kabinetschef)');

            // 7. Resoluties
            $bvpId = OrgUnit::where('code', 'BVP')->value('id');

            Resolution::updateOrCreate(
                ['resolution_number' => 'PB-2020-0241'],
                [
                    'subject' => 'Benoeming Kabinetschef / Directeur Kabinet van de President',
                    'category' => 'benoeming',
                    'employee_id' => $employee->id,
                    'org_unit_id' => $bvpId,
                    'signed_at' => '2020-07-10',
                    'effective_from' => '2020-07-16',
                    'expires_at' => null,
                    'status' => 'active',
                    'signed_by' => 'President van de Republiek Suriname',
                    'summary' => 'Bij beschikking van de President van de Republiek Suriname wordt de heer S. Akiemboto met ingang van 16 juli 2020 benoemd tot Kabinetschef / Directeur van het Kabinet van de President.',
                ]
            );

            Resolution::updateOrCreate(
                ['resolution_number' => 'PB-2024-0078'],
                [
                    'subject' => 'Mandaat ondertekening personeelsbesluiten Kabinetschef',
                    'category' => 'mandaat',
                    'employee_id' => $employee->id,
                    'org_unit_id' => $bvpId,
                    'signed_at' => '2024-01-15',
                    'effective_from' => '2024-02-01',
                    'expires_at' => '2027-01-31',
                    'status' => 'active',
                    'signed_by' => 'President van de Republiek Suriname',
                    'summary' => 'Aan de Kabinetschef wordt mandaat verleend tot het ondertekenen van personeels- en HR-besluiten t.b.v. medewerkers binnen het Kabinet.',
                ]
            );
            $this->line('<fg=green>✓</> Resoluties PB-2020-0241 (benoeming) + PB-2024-0078 (mandaat)');

            // 8. Demo-vergaderingen optioneel
            if ($this->option('with-demo')) {
                $this->newLine();
                $this->info('--- Demo-vergaderingen, besluiten, werkafspraken ---');
                $this->installDemoMeetings($employee);
            } else {
                $this->newLine();
                $this->comment('(Demo-data overgeslagen — voeg --with-demo toe om die mee te installeren.)');
            }

            $this->newLine();
            $this->info('Klaar. Login: '.$user->email);
            if ($user->wasRecentlyCreated || isset($user->_freshlyHashed)) {
                $this->warn('Standaard-wachtwoord: kabinet-demo-2026  →  WIJZIG DIT DIRECT in /admin profiel.');
            }
            $this->newLine();

            return self::SUCCESS;
        });
    }

    private function installUser(Employee $employee): User
    {
        // Prefer existing user already tied to this employee
        $user = User::where('employee_id', $employee->id)->first();

        // Otherwise look for legacy placeholder (Rodney) or the target email
        if (! $user) {
            $user = User::where('email', 'sergio.akiemboto@kabinet.sr')->first();
        }
        if (! $user) {
            $user = User::where('email', 'rodney.asabina@kabinet.sr')->first();
        }

        if ($user) {
            $renamed = $user->email !== 'sergio.akiemboto@kabinet.sr';
            $user->update([
                'email' => 'sergio.akiemboto@kabinet.sr',
                'name' => 'Sergio Akiemboto',
                'employee_id' => $employee->id,
                'is_active' => true,
            ]);
            $this->line($renamed
                ? '<fg=green>✓</> Bestaand user-account hernoemd naar sergio.akiemboto@kabinet.sr (wachtwoord ongewijzigd)'
                : '<fg=green>✓</> User sergio.akiemboto@kabinet.sr bijgewerkt'
            );
        } else {
            $user = User::create([
                'email' => 'sergio.akiemboto@kabinet.sr',
                'name' => 'Sergio Akiemboto',
                'password' => Hash::make('kabinet-demo-2026'),
                'employee_id' => $employee->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $user->_freshlyHashed = true;
            $this->line('<fg=green>✓</> User-account aangemaakt: sergio.akiemboto@kabinet.sr');
        }

        if (! $user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
        }
        $this->line('<fg=green>✓</> Rol super_admin toegekend');

        return $user;
    }

    private function installDemoMeetings(Employee $sergio): void
    {
        $byNum = fn (string $no) => Employee::where('employee_number', $no)->first();

        $hoofdJur = $byNum('KAB-0002');
        $hoofdCom = $byNum('KAB-0003');
        $hoofdPro = $byNum('KAB-0004');
        $hoofdHR = $byNum('KAB-0005');
        $hoofdFin = $byNum('KAB-0006');
        $hoofdICT = $byNum('KAB-0007');
        $persvoor = $byNum('KAB-0009');
        $hoofdBev = $byNum('KAB-0011');

        // ---- Vergaderingen ----
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
                'minutes_status' => 'none',
            ]
        );
        foreach (array_filter([$hoofdJur, $hoofdCom]) as $emp) {
            $m1->attendees()->syncWithoutDetaching([$emp->id => ['role' => 'participant']]);
        }
        if ($hoofdPro) {
            $m1->attendees()->syncWithoutDetaching([$hoofdPro->id => ['role' => 'note_taker']]);
        }

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
        foreach (array_filter([$hoofdJur, $hoofdCom, $hoofdPro, $hoofdHR, $hoofdFin, $hoofdICT]) as $emp) {
            $m2->attendees()->syncWithoutDetaching([$emp->id => ['role' => 'participant', 'attended' => true]]);
        }

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
        if ($hoofdHR) $m3->attendees()->syncWithoutDetaching([$hoofdHR->id => ['role' => 'participant']]);

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
                'agenda' => 'Halfjaarsplanning, prioritering dossiers, resourceallocatie.',
                'external_attendees' => "Mevr. J. Karijomenawi — Min. Buitenlandse Zaken\nDhr. F. Liauw — Bureau Statistiek",
                'minutes_status' => 'none',
            ]
        );
        foreach (array_filter([$hoofdJur, $hoofdCom, $hoofdFin, $hoofdPro]) as $emp) {
            $m4->attendees()->syncWithoutDetaching([$emp->id => ['role' => 'participant']]);
        }

        $this->line('<fg=green>✓</> 4 vergaderingen ingesteld (1 met President, 1 staf met notulen, 1 sollicitatie, 1 strategisch)');

        // ---- Besluiten ----
        $d1 = Decision::updateOrCreate(
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
                'notes' => 'Procesbegeleiding door Hoofd Personeelszaken. Concept-resoluties klaarmaken voor President.',
            ]
        );

        $d2 = Decision::updateOrCreate(
            ['decision_number' => 'KAB-BES-2026-015'],
            [
                'meeting_id' => $m2->id,
                'subject' => 'Persbericht digitalisering Kabinet — publicatie uiterlijk 20 mei',
                'decision_text' => 'Hoofd Communicatie stelt persbericht op over voortgang digitalisering Kabinet. Definitieve versie ter goedkeuring aan Kabinetschef vóór 18 mei; publicatie uiterlijk 20 mei 2026.',
                'decided_at' => now()->subWeek()->next('Friday')->toDateString(),
                'responsible_employee_id' => $hoofdCom?->id,
                'deadline' => '2026-05-20',
                'priority' => 'urgent',
                'status' => 'open',
            ]
        );

        $d3 = Decision::updateOrCreate(
            ['decision_number' => 'KAB-BES-2026-016'],
            [
                'meeting_id' => $m2->id,
                'subject' => 'Aanpak vertraging ICT-modernisering',
                'decision_text' => 'Hoofd ICT presenteert binnen 2 weken een herziene planning + mitigatieplan voor leveranciersvertraging.',
                'decided_at' => now()->subWeek()->next('Friday')->toDateString(),
                'responsible_employee_id' => $hoofdICT?->id,
                'deadline' => now()->subWeek()->next('Friday')->addDays(14)->toDateString(),
                'priority' => 'high',
                'status' => 'in_progress',
            ]
        );

        $this->line('<fg=green>✓</> 3 besluiten ingesteld');

        // ---- Werkafspraken ----
        $actions = [
            ['title' => 'Vacaturetekst Senior Adviseur Economische Zaken opstellen', 'assignee' => $hoofdHR, 'due' => now()->addDays(5), 'prio' => 'high', 'status' => 'in_progress', 'decision' => $d1, 'meeting' => $m2],
            ['title' => 'Vacaturetekst Adviseur Juridische Zaken opstellen', 'assignee' => $hoofdHR, 'due' => now()->addDays(7), 'prio' => 'normal', 'status' => 'open', 'decision' => $d1, 'meeting' => $m2],
            ['title' => 'Concept presidentiële beschikkingen voor 3 benoemingen voorbereiden', 'assignee' => $hoofdJur, 'due' => now()->addDays(21), 'prio' => 'high', 'status' => 'open', 'decision' => $d1, 'meeting' => $m2],
            ['title' => 'Persbericht digitalisering eerste concept', 'assignee' => $persvoor, 'due' => now()->addDays(2), 'prio' => 'urgent', 'status' => 'in_progress', 'decision' => $d2, 'meeting' => $m2],
            ['title' => 'Persbericht definitief — goedkeuring Kabinetschef', 'assignee' => $hoofdCom, 'due' => '2026-05-18', 'prio' => 'urgent', 'status' => 'open', 'decision' => $d2, 'meeting' => $m2],
            ['title' => 'ICT-mitigatieplan opstellen en presenteren', 'assignee' => $hoofdICT, 'due' => now()->addDays(10), 'prio' => 'high', 'status' => 'open', 'decision' => $d3, 'meeting' => $m2],
            ['title' => 'Voorbereiding staatsbezoek juni — protocol-briefing', 'assignee' => $hoofdPro, 'due' => now()->addDays(14), 'prio' => 'high', 'status' => 'in_progress', 'decision' => null, 'meeting' => null],
            ['title' => 'Beveiligingsanalyse staatsbezoek juni', 'assignee' => $hoofdBev, 'due' => now()->addDays(20), 'prio' => 'high', 'status' => 'open', 'decision' => null, 'meeting' => null],
            ['title' => 'Q2-begrotingsrapportage opstellen', 'assignee' => $hoofdFin, 'due' => now()->addMonths(1)->toDateString(), 'prio' => 'normal', 'status' => 'open', 'decision' => null, 'meeting' => null],
            ['title' => 'Achterstallige briefing intern overleg justitie', 'assignee' => $hoofdJur, 'due' => now()->subDays(3), 'prio' => 'high', 'status' => 'in_progress', 'decision' => null, 'meeting' => null],
        ];

        $count = 0;
        foreach ($actions as $a) {
            if (! $a['assignee']) continue;
            ActionItem::updateOrCreate(
                ['title' => $a['title'], 'assignee_employee_id' => $a['assignee']->id],
                [
                    'meeting_id' => $a['meeting']?->id,
                    'decision_id' => $a['decision']?->id,
                    'due_date' => is_string($a['due']) ? $a['due'] : $a['due']->toDateString(),
                    'priority' => $a['prio'],
                    'status' => $a['status'],
                ]
            );
            $count++;
        }
        $this->line('<fg=green>✓</> '.$count.' werkafspraken ingesteld (1 al verstreken — demo voor rood-status)');
    }
}
