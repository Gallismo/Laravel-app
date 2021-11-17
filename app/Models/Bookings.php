<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bookings extends Model
{
    protected $fillable = [
        'flight_from',
        'flight_back',
        'date_from',
        'date_back',
        'code'
    ];

    use HasFactory;
}
