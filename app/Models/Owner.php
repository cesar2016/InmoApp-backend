<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'dni',
        'address',
        'whatsapp',
        'email',
    ];

    public function properties()
    {
        return $this->hasMany(Property::class);
    }
}
