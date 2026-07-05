<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bank_name', 'account_name', 'account_number', 'swift_code',
        'iban', 'branch', 'address', 'currency', 'is_default', 'is_active', 'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    /** Label shown in the bank picker, e.g. "HSBC — •••4321". */
    public function getLabelAttribute(): string
    {
        $tail = $this->account_number ? ' — •••' . substr($this->account_number, -4) : '';

        return $this->bank_name . $tail;
    }
}
