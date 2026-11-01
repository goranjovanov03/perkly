<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationProvider extends Model
{
    protected $table = 'integration_providers';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'service_type', 'name'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function serviceType()
    {
        return $this->belongsTo(IntegrationServiceType::class, 'service_type', 'key');
    }

    public function connections()
    {
        return $this->hasMany(IntegrationConnection::class, 'provider_key', 'key');
    }
}