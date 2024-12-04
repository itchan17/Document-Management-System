<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Yebor974\Filament\RenewPassword\Contracts\RenewPasswordContract;
use Yebor974\Filament\RenewPassword\Traits\RenewPassword;




class User extends Authenticatable implements RenewPasswordContract
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, RenewPassword;



    const ROLE_SUPER_ADMIN = 'SUPER ADMIN';
    const ROLE_ADMIN = 'ADMIN';

    const ROLES = [
        self::ROLE_SUPER_ADMIN => 'Super_Admin',
        self::ROLE_ADMIN => 'Admin',
    ];



    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    public function isSuperAdmin(){
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(){
        return $this->role === self::ROLE_ADMIN;
    }


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'lastname',
        'role',
        'email',
        'password',
        'force_renew_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'password' => 'hashed',
        ];
    }
}
