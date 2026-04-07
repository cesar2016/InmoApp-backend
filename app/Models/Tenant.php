<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'dni',
        'address',
        'whatsapp',
        'email',
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function guarantors()
    {
        return $this->hasMany(Guarantor::class);
    }

    public function activeContract()
    {
        return $this->hasOne(Contract::class)->where('is_active', true);
    }
}
