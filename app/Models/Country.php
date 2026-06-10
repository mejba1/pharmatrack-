<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name',
        'iso_code',
        'region',
        'currency_code',
        'import_permitted',
        'license_required',
        'gmp_certificate_required',
        'regulatory_authority',
        'notes',
        'status',
    ];

    protected $casts = [
        'import_permitted'        => 'boolean',
        'license_required'        => 'boolean',
        'gmp_certificate_required'=> 'boolean',
    ];

    public function productRegistrations()
    {
        return $this->hasMany(ProductCountryRegistration::class);
    }
}
