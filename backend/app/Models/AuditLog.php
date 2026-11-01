<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['id', 'actor_id', 'organization_id', 'action', 'resource_type', 'resource_id', 'metadata', 'request_id'])]
class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $keyType = 'string';
    public $incrementing = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }
}