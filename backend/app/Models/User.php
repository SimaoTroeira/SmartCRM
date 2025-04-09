<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    //codigo dos roles

    // No modelo User.php
    public function companyRoles()
    {
        return $this->hasMany(UserCompanyRole::class, 'user_id');
    }


    public function isSuperAdmin()
    {
        return $this->email === 'admin@admin.com'; // ou verifica pelo role no futuro
    }

    public function roleInCompany($companyId)
    {
        return $this->companyRoles()->where('company_id', $companyId)->first();
    }

    public function hasRole($roleCode)
    {
        return $this->companyRoles()
            ->whereHas('role', function ($query) use ($roleCode) {
                $query->where('code', $roleCode);
            })
            ->exists();
    }
}
