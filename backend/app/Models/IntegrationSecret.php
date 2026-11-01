<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class IntegrationSecret extends Model
{
    protected $table = 'integration_secrets';
    protected $primaryKey = ['connection_id', 'key'];
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['connection_id', 'key', 'ciphertext'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function connection()
    {
        return $this->belongsTo(IntegrationConnection::class, 'connection_id', 'id');
    }

    public function getPlaintextAttribute(): string
    {
        return Crypt::decryptString($this->ciphertext);
    }

    public function setPlaintextAttribute(string $value): void
    {
        $this->attributes['ciphertext'] = Crypt::encryptString($value);
    }
}