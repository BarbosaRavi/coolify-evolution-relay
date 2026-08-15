<?php

namespace Database\Seeders;

use App\Enums\UserTypeEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionModel = config('permission.models.permission');
        $roleModel = config('permission.models.role');

        $guard = 'api';

        $permissionNames = array_keys(config('permission_sync.permissions', []));

        foreach ($permissionNames as $permissionName) {
            $permissionModel::findOrCreate($permissionName, $guard);
        }

        $allPermissions = $permissionModel::query()
            ->where('guard_name', $guard)
            ->get();

        $rolePermissions = [
            UserTypeEnum::SYS_ADMIN->value => ['*'],

            UserTypeEnum::USER->value => [
                'project.view',
            ],
        ];

        foreach (UserTypeEnum::cases() as $userType) {
            $role = $roleModel::findOrCreate($userType->value, $guard);

            $permissions = $rolePermissions[$userType->value] ?? [];

            if ($permissions === ['*']) {
                $role->syncPermissions($allPermissions);

                continue;
            }

            $role->syncPermissions(
                $permissionModel::query()
                    ->where('guard_name', $guard)
                    ->whereIn('name', $permissions)
                    ->get()
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
