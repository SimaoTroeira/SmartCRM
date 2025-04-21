<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'email',
        'token',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function isExpired()
    {
        return $this->expires_at?->isPast(); // null-safe para evitar erro se for null
    }

    public function isAccepted()
    {
        return $this->accepted_at !== null;
    }
}

