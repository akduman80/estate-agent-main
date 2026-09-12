<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\AgentConsultant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgentConsultantService
{
    public const MAX_ACTIVE_CONSULTANTS = 3;

    public function create(User $agent, array $data): User
    {
        abort_unless($agent->role === UserRole::AGENT && $agent->isActive(), 403);

        return DB::transaction(function () use ($agent, $data): User {
            $activeCount = AgentConsultant::query()->where('agent_id', $agent->id)->where('status', 'active')->lockForUpdate()->count();
            if ($activeCount >= self::MAX_ACTIVE_CONSULTANTS) {
                throw ValidationException::withMessages(['consultant' => 'Bir agent en fazla 3 aktif danışman ekleyebilir.']);
            }

            $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
            if (User::query()->where('email', $email)->exists()) {
                throw ValidationException::withMessages(['email' => 'Bu e-posta adresi zaten kayıtlı.']);
            }

            $consultant = User::create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'role' => UserRole::CONSULTANT,
                'account_status' => AccountStatus::ACTIVE,
                'email_verified_at' => now(),
            ]);

            AgentConsultant::create([
                'agent_id' => $agent->id,
                'consultant_id' => $consultant->id,
                'status' => 'active',
                'assigned_at' => now(),
            ]);

            return $consultant;
        });
    }

    public function canWorkFor(User $consultant, User $agent): bool
    {
        return $consultant->role === UserRole::CONSULTANT
            && $agent->role === UserRole::AGENT
            && $agent->isActive()
            && AgentConsultant::query()
                ->where('agent_id', $agent->id)
                ->where('consultant_id', $consultant->id)
                ->where('status', 'active')
                ->exists();
    }

    public function assign(User $agent, User $consultant): AgentConsultant
    {
        abort_unless($agent->role === UserRole::AGENT && $consultant->role === UserRole::CONSULTANT, 403);

        return DB::transaction(function () use ($agent, $consultant): AgentConsultant {
            $existing = AgentConsultant::query()
                ->where('agent_id', $agent->id)
                ->where('consultant_id', $consultant->id)
                ->lockForUpdate()
                ->first();

            if ($existing?->status === 'active') {
                throw ValidationException::withMessages(['consultant' => 'Bu danışman zaten bu agent’a bağlı.']);
            }

            $count = AgentConsultant::query()->where('agent_id', $agent->id)->where('status', 'active')->lockForUpdate()->count();
            if ($count >= self::MAX_ACTIVE_CONSULTANTS) {
                throw ValidationException::withMessages(['agent' => 'Bir agent en fazla 3 aktif danışmana sahip olabilir.']);
            }

            return AgentConsultant::updateOrCreate(
                ['agent_id' => $agent->id, 'consultant_id' => $consultant->id],
                ['status' => 'active', 'assigned_at' => now(), 'revoked_at' => null],
            );
        });
    }
}
