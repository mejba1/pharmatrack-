<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoicePayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'commercial_invoice_id', 'customer_id', 'amount', 'currency',
        'paid_on', 'method', 'reference', 'note', 'recorded_by',
    ];

    protected $casts = [
        'paid_on' => 'date',
        'amount'  => 'decimal:2',
    ];

    public const METHODS = [
        'bank_transfer' => 'Bank transfer',
        'cash'          => 'Cash',
        'cheque'        => 'Cheque',
        'card'          => 'Card',
        'other'         => 'Other',
    ];

    public function commercialInvoice() { return $this->belongsTo(CommercialInvoice::class); }
    public function customer()          { return $this->belongsTo(Customer::class); }
    public function recorder()          { return $this->belongsTo(User::class, 'recorded_by'); }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->method] ?? ucfirst((string) $this->method);
    }
}
