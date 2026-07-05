<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSaleItem extends Model
{
    protected $fillable = [
        'customer_sale_id', 'product_id', 'batch_id',
        'quantity', 'unit_price', 'line_total',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function sale()    { return $this->belongsTo(CustomerSale::class, 'customer_sale_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function batch()   { return $this->belongsTo(Batch::class); }
}
