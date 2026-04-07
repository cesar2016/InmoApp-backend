<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $fillable = [
        'property_id',
        'description',
        'cost',
        'date',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
