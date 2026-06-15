<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsignmentScan extends Model
{
    protected $fillable = [
        'consignment_id', 'event', 'performed_by', 'location', 'ip_address', 'note',
    ];

    public function consignment()
    {
        return $this->belongsTo(Consignment::class);
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'created'    => 'Shipment Created',
            'dispatched' => 'Dispatched from Factory',
            'in_transit' => 'In Transit',
            'received'   => 'Received',
            default      => 'QR Scan',
        };
    }

    public function getEventIconAttribute(): string
    {
        return match ($this->event) {
            'created'    => 'bi-plus-circle',
            'dispatched' => 'bi-box-arrow-up',
            'in_transit' => 'bi-truck',
            'received'   => 'bi-box-arrow-in-down',
            default      => 'bi-qr-code-scan',
        };
    }
}
