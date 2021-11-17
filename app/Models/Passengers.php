<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Passengers extends Model
{
    protected $fillable = [
        'booking_id',
        'first_name',
        'last_name',
        'birth_date',
        'document_number'
    ];
    use HasFactory;
}
