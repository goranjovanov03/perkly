<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationConfigurationVersion extends Model
{
    protected $table = 'integration_configuration_versions';
    protected $primaryKey = ['connection_id', 'version_number'];
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['connection_id', 'version_number', 'config', 'created_by'];

    protected $casts = [
        'config' => 'json',
        'version_number' => 'integer',
        'created_at' => 'datetime',
    ];

    public function connection()
    {
        return $this->belongsTo(IntegrationConnection::class, 'connection_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}