<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialInvoiceLine extends Model
{
    protected $fillable = [
        'commercial_invoice_id', 'proforma_invoice_line_id', 'product_id', 'batch_id',
        'line_number', 'product_description', 'quantity', 'unit_price', 'line_total',
        'discount_amount', 'unit_of_measure', 'net_weight_kg', 'gross_weight_kg', 'notes',
    ];

    protected $casts = [
        'line_number'     => 'integer',
        'quantity'        => 'integer',
        'unit_price'      => 'decimal:4',
        'line_total'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_weight_kg'   => 'decimal:3',
        'gross_weight_kg' => 'decimal:3',
    ];

    /** Line total after the per-product discount. */
    public function getNetTotalAttribute(): float
    {
        return max(0, (float) $this->line_total - (float) $this->discount_amount);
    }

    public function commercialInvoice()  { return $this->belongsTo(CommercialInvoice::class); }
    public function proformaInvoiceLine() { return $this->belongsTo(ProformaInvoiceLine::class); }
    public function product()            { return $this->belongsTo(Product::class); }
    public function batch()              { return $this->belongsTo(Batch::class); }
}
