<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Locale extends Model
{
    protected $table = 'locales';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'name', 'is_default'];

    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}