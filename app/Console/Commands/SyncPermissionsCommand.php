<?php

namespace App\Console\Commands;

use App\Enums\UserTypeEnum;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'permission:sync {--fresh : Delete existing permissions before syncing}';

    protected $description = 'Sync permissions from config and assign all permissions to sys_admin';

    public function handle(): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionModel = config('permission.models.permission');
        $roleModel = config('permission.models.role');

        if ($this->option('fresh')) {
            $permissionModel::query()->delete();
        }

        $guard = 'api';

        $permissions = array_keys(config('permission_sync.permissions', []));

        foreach ($permissions as $permission) {
            $permissionModel::findOrCreate($permission, $guard);
        }

        $sysAdminRole = $roleModel::findOrCreate(UserTypeEnum::SYS_ADMIN->value, $guard);

        $sysAdminRole->syncPermissions(
            $permissionModel::query()->pluck('name')->all()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Permissions synced and assigned to sys_admin.');

        return self::SUCCESS;
    }
}