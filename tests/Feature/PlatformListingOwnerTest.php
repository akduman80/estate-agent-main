<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource\Pages\CreateListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformListingOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_select_self_for_platform_listing(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPERVISOR]);
        $this->actingAs($admin);
        $data = $this->prepare(['user_id' => $admin->id, 'title' => 'Platform ilanı']);
        $this->assertSame($admin->id, $data['user_id']);
        $this->assertSame('zydemlak', $data['source_type']);
        $this->assertNull($data['business_profile_id']);
    }

    public function test_agent_cannot_select_supervisor_to_use_platform_identity(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPERVISOR]);
        $agent = User::factory()->create(['role' => UserRole::AGENT]);
        $this->actingAs($agent);
        $data = $this->prepare(['user_id' => $admin->id, 'title' => 'Agent ilanı', 'source_type' => 'zydemlak']);
        $this->assertSame($agent->id, $data['user_id']);
        $this->assertSame('real_estate_office', $data['source_type']);
    }

    public function test_supervisor_selecting_agent_preserves_agent_ownership(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPERVISOR]);
        $agent = User::factory()->create(['role' => UserRole::AGENT]);
        $this->actingAs($admin);
        $data = $this->prepare(['user_id' => $agent->id, 'title' => 'Agent adına']);
        $this->assertSame($agent->id, $data['user_id']);
        $this->assertSame('real_estate_office', $data['source_type']);
    }

    private function prepare(array $data): array
    {
        $page = new class extends CreateListing
        {
            public function prepare(array $data): array
            {
                return $this->mutateFormDataBeforeCreate($data);
            }
        };

        return $page->prepare($data);
    }
}
