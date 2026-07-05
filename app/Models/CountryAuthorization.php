<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountryAuthorization extends Model
{
    protected $fillable = ['product_id', 'country_code', 'country_name'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
