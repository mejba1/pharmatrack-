<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalSettingLog extends Model
{
    protected $fillable = ['user_id', 'summary', 'changes'];

    protected $casts = ['changes' => 'array'];

    public function user() { return $this->belongsTo(User::class); }
}
