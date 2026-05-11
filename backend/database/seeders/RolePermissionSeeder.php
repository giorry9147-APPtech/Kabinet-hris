<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'org_units.view', 'org_units.manage',
            'positions.view', 'positions.manage',
            'employees.view', 'employees.manage', 'employees.view_sensitive',
            'employment.manage',
            'salary_grades.manage',
            'salary.view', 'salary.manage',
            'certificate_types.manage',
            'certificates.view', 'certificates.manage',
            'leave.view', 'leave.request', 'leave.approve',
            'assets.view', 'assets.manage',
            'documents.view', 'documents.manage',
            'reports.view',
            'users.manage',
            'roles.manage',
            'audit_log.view',
            'meetings.view', 'meetings.manage',
            'decisions.view', 'decisions.manage',
            'action_items.view', 'action_items.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $roles = [
            'super_admin' => $permissions,

            'hr_manager' => [
                'org_units.view', 'org_units.manage',
                'positions.view', 'positions.manage',
                'employees.view', 'employees.manage', 'employees.view_sensitive',
                'employment.manage',
                'salary_grades.manage', 'salary.view', 'salary.manage',
                'certificate_types.manage', 'certificates.view', 'certificates.manage',
                'leave.view', 'leave.approve',
                'assets.view',
                'documents.view', 'documents.manage',
                'reports.view',
                'users.manage',
                'audit_log.view',
                'meetings.view', 'meetings.manage',
                'decisions.view', 'decisions.manage',
                'action_items.view', 'action_items.manage',
            ],

            'hr_admin' => [
                'org_units.view', 'positions.view',
                'employees.view', 'employees.manage',
                'employment.manage',
                'salary.view',
                'certificates.view', 'certificates.manage',
                'leave.view',
                'assets.view',
                'documents.view', 'documents.manage',
                'reports.view',
            ],

            'dept_head' => [
                'org_units.view', 'positions.view',
                'employees.view',
                'leave.view', 'leave.approve',
                'assets.view',
                'documents.view',
                'reports.view',
                'meetings.view', 'meetings.manage',
                'decisions.view',
                'action_items.view', 'action_items.manage',
            ],

            'finance' => [
                'employees.view',
                'salary_grades.manage', 'salary.view', 'salary.manage',
                'reports.view',
            ],

            'employee' => [
                'leave.request',
            ],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
