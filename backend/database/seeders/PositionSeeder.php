<?php

namespace Database\Seeders;

use App\Models\OrgUnit;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            // Top
            ['org' => 'KAB',  'title' => 'President',                        'code' => 'PRES-001', 'vacancies' => 1],
            ['org' => 'KAB',  'title' => 'Vicepresident',                    'code' => 'VP-001',   'vacancies' => 1],

            // Bureau van de President — hoogste positie binnen het Kabinet
            ['org' => 'BVP',  'title' => 'Kabinetschef / Directeur Kabinet (Chief of Staff)', 'code' => 'BVP-001', 'vacancies' => 1],
            ['org' => 'BVP',  'title' => 'Politiek Adviseur',                'code' => 'BVP-002',  'vacancies' => 3],
            ['org' => 'BVP',  'title' => 'Persoonlijk Assistent President',  'code' => 'BVP-003',  'vacancies' => 2],

            // Bureau van de Vicepresident
            ['org' => 'BVVP', 'title' => 'Adjunct-Kabinetschef',             'code' => 'BVVP-001', 'vacancies' => 1],
            ['org' => 'BVVP', 'title' => 'Beleidsadviseur Vicepresident',    'code' => 'BVVP-002', 'vacancies' => 2],

            // Communicatie
            ['org' => 'COM',  'title' => 'Hoofd Communicatie',               'code' => 'COM-001',  'vacancies' => 1],
            ['org' => 'COM',  'title' => 'Persvoorlichter',                  'code' => 'COM-002',  'vacancies' => 2],
            ['org' => 'COM',  'title' => 'Digital Media Medewerker',         'code' => 'COM-003',  'vacancies' => 2],

            // Protocol
            ['org' => 'PROT', 'title' => 'Hoofd Protocol',                   'code' => 'PROT-001', 'vacancies' => 1],
            ['org' => 'PROT', 'title' => 'Protocolmedewerker',               'code' => 'PROT-002', 'vacancies' => 3],

            // Juridisch
            ['org' => 'JUR',  'title' => 'Hoofd Juridische Zaken',           'code' => 'JUR-001',  'vacancies' => 1],
            ['org' => 'JUR',  'title' => 'Juridisch Adviseur',               'code' => 'JUR-002',  'vacancies' => 2],

            // Beleid
            ['org' => 'BEL',  'title' => 'Hoofd Beleidscoordinatie',         'code' => 'BEL-001',  'vacancies' => 1],
            ['org' => 'BEL',  'title' => 'Senior Beleidsadviseur',           'code' => 'BEL-002',  'vacancies' => 3],

            // Algemene Zaken
            ['org' => 'AZ-HR',  'title' => 'Hoofd Personeelszaken',          'code' => 'HR-001',   'vacancies' => 1],
            ['org' => 'AZ-HR',  'title' => 'HR-medewerker',                  'code' => 'HR-002',   'vacancies' => 2],
            ['org' => 'AZ-FIN', 'title' => 'Hoofd Financien',                'code' => 'FIN-001',  'vacancies' => 1],
            ['org' => 'AZ-FIN', 'title' => 'Financieel Medewerker',          'code' => 'FIN-002',  'vacancies' => 2],
            ['org' => 'AZ-ICT', 'title' => 'Hoofd ICT',                      'code' => 'ICT-001',  'vacancies' => 1],
            ['org' => 'AZ-ICT', 'title' => 'Systeembeheerder',               'code' => 'ICT-002',  'vacancies' => 2],
            ['org' => 'AZ-FAC', 'title' => 'Hoofd Facilitair',               'code' => 'FAC-001',  'vacancies' => 1],
            ['org' => 'AZ-FAC', 'title' => 'Facilitair Medewerker',          'code' => 'FAC-002',  'vacancies' => 4],
            ['org' => 'AZ-FAC', 'title' => 'Chauffeur',                      'code' => 'FAC-003',  'vacancies' => 6],

            // Beveiliging
            ['org' => 'SEC',  'title' => 'Hoofd Beveiliging',                'code' => 'SEC-001',  'vacancies' => 1],
            ['org' => 'SEC',  'title' => 'Persoonsbeveiliger President',     'code' => 'SEC-002',  'vacancies' => 4],
            ['org' => 'SEC',  'title' => 'Beveiligingsbeambte',              'code' => 'SEC-003',  'vacancies' => 8],
        ];

        foreach ($positions as $p) {
            $org = OrgUnit::where('code', $p['org'])->first();
            if (! $org) continue;

            Position::updateOrCreate(['code' => $p['code']], [
                'title' => $p['title'],
                'org_unit_id' => $org->id,
                'vacancies_count' => $p['vacancies'],
            ]);
        }
    }
}
