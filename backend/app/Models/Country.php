<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'countries';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['iso2', 'name_mk', 'name_en'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}