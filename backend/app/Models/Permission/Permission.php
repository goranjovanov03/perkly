<?php

namespace App\Models\Permission;

use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Support\Str;

class Permission extends SpatiePermission
{
    protected $keyType = 'string';
    public $incrementing = false;

    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] ??= \Spatie\Permission\Guard::getDefaultName(static::class);

        parent::__construct($attributes);
        
        $this->guarded = [];
        $this->table = config('permission.table_names.permissions') ?: parent::getTable();
    }

    protected static function booted(): void
    {
        static::creating(function ($permission) {
            if (empty($permission->id)) {
                $permission->id = (string) Str::ulid();
            }
        });
    }
}