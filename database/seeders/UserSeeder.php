<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Süpervizör / Admin Kullanıcısı
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Süpervizör Admin',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'LocalAdmin123!')),
                'role' => UserRole::SUPERVISOR,
                'email_verified_at' => now(),
                'account_status' => AccountStatus::ACTIVE,
            ]
        );

        // 2. Emlakçı Kullanıcısı
        User::updateOrCreate(
            ['email' => 'emlakci@test.com'],
            [
                'name' => 'Emlak Ofisi Yetkilisi',
                'password' => Hash::make(env('SEED_AGENT_PASSWORD', 'LocalAgent123!')),
                'role' => UserRole::AGENT,
                'email_verified_at' => now(),
                'account_status' => AccountStatus::ACTIVE,
            ]
        );

        // 3. Haber Editörü / Blogger
        User::updateOrCreate(
            ['email' => 'editor@test.com'],
            [
                'name' => 'Haber Editörü',
                'password' => Hash::make(env('SEED_EDITOR_PASSWORD', 'LocalEditor123!')),
                'role' => UserRole::EDITOR,
                'email_verified_at' => now(),
                'account_status' => AccountStatus::ACTIVE,
            ]
        );
    }
}
