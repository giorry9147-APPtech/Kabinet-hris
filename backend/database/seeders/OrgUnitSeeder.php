<?php

namespace Database\Seeders;

use App\Models\OrgUnit;
use Illuminate\Database\Seeder;

class OrgUnitSeeder extends Seeder
{
    /**
     * Seeds plausible Kabinet-van-de-President org-structuur (demo data).
     * Tree:
     *   Kabinet van de President (KAB)
     *     ├── Bureau van de President (BVP)
     *     ├── Bureau van de Vicepresident (BVVP)
     *     ├── Communicatie & Voorlichting (COM)
     *     ├── Protocol & Internationale Betrekkingen (PROT)
     *     ├── Juridische Zaken (JUR)
     *     ├── Beleidscoordinatie (BEL)
     *     ├── Algemene Zaken (AZ)
     *     │     ├── Personeelszaken (AZ-HR)
     *     │     ├── Financien (AZ-FIN)
     *     │     ├── ICT (AZ-ICT)
     *     │     └── Facilitair (AZ-FAC)
     *     └── Beveiliging Kabinet (SEC)
     */
    public function run(): void
    {
        $kab = OrgUnit::updateOrCreate(['code' => 'KAB'], [
            'name' => 'Kabinet van de President',
            'type' => 'directie',
            'parent_id' => null,
        ]);

        $afdelingen = [
            ['code' => 'BVP',  'name' => 'Bureau van de President'],
            ['code' => 'BVVP', 'name' => 'Bureau van de Vicepresident'],
            ['code' => 'COM',  'name' => 'Communicatie & Voorlichting'],
            ['code' => 'PROT', 'name' => 'Protocol & Internationale Betrekkingen'],
            ['code' => 'JUR',  'name' => 'Juridische Zaken'],
            ['code' => 'BEL',  'name' => 'Beleidscoordinatie'],
            ['code' => 'SEC',  'name' => 'Beveiliging Kabinet'],
        ];
        foreach ($afdelingen as $a) {
            OrgUnit::updateOrCreate(['code' => $a['code']], [
                'name' => $a['name'],
                'type' => 'afdeling',
                'parent_id' => $kab->id,
            ]);
        }

        $az = OrgUnit::updateOrCreate(['code' => 'AZ'], [
            'name' => 'Algemene Zaken',
            'type' => 'afdeling',
            'parent_id' => $kab->id,
        ]);

        $azSecties = [
            ['code' => 'AZ-HR',  'name' => 'Personeelszaken'],
            ['code' => 'AZ-FIN', 'name' => 'Financien'],
            ['code' => 'AZ-ICT', 'name' => 'ICT'],
            ['code' => 'AZ-FAC', 'name' => 'Facilitair'],
        ];
        foreach ($azSecties as $s) {
            OrgUnit::updateOrCreate(['code' => $s['code']], [
                'name' => $s['name'],
                'type' => 'sectie',
                'parent_id' => $az->id,
            ]);
        }
    }
}
