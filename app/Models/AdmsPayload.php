<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmsPayload extends Model
{
    protected $guarded = [];

    // Assuming this table is in the central database, but we are inside the master connection by default
    // If the app runs in tenancy context, we might need to specify the connection.
    // Assuming the default connection is 'mysql', we can force it just in case.
    protected $connection = 'mysql';

    public function tenant()
    {
        return $this->belongsTo(Organisation::class, 'tenant_id');
    }
}
