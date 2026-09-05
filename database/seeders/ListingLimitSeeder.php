<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ListingLimitSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'limit-dolu@test.com'],
            [
                'name' => 'Limiti Dolu Test Kullanıcısı',
                'first_name' => 'Limiti Dolu',
                'last_name' => 'Test Kullanıcısı',
                'phone' => '905559999999',
                'phone_verified_at' => now(),
                'city' => 'Osmaniye',
                'district' => 'Merkez',
                'listing_limit_override' => 2,
                'password' => Hash::make(env('SEED_LIMIT_USER_PASSWORD', 'LocalLimit123!')),
                'role' => UserRole::INDIVIDUAL,
                'email_verified_at' => now(),
                'account_status' => AccountStatus::ACTIVE,
                'eids_identity_verified_at' => now(),
            ]
        );

        Listing::withoutEvents(function () use ($user): void {
            foreach (range(1, 2) as $number) {
                Listing::updateOrCreate(
                    ['slug' => 'limit-testi-'.$number],
                    [
                        'user_id' => $user->id,
                        'source_type' => 'owner',
                        'title' => 'Limit Test İlanı '.$number,
                        'description' => 'Yerel ilan limiti testi için oluşturulmuştur.',
                        'price' => 1000000 + ($number * 100000),
                        'currency' => 'TRY',
                        'type' => 'Satılık',
                        'category' => 'Konut',
                        'eids_status' => 'verified',
                        'publication_status' => 'approved',
                        'published_at' => now(),
                        'city_id' => 'Osmaniye',
                        'district_id' => 'Merkez',
                        'neighborhood_id' => 'Alibeyli',
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
