<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sector',
        'status',
    ];

    public function userCompanyRoles()
    {
        return $this->hasMany(UserCompanyRole::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function invites()
    {
        return $this->hasMany(CompanyInvite::class);
    }
}
