<?php

namespace Database\Seeders;

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
                'name'     => 'Süpervizör Admin',
                'password' => Hash::make('12345678'),
                'role'     => UserRole::SUPERVISOR,
            ]
        );

        // 2. Emlakçı Kullanıcısı
        User::updateOrCreate(
            ['email' => 'emlakci@test.com'],
            [
                'name'     => 'Emlak Ofisi Yetkilisi',
                'password' => Hash::make('12345678'),
                'role'     => UserRole::AGENT,
            ]
        );

        // 3. Haber Editörü / Blogger
        User::updateOrCreate(
            ['email' => 'editor@test.com'],
            [
                'name'     => 'Haber Editörü',
                'password' => Hash::make('12345678'),
                'role'     => UserRole::EDITOR,
            ]
        );
    }
}
