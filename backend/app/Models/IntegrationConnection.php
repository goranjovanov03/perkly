<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class IntegrationConnection extends Model
{
    use HasFactory;

    protected $table = 'integration_connections';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'organization_id', 'provider_key', 'name', 'status', 'last_health_status', 'last_health_at'];

    protected $casts = [
        'last_health_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::ulid();
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function provider()
    {
        return $this->belongsTo(IntegrationProvider::class, 'provider_key', 'key');
    }

    public function secrets()
    {
        return $this->hasMany(IntegrationSecret::class, 'connection_id', 'id');
    }

    public function configurationVersions()
    {
        return $this->hasMany(IntegrationConfigurationVersion::class, 'connection_id', 'id');
    }
}