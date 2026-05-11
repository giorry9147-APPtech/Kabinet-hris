<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SuperAdminSeeder::class,
            OrgUnitSeeder::class,
            PositionSeeder::class,
            SalaryGradeSeeder::class,
            CertificateTypeSeeder::class,
            DemoEmployeeSeeder::class,
            ContractsAndResolutionsSeeder::class,
            MeetingsSeeder::class,
        ]);
    }
}
