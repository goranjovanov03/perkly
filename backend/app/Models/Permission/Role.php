<?php

namespace App\Models\Permission;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Support\Str;

class Role extends SpatieRole
{
    protected $keyType = 'string';
    public $incrementing = false;

    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] ??= \Spatie\Permission\Guard::getDefaultName(static::class);

        parent::__construct($attributes);
        
        $this->guarded = [];
        $this->table = config('permission.table_names.roles') ?: parent::getTable();
    }

    protected static function booted(): void
    {
        static::creating(function ($role) {
            if (empty($role->id)) {
                $role->id = (string) Str::ulid();
            }
        });
    }
}