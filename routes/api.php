<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/register', [\App\Http\Controllers\UserController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\UserController::class, 'token']);
Route::middleware('bearer')->group(function () {
    Route::get('/user', [\App\Http\Controllers\UserController::class, 'getUser']);
});

Route::get('/searchAirports', [\App\Http\Controllers\AirportsController::class, 'searchAirports']);
Route::get('/flight', [\App\Http\Controllers\AirportsController::class, 'flights']);
Route::post('/booking', [\App\Http\Controllers\BookingController::class, 'booking']);
Route::get('/booking/{code}', [\App\Http\Controllers\BookingController::class, 'currentBooking']);
Route::get('/booking/{code}/seat', [\App\Http\Controllers\BookingController::class, 'seatCheck']);
Route::patch('/booking/{code}/seat', [\App\Http\Controllers\BookingController::class, 'chooseSeat']);

Route::middleware('bearer')->group(function () {
    Route::get('/user/booking', [\App\Http\Controllers\BookingController::class, 'getUserBooking']);
});