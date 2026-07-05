<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerNotification extends Model
{
    protected $fillable = ['customer_id', 'type', 'title', 'message', 'icon', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function customer() { return $this->belongsTo(Customer::class); }
}
