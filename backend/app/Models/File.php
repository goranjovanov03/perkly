<?php

namespace App\Models;

use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['id', 'organization_id', 'uploader_id', 'original_name', 'stored_path', 'mime_type', 'size_bytes', 'sha256_hash'])]
class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'files';

    protected $keyType = 'string';
    public $incrementing = false;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'created_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }
}