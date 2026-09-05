<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\BusinessApprovalStatus;
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
                'first_name' => 'Süpervizör',
                'last_name' => 'Admin',
                'city' => 'Osmaniye',
                'district' => 'Merkez',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'LocalAdmin123!')),
                'role' => UserRole::SUPERVISOR,
                'email_verified_at' => now(),
                'account_status' => AccountStatus::ACTIVE,
            ]
        );

        // 2. Emlakçı Kullanıcısı
        $agent = User::updateOrCreate(
            ['email' => 'emlakci@test.com'],
            [
                'name' => 'Emlak Ofisi Yetkilisi',
                'first_name' => 'Emlak Ofisi',
                'last_name' => 'Yetkilisi',
                'phone' => '905553607769',
                'phone_verified_at' => now(),
                'two_factor_enabled' => false,
                'city' => 'Osmaniye',
                'district' => 'Merkez',
                'password' => Hash::make(env('SEED_AGENT_PASSWORD', 'LocalAgent123!')),
                'role' => UserRole::AGENT,
                'email_verified_at' => now(),
                'account_status' => AccountStatus::ACTIVE,
                'eids_identity_verified_at' => now(),
            ]
        );

        $agent->businessProfile()->updateOrCreate(
            [],
            [
                'office_name' => 'Test Emlak Ofisi',
                'license_number' => 'TEST-YB-2026-001',
                'tax_office' => 'Osmaniye',
                'tax_number' => '1111111111',
                'tax_number_hash' => hash('sha256', '1111111111'),
                'business_phone' => '905553607769',
                'city' => 'Osmaniye',
                'district' => 'Merkez',
                'address' => 'Alibeyli Mahallesi, Osmaniye',
                'approval_status' => BusinessApprovalStatus::APPROVED,
                'reviewed_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'bireysel@test.com'],
            [
                'name' => 'Bireysel Test Kullanıcısı',
                'first_name' => 'Bireysel Test',
                'last_name' => 'Kullanıcısı',
                'phone' => '905551112233',
                'phone_verified_at' => now(),
                'two_factor_enabled' => false,
                'identity_number' => '10000000146',
                'identity_number_hash' => hash('sha256', '10000000146'),
                'city' => 'Osmaniye',
                'district' => 'Kadirli',
                'password' => Hash::make(env('SEED_INDIVIDUAL_PASSWORD', 'LocalIndividual123!')),
                'role' => UserRole::INDIVIDUAL,
                'email_verified_at' => now(),
                'account_status' => AccountStatus::ACTIVE,
                'eids_identity_verified_at' => now(),
            ]
        );

        // 3. Haber Editörü / Blogger
        User::updateOrCreate(
            ['email' => 'editor@test.com'],
            [
                'name' => 'Haber Editörü',
                'first_name' => 'Haber',
                'last_name' => 'Editörü',
                'city' => 'Osmaniye',
                'district' => 'Merkez',
                'password' => Hash::make(env('SEED_EDITOR_PASSWORD', 'LocalEditor123!')),
                'role' => UserRole::EDITOR,
                'email_verified_at' => now(),
                'account_status' => AccountStatus::ACTIVE,
            ]
        );
    }
}
