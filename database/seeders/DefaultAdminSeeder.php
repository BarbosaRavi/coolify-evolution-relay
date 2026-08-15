<?php

namespace Database\Seeders;

use App\Enums\UserTypeEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultAdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('default_admin.password');

        if (blank($password)) {
            $this->command?->warn('DEFAULT_ADMIN_PASSWORD não definida — nenhum administrador foi criado.');

            return;
        }

        $email = config('default_admin.email');

        if (User::query()->withTrashed()->where('email', $email)->exists()) {
            $this->command?->info("Administrador {$email} já existe — nada a fazer.");

            return;
        }

        $user = User::create([
            'name' => config('default_admin.name'),
            'email' => $email,
            'user_type' => UserTypeEnum::SYS_ADMIN,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $user->assignRole(UserTypeEnum::SYS_ADMIN->value)->save();

        $this->command?->info("Administrador {$email} criado.");
    }
}
