<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\AgentConsultant;
use App\Models\Listing;
use App\Models\User;
use App\Services\AgentConsultantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AgentConsultantTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_add_up_to_three_consultants(): void
    {
        $agent = User::factory()->create(['role' => UserRole::AGENT, 'account_status' => AccountStatus::ACTIVE]);
        $service = app(AgentConsultantService::class);

        foreach (range(1, 3) as $index) {
            $service->create($agent, [
                'first_name' => 'Danışman', 'last_name' => (string) $index,
                'email' => "consultant{$index}@example.test", 'phone' => "555000000{$index}",
                'password' => 'StrongPassword1',
            ]);
        }

        $this->expectException(ValidationException::class);
        $service->create($agent, [
            'first_name' => 'Dördüncü', 'last_name' => 'Danışman',
            'email' => 'consultant4@example.test', 'phone' => '5550000004',
            'password' => 'StrongPassword1',
        ]);
    }

    public function test_consultant_only_sees_listings_created_by_themselves(): void
    {
        $agent = User::factory()->create(['role' => UserRole::AGENT]);
        $first = User::factory()->create(['role' => UserRole::CONSULTANT]);
        $second = User::factory()->create(['role' => UserRole::CONSULTANT]);
        AgentConsultant::create(['agent_id' => $agent->id, 'consultant_id' => $first->id, 'status' => 'active']);
        AgentConsultant::create(['agent_id' => $agent->id, 'consultant_id' => $second->id, 'status' => 'active']);

        Listing::withoutEvents(fn () => Listing::insert([
            ['user_id' => $agent->id, 'created_by_id' => $first->id, 'title' => 'Birinci', 'slug' => 'birinci', 'price' => 1, 'city_id' => '34', 'district_id' => '1'],
            ['user_id' => $agent->id, 'created_by_id' => $second->id, 'title' => 'İkinci', 'slug' => 'ikinci', 'price' => 1, 'city_id' => '34', 'district_id' => '1'],
        ]));

        $this->assertSame(['Birinci'], Listing::query()->where('created_by_id', $first->id)->pluck('title')->all());
        $this->assertSame(1, Listing::query()->where('created_by_id', $first->id)->count());
    }
}
