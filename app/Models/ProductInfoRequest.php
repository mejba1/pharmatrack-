<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductInfoRequest extends Model
{
    protected $fillable = [
        'uuc_code', 'product_id', 'batch_id',
        'name', 'email', 'phone', 'country', 'city', 'address',
        'partner_name', 'partner_phone', 'whatsapp', 'emailed',
    ];

    protected $casts = ['emailed' => 'boolean'];

    public function product() { return $this->belongsTo(Product::class); }
    public function batch()   { return $this->belongsTo(Batch::class); }
}
