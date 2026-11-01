<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationServiceType extends Model
{
    protected $table = 'integration_service_types';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'name', 'config_schema'];

    protected $casts = [
        'config_schema' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function providers()
    {
        return $this->hasMany(IntegrationProvider::class, 'service_type', 'key');
    }
}