<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $table = 'currencies';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'name_mk', 'name_en', 'minor_units'];

    protected $casts = [
        'minor_units' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}