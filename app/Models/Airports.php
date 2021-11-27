<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Airports extends Model
{
    protected $fillable = [
        'city',
        'name',
        'iata'
    ];
    use HasFactory;
}
