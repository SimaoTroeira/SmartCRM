<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'sector',
        'number_of_employees',
        'annual_revenue',
        'address',
        'website',
        'created_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForCurrentUser($query)
    {
        return $query->where('user_id', auth()->id());
    }
}