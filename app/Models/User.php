<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const PERFIL_SUPER = 'superuser';

    public const PERFIL_CONSULTA = 'consulta';

    protected $fillable = [
        'name',
        'usuario',
        'email',
        'password',
        'perfil',
        'setor',
        'bloqueado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'bloqueado' => 'boolean',
        ];
    }

    public function isSuper(): bool
    {
        return strtolower(trim((string) $this->perfil)) === self::PERFIL_SUPER;
    }
}
