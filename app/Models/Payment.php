<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'contract_id',
        'amount',
        'detail',
        'subtotal',
        'credit_balance',
        'debit_balance',
        'total',
        'payment_date',
        'period_month',
        'period_year',
        'receipt_number',
        'note',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
