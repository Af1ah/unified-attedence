<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Organisation extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $table = 'organisations';

    protected $fillable = [
        'name',
        'shortname',
        'db_name',
        'email',
        'phone',
        'logo',
        'brand_color',
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}
