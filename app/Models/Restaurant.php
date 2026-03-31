<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $fillable = [
        'name',
        'cui',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
