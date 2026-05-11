<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\OrgUnit;
use App\Models\Resolution;
use Illuminate\Database\Seeder;

/**
 * Demo-data voor contracten + presidentiële resoluties (beschikkingen).
 * Aansluitend op DemoEmployeeSeeder.
 */
class ContractsAndResolutionsSeeder extends Seeder
{
    public function run(): void
    {
        $byEmp = fn (string $no) => Employee::where('employee_number', $no)->first();

        // ---------- Contracten ----------
        $contracts = [
            // Sergio Akiemboto — Kabinetschef, vast benoemd
            ['emp' => 'KAB-0001', 'no' => 'CONT-2020-0001', 'type' => 'vast', 'title' => 'Kabinetschef / Directeur Kabinet', 'start' => '2020-07-16', 'end' => null,         'signed' => '2020-07-10', 'notice' => null, 'amount' => 28500.00, 'status' => 'active'],
            // Soraya Doelawat — Hoofd Juridische Zaken, vaste aanstelling
            ['emp' => 'KAB-0002', 'no' => 'CONT-2020-0002', 'type' => 'vast', 'title' => 'Hoofd Juridische Zaken',       'start' => '2020-09-15', 'end' => null,         'signed' => '2020-09-01', 'notice' => null, 'amount' => 21500.00, 'status' => 'active'],
            // Anand Pawiroredjo — Hoofd Communicatie, bepaalde tijd 4 jaar (verloopt binnenkort!)
            ['emp' => 'KAB-0003', 'no' => 'CONT-2020-0003', 'type' => 'bepaald', 'title' => 'Hoofd Communicatie',         'start' => '2020-10-01', 'end' => '2026-09-30', 'signed' => '2020-09-20', 'notice' => 90,   'amount' => 19500.00, 'status' => 'active'],
            // Marlinde Nelson — Hoofd Protocol, bepaalde tijd (binnen 12 maanden verloop)
            ['emp' => 'KAB-0004', 'no' => 'CONT-2021-0001', 'type' => 'bepaald', 'title' => 'Hoofd Protocol',             'start' => '2021-01-12', 'end' => '2027-01-11', 'signed' => '2021-01-05', 'notice' => 60,   'amount' => 17500.00, 'status' => 'active'],
            // Tarun Doerga — Hoofd ICT, detachering
            ['emp' => 'KAB-0007', 'no' => 'DET-2021-0007', 'type' => 'detachering', 'title' => 'Hoofd ICT (detachering Telesur)', 'start' => '2021-05-20', 'end' => '2026-05-19', 'signed' => '2021-05-10', 'notice' => 30, 'amount' => 14500.00, 'status' => 'expiring'],
            // Miguel Codrington — Persvoorlichter, tijdelijk 2 jaar (verloopt < 90 dagen)
            ['emp' => 'KAB-0009', 'no' => 'TMP-2024-0009', 'type' => 'tijdelijk', 'title' => 'Persvoorlichter',           'start' => '2024-06-01', 'end' => '2026-07-01', 'signed' => '2024-05-25', 'notice' => 30,   'amount' => 11500.00, 'status' => 'expiring'],
            // Farah Mahabier — consultancy beleid
            ['emp' => 'KAB-0010', 'no' => 'CON-2024-0010', 'type' => 'consultancy', 'title' => 'Senior Beleidsadviseur (consultancy)', 'start' => '2024-09-15', 'end' => '2026-09-14', 'signed' => '2024-09-10', 'notice' => 60, 'amount' => 16000.00, 'status' => 'active'],
            // Quincy Boldewijn — Hoofd Beveiliging, vast
            ['emp' => 'KAB-0011', 'no' => 'CONT-2020-0011', 'type' => 'vast', 'title' => 'Hoofd Beveiliging',             'start' => '2020-12-01', 'end' => null,         'signed' => '2020-11-20', 'notice' => null, 'amount' => 14500.00, 'status' => 'active'],
            // Devika Tjon-A-Joe — stagecontract (al verlopen — demo expired state)
            ['emp' => 'KAB-0012', 'no' => 'STA-2023-0012', 'type' => 'stage', 'title' => 'Stagiair Protocol',             'start' => '2023-02-15', 'end' => '2023-08-14', 'signed' => '2023-02-10', 'notice' => null, 'amount' => 3500.00,  'status' => 'expired'],
        ];

        foreach ($contracts as $c) {
            $employee = $byEmp($c['emp']);
            if (! $employee) continue;

            Contract::updateOrCreate(
                ['contract_number' => $c['no']],
                [
                    'employee_id' => $employee->id,
                    'type' => $c['type'],
                    'title' => $c['title'],
                    'start_date' => $c['start'],
                    'end_date' => $c['end'],
                    'signed_at' => $c['signed'],
                    'notice_period_days' => $c['notice'],
                    'monthly_amount' => $c['amount'],
                    'currency' => 'SRD',
                    'status' => $c['status'],
                ]
            );
        }

        // ---------- Presidentiële resoluties (beschikkingen) ----------
        $bvp = OrgUnit::where('code', 'BVP')->first();
        $kab = OrgUnit::where('code', 'KAB')->first();
        $sec = OrgUnit::where('code', 'SEC')->first();
        $com = OrgUnit::where('code', 'COM')->first();

        $resolutions = [
            [
                'no' => 'PB-2020-0241', 'subject' => 'Benoeming Kabinetschef / Directeur Kabinet van de President',
                'category' => 'benoeming', 'emp' => 'KAB-0001', 'org' => $bvp?->id,
                'signed' => '2020-07-10', 'eff' => '2020-07-16', 'exp' => null,
                'status' => 'active',
                'summary' => 'Bij beschikking van de President van de Republiek Suriname wordt de heer S. Akiemboto met ingang van 16 juli 2020 benoemd tot Kabinetschef / Directeur van het Kabinet van de President.',
            ],
            [
                'no' => 'PB-2024-0078', 'subject' => 'Mandaat ondertekening personeelsbesluiten Kabinetschef',
                'category' => 'mandaat', 'emp' => 'KAB-0001', 'org' => $bvp?->id,
                'signed' => '2024-01-15', 'eff' => '2024-02-01', 'exp' => '2027-01-31',
                'status' => 'active',
                'summary' => 'Aan de Kabinetschef wordt mandaat verleend tot het ondertekenen van personeels- en HR-besluiten t.b.v. medewerkers binnen het Kabinet.',
            ],
            [
                'no' => 'PB-2023-0512', 'subject' => 'Instelling Commissie Communicatiestrategie',
                'category' => 'commissie', 'emp' => 'KAB-0003', 'org' => $com?->id,
                'signed' => '2023-11-04', 'eff' => '2023-12-01', 'exp' => '2026-11-30',
                'status' => 'active',
                'summary' => 'Instelling van een ad-hoc commissie ter advisering over de strategische communicatie van het Kabinet, onder voorzitterschap van het Hoofd Communicatie.',
            ],
            [
                'no' => 'PB-2024-0214', 'subject' => 'Toelage Persoonsbeveiliging President',
                'category' => 'bezoldiging', 'emp' => null, 'org' => $sec?->id,
                'signed' => '2024-05-21', 'eff' => '2024-06-01', 'exp' => '2026-05-31',
                'status' => 'expiring',
                'summary' => 'Vaststelling van een tijdelijke risicotoelage voor persoonsbeveiligers van de President — looptijd 24 maanden, verlenging vereist vóór einddatum.',
            ],
            [
                'no' => 'PB-2024-0299', 'subject' => 'Detachering ICT-specialist vanuit Telesur',
                'category' => 'detachering', 'emp' => 'KAB-0007', 'org' => null,
                'signed' => '2024-04-30', 'eff' => '2024-05-01', 'exp' => '2026-04-30',
                'status' => 'expired',
                'summary' => 'Beschikking houdende detachering van een ICT-specialist vanuit Telesur N.V. aan het Kabinet voor een periode van 24 maanden.',
            ],
            [
                'no' => 'PB-2025-0033', 'subject' => 'Aanwijzing protocolofficier staatsbezoeken 2025–2027',
                'category' => 'mandaat', 'emp' => 'KAB-0004', 'org' => null,
                'signed' => '2025-01-15', 'eff' => '2025-02-01', 'exp' => '2027-12-31',
                'status' => 'active',
                'summary' => 'Aanwijzing van het Hoofd Protocol als verantwoordelijk protocolofficier voor inkomende en uitgaande staatsbezoeken.',
            ],
            [
                'no' => 'PB-2025-0188', 'subject' => 'Beleidsbeschikking digitalisering Kabinet',
                'category' => 'beleid', 'emp' => null, 'org' => $kab?->id,
                'signed' => '2025-08-22', 'eff' => '2025-09-01', 'exp' => null,
                'status' => 'active',
                'summary' => 'Vaststelling van het meerjarig digitaliseringsbeleid voor het Kabinet van de President; open-ended geldigheid.',
            ],
        ];

        foreach ($resolutions as $r) {
            $employee = $r['emp'] ? $byEmp($r['emp']) : null;

            Resolution::updateOrCreate(
                ['resolution_number' => $r['no']],
                [
                    'subject' => $r['subject'],
                    'category' => $r['category'],
                    'employee_id' => $employee?->id,
                    'org_unit_id' => $r['org'],
                    'signed_at' => $r['signed'],
                    'effective_from' => $r['eff'],
                    'expires_at' => $r['exp'],
                    'status' => $r['status'],
                    'signed_by' => 'President van de Republiek Suriname',
                    'summary' => $r['summary'],
                ]
            );
        }
    }
}
