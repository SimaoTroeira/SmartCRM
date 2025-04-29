<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{
    use HasFactory;

    protected $table = 'datasets';

    protected $fillable = [
        'company_id',
        'campaign_id',
        'company_name',
        'sector',
        'number_of_employees',
        'annual_revenue',
        'address',
        'website',
        'created_at',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    // Permite filtrar datasets por empresa logada atualmente
    public function scopeForCurrentCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
