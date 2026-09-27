<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions map per Section 4
        $permissions = [
            'spj.create',
            'spj.view-own',
            'spj.view-all',
            'spj.konsolidasi',
            'spj.ajukan-verifikasi',
            'spj.verifikasi',
            'kegiatan.manage',
            'kegiatan.view',
            'aset.create',
            'aset.update',
            'aset.view',
            'aset.generate-qr',
            'aset.generate-kibkir',
            'laporan.view',
            'laporan.export',
            'dashboard.eksekutif',
            'dashboard.operasional',
            'dashboard.kasi',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // Roles mapping
        $rolesWithPermissions = [
            UserRole::SUPER_ADMIN->value => $permissions,
            UserRole::STAF_UMUM->value => [
                'aset.update',
                'aset.view',
                'aset.generate-qr',
            ],
            UserRole::STAF_KEUANGAN->value => [
                'spj.view-all',
                'spj.konsolidasi',
                'spj.ajukan-verifikasi',
                'kegiatan.manage',
                'kegiatan.view',
                'aset.create',
                'aset.update',
                'aset.view',
                'aset.generate-qr',
                'aset.generate-kibkir',
                'laporan.view',
                'laporan.export',
                'dashboard.operasional',
            ],
            UserRole::KASI->value => [
                'spj.create',
                'spj.view-own',
                'dashboard.kasi',
            ],
            UserRole::SEKMAT->value => [
                'spj.view-all',
                'spj.verifikasi',
                'kegiatan.view',
                'aset.view',
                'laporan.view',
                'laporan.export',
                'dashboard.eksekutif',
            ],
            UserRole::CAMAT->value => [
                'kegiatan.view',
                'spj.view-all',
                'spj.verifikasi',
                'aset.view',
                'laporan.view',
                'laporan.export',
                'dashboard.eksekutif',
            ],
        ];

        foreach ($rolesWithPermissions as $roleName => $assignedPermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($assignedPermissions);
        }
    }
}
