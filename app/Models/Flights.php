<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flights extends Model
{
    protected $fillable = [
       'flight_code',
       'from_id',
       'to_id',
       'time_from',
       'time_to',
       'cost'
    ];
    use HasFactory;
}
