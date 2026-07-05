<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaInvoiceLine extends Model
{
    protected $fillable = [
        'proforma_invoice_id', 'product_id', 'batch_id', 'line_number',
        'quantity', 'unit_price', 'line_total', 'unit_of_measure', 'notes',
    ];

    protected $casts = [
        'line_number' => 'integer',
        'quantity'    => 'integer',
        'unit_price'  => 'decimal:4',
        'line_total'  => 'decimal:2',
    ];

    public function proformaInvoice() { return $this->belongsTo(ProformaInvoice::class); }
    public function product()         { return $this->belongsTo(Product::class); }
    public function batch()           { return $this->belongsTo(Batch::class); }
}
