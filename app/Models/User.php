<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class, // Enum Casting
        ];
    }

    // Panel Erişim Kontrolü
public function canAccessPanel(Panel $panel): bool
{
    // Giriş (Login) kontrolü için admin paneline herkesin giriş yapabilmesine izin veriyoruz.
    // Kullanıcı giriş yaptıktan sonra LoginResponse ile kendi rolünün paneline uçurulacak.
    return match ($panel->getId()) {
        'admin'  => true, // Giriş kapısı ortak olduğu için true yapıyoruz
        'portal' => $this->role === UserRole::AGENT || $this->role === UserRole::SUPERVISOR,
        'editor' => $this->role === UserRole::EDITOR || $this->role === UserRole::SUPERVISOR,
        default  => false,
    };
}
}
