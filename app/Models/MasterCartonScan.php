<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterCartonScan extends Model
{
    protected $fillable = [
        'master_carton_id', 'event', 'performed_by', 'location', 'note',
    ];

    public function carton()
    {
        return $this->belongsTo(MasterCarton::class, 'master_carton_id');
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'dispatched' => 'Factory Dispatch',
            'received'   => 'Depot Received',
            default      => 'QR Scan',
        };
    }

    public function getEventIconAttribute(): string
    {
        return match ($this->event) {
            'dispatched' => 'bi-box-arrow-up',
            'received'   => 'bi-box-arrow-in-down',
            default      => 'bi-qr-code-scan',
        };
    }
}
