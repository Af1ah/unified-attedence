<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'organisation_id',
        'name',
        'location',
        'address',
        'phone_number',
        'pin_code',
    ];

    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }
}
