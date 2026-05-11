<?php

namespace Database\Seeders;

use App\Models\CertificateType;
use Illuminate\Database\Seeder;

class CertificateTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'STCW-BST', 'name' => 'STCW Basic Safety Training', 'category' => 'STCW', 'requires_expiry' => true, 'default_validity_months' => 60],
            ['code' => 'STCW-AFF', 'name' => 'STCW Advanced Fire Fighting', 'category' => 'STCW', 'requires_expiry' => true, 'default_validity_months' => 60],
            ['code' => 'STCW-MED', 'name' => 'STCW Medical First Aid', 'category' => 'STCW', 'requires_expiry' => true, 'default_validity_months' => 60],
            ['code' => 'STCW-PSCRB', 'name' => 'STCW Proficiency in Survival Craft', 'category' => 'STCW', 'requires_expiry' => true, 'default_validity_months' => 60],
            ['code' => 'ISPS-PFSO', 'name' => 'ISPS Port Facility Security Officer', 'category' => 'ISPS', 'requires_expiry' => true, 'default_validity_months' => 60],
            ['code' => 'ISPS-SSO', 'name' => 'ISPS Ship Security Officer', 'category' => 'ISPS', 'requires_expiry' => true, 'default_validity_months' => 60],
            ['code' => 'IMO-VTS', 'name' => 'IMO VTS Operator (V-103/1)', 'category' => 'IMO', 'requires_expiry' => true, 'default_validity_months' => 36],
            ['code' => 'IMO-PILOT', 'name' => 'IMO Maritime Pilot', 'category' => 'IMO', 'requires_expiry' => true, 'default_validity_months' => 60],
            ['code' => 'GMDSS-GOC', 'name' => 'GMDSS General Operator Certificate', 'category' => 'GMDSS', 'requires_expiry' => true, 'default_validity_months' => 60],
            ['code' => 'MED-OFF', 'name' => 'Medical Fitness for Maritime Service', 'category' => 'Medisch', 'requires_expiry' => true, 'default_validity_months' => 24],
            ['code' => 'BHV', 'name' => 'BHV / Bedrijfshulpverlening', 'category' => 'Veiligheid', 'requires_expiry' => true, 'default_validity_months' => 12],
            ['code' => 'DRIVE-B', 'name' => 'Rijbewijs B', 'category' => 'Algemeen', 'requires_expiry' => true, 'default_validity_months' => 120],
        ];

        foreach ($types as $t) {
            CertificateType::updateOrCreate(['code' => $t['code']], $t + ['is_active' => true]);
        }
    }
}
