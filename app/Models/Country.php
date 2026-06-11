<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'code',
        'flag',
        'dial_code',
        'name',
        'region',
        'currency_code',
        'import_permitted',
        'import_license_required',
        'gmp_certificate_required',
        'product_registration_required',
        'regulatory_authority',
        'regulatory_status',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'import_permitted'              => 'boolean',
        'import_license_required'       => 'boolean',
        'gmp_certificate_required'      => 'boolean',
        'product_registration_required' => 'boolean',
        'is_active'                     => 'boolean',
    ];

    public function productRegistrations()
    {
        return $this->hasMany(ProductCountryRegistration::class);
    }
}
