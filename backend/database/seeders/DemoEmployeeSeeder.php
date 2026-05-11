<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Employee;
use App\Models\EmploymentRecord;
use App\Models\LeaveRequest;
use App\Models\Position;
use App\Models\SalaryAssignment;
use App\Models\SalaryGrade;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Kabinet-van-de-President demo medewerkers — clearly placeholder/fictional namen.
 * Vervang via Filament admin of via een latere echte import (zie Sprint 7).
 */
class DemoEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            // Chief of Staff / Directeur Kabinet — Sergio Akiemboto (echte naam, hoogste functie binnen het Kabinet)
            ['emp_no' => 'KAB-0001', 'first' => 'Sergio',   'last' => 'Akiemboto',    'email' => 'sergio.akiemboto@kabinet.sr',  'gender' => 'm', 'pos' => 'BVP-001',  'schaal' => 18, 'trede' => 10, 'joined' => '2020-07-16', 'role' => 'super_admin'],
            ['emp_no' => 'KAB-0002', 'first' => 'Soraya',   'last' => 'Doelawat',     'email' => 'soraya.doelawat@kabinet.sr',   'gender' => 'v', 'pos' => 'JUR-001',  'schaal' => 16, 'trede' => 6, 'joined' => '2020-09-15', 'role' => 'dept_head'],
            ['emp_no' => 'KAB-0003', 'first' => 'Anand',    'last' => 'Pawiroredjo',  'email' => 'anand.pawiroredjo@kabinet.sr', 'gender' => 'm', 'pos' => 'COM-001',  'schaal' => 15, 'trede' => 5, 'joined' => '2020-10-01', 'role' => 'dept_head'],
            ['emp_no' => 'KAB-0004', 'first' => 'Marlinde', 'last' => 'Nelson',       'email' => 'marlinde.nelson@kabinet.sr',   'gender' => 'v', 'pos' => 'PROT-001', 'schaal' => 14, 'trede' => 4, 'joined' => '2021-01-12', 'role' => 'dept_head'],
            ['emp_no' => 'KAB-0005', 'first' => 'Kenrick',  'last' => 'Sapoen',       'email' => 'kenrick.sapoen@kabinet.sr',    'gender' => 'm', 'pos' => 'HR-001',   'schaal' => 14, 'trede' => 5, 'joined' => '2020-09-01', 'role' => 'hr_manager'],
            ['emp_no' => 'KAB-0006', 'first' => 'Rianne',   'last' => 'Vreden',       'email' => 'rianne.vreden@kabinet.sr',     'gender' => 'v', 'pos' => 'FIN-001',  'schaal' => 14, 'trede' => 6, 'joined' => '2020-09-01', 'role' => 'finance'],
            ['emp_no' => 'KAB-0007', 'first' => 'Tarun',    'last' => 'Doerga',       'email' => 'tarun.doerga@kabinet.sr',      'gender' => 'm', 'pos' => 'ICT-001',  'schaal' => 12, 'trede' => 3, 'joined' => '2021-05-20', 'role' => 'employee'],
            ['emp_no' => 'KAB-0008', 'first' => 'Lakshmi',  'last' => 'Algoe',        'email' => 'lakshmi.algoe@kabinet.sr',     'gender' => 'v', 'pos' => 'HR-002',   'schaal' => 9,  'trede' => 3, 'joined' => '2022-03-10', 'role' => 'employee'],
            ['emp_no' => 'KAB-0009', 'first' => 'Miguel',   'last' => 'Codrington',   'email' => 'miguel.codrington@kabinet.sr', 'gender' => 'm', 'pos' => 'COM-002',  'schaal' => 10, 'trede' => 2, 'joined' => '2022-06-01', 'role' => 'employee'],
            ['emp_no' => 'KAB-0010', 'first' => 'Farah',    'last' => 'Mahabier',     'email' => 'farah.mahabier@kabinet.sr',    'gender' => 'v', 'pos' => 'BEL-002',  'schaal' => 13, 'trede' => 4, 'joined' => '2021-09-15', 'role' => 'employee'],
            ['emp_no' => 'KAB-0011', 'first' => 'Quincy',   'last' => 'Boldewijn',    'email' => 'quincy.boldewijn@kabinet.sr',  'gender' => 'm', 'pos' => 'SEC-001',  'schaal' => 12, 'trede' => 5, 'joined' => '2020-12-01'],
            ['emp_no' => 'KAB-0012', 'first' => 'Devika',   'last' => 'Tjon-A-Joe',   'email' => 'devika.tjon-a-joe@kabinet.sr', 'gender' => 'v', 'pos' => 'PROT-002', 'schaal' => 9,  'trede' => 2, 'joined' => '2023-02-15'],
        ];

        foreach ($employees as $i => $e) {
            $position = Position::where('code', $e['pos'])->first();
            $grade = SalaryGrade::where('schaal', $e['schaal'])->where('trede', $e['trede'])->first();
            if (! $position || ! $grade) continue;

            $employee = Employee::updateOrCreate(
                ['employee_number' => $e['emp_no']],
                [
                    'first_name' => $e['first'],
                    'last_name' => $e['last'],
                    'email' => $e['email'],
                    'gender' => $e['gender'],
                    'nationality' => 'Surinaams',
                    'phone' => sprintf('+597 %d %d %d', random_int(700, 899), random_int(1000, 9999), random_int(10, 99)),
                    'current_position_id' => $position->id,
                    'status' => 'active',
                    'joined_at' => $e['joined'],
                ]
            );

            $position->update(['status' => 'occupied']);

            EmploymentRecord::updateOrCreate(
                ['employee_id' => $employee->id, 'position_id' => $position->id, 'start_date' => $e['joined']],
                ['status' => 'active', 'reason' => 'hire']
            );

            SalaryAssignment::updateOrCreate(
                ['employee_id' => $employee->id, 'salary_grade_id' => $grade->id, 'start_date' => $e['joined']],
                ['base_amount' => $grade->base_amount, 'allowances' => round($grade->base_amount * 0.1, 2), 'currency' => 'SRD']
            );

            // Pending verlofaanvragen voor enkele medewerkers
            if ($i % 4 === 0) {
                LeaveRequest::updateOrCreate(
                    ['employee_id' => $employee->id, 'start_date' => now()->addDays(14 + $i)->toDateString()],
                    [
                        'type' => 'vacation',
                        'end_date' => now()->addDays(14 + $i + 5)->toDateString(),
                        'days_count' => 5,
                        'status' => 'pending',
                        'reason' => 'Familiebezoek',
                    ]
                );
            }

            // User-account voor medewerkers met een rol
            if (! empty($e['role'])) {
                $user = User::updateOrCreate(
                    ['email' => $e['email']],
                    [
                        'name' => "{$e['first']} {$e['last']}",
                        'password' => Hash::make('kabinet-demo-2026'),
                        'employee_id' => $employee->id,
                        'is_active' => true,
                        'email_verified_at' => now(),
                    ]
                );
                if (! $user->hasRole($e['role'])) {
                    $user->assignRole($e['role']);
                }
            }
        }

        // Generic bedrijfsmiddelen (zonder MAS-specifieke maritieme gear)
        $assets = [
            ['code' => 'LAP-001', 'name' => 'Dell Latitude 5430',  'category' => 'Laptop',   'serial' => 'DL5430-K-001'],
            ['code' => 'LAP-002', 'name' => 'MacBook Pro 14"',     'category' => 'Laptop',   'serial' => 'MBP14-K-001'],
            ['code' => 'LAP-003', 'name' => 'Dell Latitude 5430',  'category' => 'Laptop',   'serial' => 'DL5430-K-002'],
            ['code' => 'PHN-001', 'name' => 'iPhone 15 Pro',       'category' => 'Mobiel',   'serial' => 'IP15P-K-001'],
            ['code' => 'PHN-002', 'name' => 'Samsung Galaxy S24',  'category' => 'Mobiel',   'serial' => 'SGS24-K-001'],
            ['code' => 'CAR-001', 'name' => 'Toyota Land Cruiser', 'category' => 'Voertuig', 'serial' => 'TLC-K-001'],
            ['code' => 'CAR-002', 'name' => 'Mercedes E-Klasse',   'category' => 'Voertuig', 'serial' => 'MERC-K-001'],
        ];

        foreach ($assets as $i => $a) {
            $asset = Asset::updateOrCreate(
                ['asset_code' => $a['code']],
                [
                    'name' => $a['name'],
                    'category' => $a['category'],
                    'serial_number' => $a['serial'],
                    'purchased_at' => now()->subMonths(6 + $i)->toDateString(),
                    'status' => 'available',
                ]
            );

            if ($i < 5 && isset($employees[$i])) {
                $emp = Employee::where('employee_number', $employees[$i]['emp_no'])->first();
                if ($emp) {
                    AssetAssignment::updateOrCreate(
                        ['asset_id' => $asset->id, 'employee_id' => $emp->id, 'assigned_at' => now()->subMonths(3)->toDateString()],
                        ['condition_at_assignment' => 'nieuw']
                    );
                    $asset->update(['status' => 'assigned']);
                }
            }
        }
    }
}
