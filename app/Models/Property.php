<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable = [
        'type',
        'listing_type',
        'real_estate_id',
        'domain',
        'street',
        'number',
        'floor',
        'dept',
        'location',
        'owner_id',
    ];

    protected $appends = ['is_rented'];

    public function getIsRentedAttribute()
    {
        return $this->activeContract()->exists();
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContract()
    {
        return $this->hasOne(Contract::class)->where('is_active', true);
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }
}
