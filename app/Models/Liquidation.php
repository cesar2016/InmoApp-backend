<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Liquidation extends Model
{
    protected $fillable = [
        'owner_id',
        'contract_id',
        'month',
        'year',
        'alquiler',
        'tasa_municipal',
        'pago_tasa_municipal',
        'recargo',
        'pago_luz',
        'descuento_admin',
        'total_percibido',
        'total_liquidado',
        'note',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
