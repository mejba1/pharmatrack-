<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationDailyStat extends Model
{
    protected $fillable = ['day', 'total', 'genuine', 'suspicious', 'invalid', 'alerts'];

    protected $casts = [
        'day'        => 'date',
        'total'      => 'integer',
        'genuine'    => 'integer',
        'suspicious' => 'integer',
        'invalid'    => 'integer',
        'alerts'     => 'integer',
    ];
}
