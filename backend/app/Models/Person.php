<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Person extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['first_name', 'last_name', 'email', 'phone', 'birth_date'];

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}