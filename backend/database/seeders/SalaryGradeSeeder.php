<?php

namespace Database\Seeders;

use App\Models\SalaryGrade;
use Illuminate\Database\Seeder;

class SalaryGradeSeeder extends Seeder
{
    public function run(): void
    {
        // Surinaamse overheidsschalen (parastatale tabel — schaal 1 vanaf 9.000 SRD)
        // Schaal 1 = laagste, Schaal 18 = hoogste. Per schaal 12 treden.

        $baseAmounts = [
            1 => 9000,  2 => 10100, 3 => 11300, 4 => 12600, 5 => 14200, 6 => 16000,
            7 => 18000, 8 => 20300, 9 => 22700, 10 => 25700, 11 => 28800, 12 => 32400,
            13 => 36500, 14 => 41000, 15 => 46100, 16 => 51800, 17 => 58100, 18 => 65300,
        ];

        $tredeStep = 0.04; // 4% per trede

        foreach ($baseAmounts as $schaal => $base) {
            for ($trede = 0; $trede < 12; $trede++) {
                $amount = round($base * (1 + $tredeStep * $trede), 2);
                $code = sprintf('S%02d-T%02d', $schaal, $trede);

                SalaryGrade::updateOrCreate(
                    ['schaal' => $schaal, 'trede' => $trede],
                    [
                        'code' => $code,
                        'base_amount' => $amount,
                        'currency' => 'SRD',
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
