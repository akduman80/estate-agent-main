<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\BusinessApprovalStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\SupervisorUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SupervisorUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_open_user_create_and_edit_pages(): void
    {
        $supervisor = $this->supervisor();
        $member = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Üye',
            'role' => UserRole::INDIVIDUAL,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($supervisor)
            ->get('/admin/users/create')
            ->assertOk()
            ->assertSee('Hesap bilgileri');

        $this->actingAs($supervisor)
            ->get("/admin/users/{$member->id}/edit")
            ->assertOk()
            ->assertSee('Konum ve kimlik bilgileri');
    }

    public function test_service_creates_an_individual_with_encrypted_identity_and_hashed_password(): void
    {
        $user = app(SupervisorUserService::class)->create($this->individualData());

        $this->assertSame(UserRole::INDIVIDUAL, $user->role);
        $this->assertSame(AccountStatus::ACTIVE, $user->account_status);
        $this->assertSame('Ramazan Orak', $user->name);
        $this->assertSame('Osmaniye', $user->city);
        $this->assertSame('Merkez', $user->district);
        $this->assertSame('Alibeyli Mahallesi', $user->neighborhood);
        $this->assertSame(12, $user->listing_limit_override);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->phone_verified_at);
        $this->assertTrue(Hash::check('GucluSifre123', $user->password));

        $rawIdentity = (string) DB::table('users')->where('id', $user->id)->value('identity_number');
        $this->assertNotSame('10000000146', $rawIdentity);
        $this->assertSame(hash('sha256', '10000000146'), $user->identity_number_hash);
    }

    public function test_service_creates_and_updates_an_agent_without_overwriting_secrets(): void
    {
        $service = app(SupervisorUserService::class);
        $agent = $service->create($this->agentData());
        $originalPassword = $agent->password;
        $originalTaxHash = $agent->businessProfile->tax_number_hash;

        $rawTaxNumber = (string) DB::table('business_profiles')->where('user_id', $agent->id)->value('tax_number');
        $this->assertNotSame('1234567890', $rawTaxNumber);
        $this->assertSame(BusinessApprovalStatus::APPROVED, $agent->businessProfile->approval_status);

        $updated = $service->update($agent, array_merge($this->agentData(), [
            'first_name' => 'Güncel',
            'password' => null,
            'business_tax_number' => null,
            'business_office_name' => 'Güncel Emlak Ltd.',
        ]));

        $this->assertSame('Güncel Orak', $updated->name);
        $this->assertSame($originalPassword, $updated->password);
        $this->assertSame($originalTaxHash, $updated->businessProfile->tax_number_hash);
        $this->assertSame('Güncel Emlak Ltd.', $updated->businessProfile->office_name);
    }

    public function test_duplicate_identity_number_is_rejected(): void
    {
        $service = app(SupervisorUserService::class);
        $service->create($this->individualData());

        $this->expectException(ValidationException::class);

        $service->create(array_merge($this->individualData(), [
            'email' => 'ikinci@example.com',
        ]));
    }

    public function test_last_active_supervisor_cannot_remove_own_panel_access(): void
    {
        $supervisor = $this->supervisor();

        $this->expectException(ValidationException::class);

        app(SupervisorUserService::class)->update($supervisor, [
            'first_name' => 'Süpervizör',
            'last_name' => 'Admin',
            'email' => $supervisor->email,
            'role' => UserRole::EDITOR->value,
            'account_status' => AccountStatus::ACTIVE->value,
            'email_verified' => true,
            'password' => null,
        ]);
    }

    private function supervisor(): User
    {
        return User::factory()->create([
            'first_name' => 'Süpervizör',
            'last_name' => 'Admin',
            'role' => UserRole::SUPERVISOR,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);
    }

    private function individualData(): array
    {
        return [
            'first_name' => 'Ramazan',
            'last_name' => 'Orak',
            'email' => 'ramazan@example.com',
            'phone' => '05551112233',
            'role' => UserRole::INDIVIDUAL->value,
            'account_status' => AccountStatus::ACTIVE->value,
            'password' => 'GucluSifre123',
            'city' => 'Osmaniye',
            'district' => 'Merkez',
            'neighborhood' => 'Alibeyli Mahallesi',
            'identity_number' => '10000000146',
            'listing_limit_override' => 12,
            'email_verified' => true,
            'phone_verified' => false,
            'two_factor_enabled' => false,
        ];
    }

    private function agentData(): array
    {
        return [
            'first_name' => 'Ramazan',
            'last_name' => 'Orak',
            'email' => 'emlakci@example.com',
            'phone' => '05551112233',
            'role' => UserRole::AGENT->value,
            'account_status' => AccountStatus::ACTIVE->value,
            'password' => 'GucluSifre123',
            'city' => 'Osmaniye',
            'district' => 'Merkez',
            'neighborhood' => 'Alibeyli Mahallesi',
            'listing_limit_override' => null,
            'email_verified' => true,
            'phone_verified' => false,
            'two_factor_enabled' => false,
            'business_office_name' => 'Test Emlak Ltd.',
            'business_license_number' => 'YB-2026-001',
            'business_tax_office' => 'Osmaniye',
            'business_tax_number' => '1234567890',
            'business_phone' => '03281112233',
            'business_address' => 'Alibeyli Mahallesi Osmaniye',
            'business_approval_status' => BusinessApprovalStatus::APPROVED->value,
            'business_review_note' => 'Belgeleri kontrol edildi.',
        ];
    }
}
