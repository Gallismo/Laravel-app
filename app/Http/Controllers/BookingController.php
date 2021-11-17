<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Bookings;
use App\Models\Passengers;
use App\Models\Flights;
use App\Models\Airports;

class BookingController extends Controller
{
    public function booking(Request $request)
    {
        $val = Validator::make($request->all(), [
            'flight_from.id' => 'required|integer',
            'flight_from.date' => 'required|date_format:Y-m-d',
            'flight_back.id' => 'required|integer',
            'flight_back.date' => 'required|date_format:Y-m-d',
            'passengers.*.first_name' => 'required|string',
            'passengers.*.last_name' => 'required|string',
            'passengers.*.birth_date' => 'required|date_format:Y-m-d',
            'passengers.*.document_number' => 'required|string|min:10|max:10'
        ]);

        if($val->fails()) {
            return response()->json(['error' => ['code' => '422', 'message' => 'Validation error', 'errors' => $val->errors()]], 422);
        }

        $data = [
            'flight_from' => $request->flight_from,
            'flight_back' => $request->flight_back,
            'passengers' => $request->passengers
        ];
        $flight_from = $data['flight_from'];
        $flight_back = $data['flight_back'];
        $passengers = $data['passengers'];

        $chars = 'QWERTYUIOPASDFGHJKLZXCVBNM';
        $code = '';

        for ($i=0; $i < 5; $i++) { 
            $code .= substr($chars, rand(1, 15) - 1, 1);
        }

        $booking = Bookings::create([
            'flight_from' => $flight_from['id'],
            'flight_back' => $flight_back['id'],
            'date_from' => $flight_from['date'],
            'date_back' => $flight_back['date'],
            'code' => $code
        ]);

        foreach ($passengers as $passenger) {
            Passengers::create([
                'booking_id' => $booking->id,
                'first_name' => $passenger['first_name'],
                'last_name' => $passenger['last_name'],
                'birth_date'=> $passenger['birth_date'],
                'document_number' => $passenger['document_number']
            ]);
        }

        return response()->json(['data' => ['code' => $code]], 201);
    }

    public function currentBooking(Request $request, $code)
    {
        $booking = Bookings::where('code', '=', $code)->first();
        if (is_null($booking)) {
            return response()->json(['data' => []], 200);
        }
        $flights = Flights::where('id', '=', $booking->flight_from)->orWhere('id', '=', $booking->flight_back)->get();
        
        $flightRes = [];
        $cost = 0;
        foreach ($flights as $flight) {
            $airportFrom = Airports::find($flight->from_id);
            $airportTo = Airports::find($flight->to_id);
            $cost += $flight->cost;
            $flightRes[] = [
                'flight_id' => $flight->id,
                'flight_code' => $flight->flight_code,
                'from' => [
                    'city' => $airportFrom->city,
                    'airport' => $airportFrom->name,
                    'iata' => $airportFrom->iata,
                    'date' => $booking->date_from,
                    'time' => $flight->time_from
                ],
                'to' => [
                    'city' => $airportTo->city,
                    'airport' => $airportTo->name,
                    'iata' => $airportTo->iata,
                    'date' => $booking->date_from,
                    'time' => $flight->time_to
                ],
                'cost' => $flight->cost,
                'availability' => '56'
            ];
        }

        $passengers = Passengers::where('booking_id', '=', $booking->id)->get();
        $passengersRes = [];
        foreach ($passengers as $passenger) {
            $passengersRes[] = [
                'id' => $passenger->id,
                'first_name' => $passenger->first_name,
                'last_name' => $passenger->last_name,
                'birth_date' => $passenger->birth_date,
                'document_number' => $passenger->document_number,
                'place_from' => $passenger->place_from,
                'place_back' => $passenger->place_back
            ];
        }
        $passengersCount = count($passengersRes);
        $cost *= $passengersCount;
        $result = [
            'data' => [
                'code' => $code,
                'cost' => $cost,
                'flights' => $flightRes,
                'passengers' => $passengersRes
            ]
        ];
        return response()->json($result, 200);
    }

    public function seatCheck(Request $request, $code)
    {
        $currentBooking = Bookings::where('code', '=', $code)->first();
        $currentPassengers = Passengers::where('booking_id', '=', $currentBooking->id)->get();

        $bookingsFrom = Bookings::where('flight_from', '=', $currentBooking->flight_from)->where('date_from', '=', $currentBooking->date_from)->get();
        $passengersFrom = [];
        foreach ($bookingsFrom as $booking) {
            if (count(Passengers::where('booking_id', '=', $booking->id)->get()) > 0) {
                $passengersFrom[] = Passengers::where('booking_id', '=', $booking->id)->get();
            }
        }

        $bookingsBack = Bookings::where('flight_back', '=', $currentBooking->flight_back)->where('date_back', '=', $currentBooking->date_back)->get();
        $passengersBack = [];
        foreach ($bookingsBack as $booking) {
            if (count(Passengers::where('booking_id', '=', $booking->id)->get()) > 0) {
                $passengersBack[] = Passengers::where('booking_id', '=', $booking->id)->get();
            }
        }

        $occupied_from = [];
        foreach ($passengersFrom as $psFrom) {
            foreach ($psFrom as $pFrom) {
                $occupied_from[] = [
                    'passenger_id' => $pFrom->id,
                    'place' => $pFrom->place_from
                ];
            }
        }

        $occupied_back = [];
        foreach ($passengersBack as $psBack) {
            foreach ($psBack as $pBack) {
                $occupied_back[] = [
                    'passenger_id' => $pBack->id,
                    'place' => $pBack->place_back
                ];
            }
        }

        $data = [
            'data' => [
                'occupied_from' => $occupied_from,
                'occupied_back' => $occupied_back
            ]
        ];
        
        return response()->json($data, 200);
    }

    public function chooseSeat(Request $request, $code)
    {
        $val = Validator::make($request->all(), [
            'passenger' => 'required|integer',
            'seat' => 'required|string',
            'type' => 'required|string'
        ]);

        if($val->fails()) {
            return response()->json(['error' => ['code' => '422', 'message' => 'Validation error', 'errors' => $val->errors()]], 422);
        }

        $booking = Bookings::where('code', '=', $code)->first();
        $passenger = Passengers::where('id', '=', $request->passenger)->first();

        if($booking->id != $passenger->booking_id) {
            return response()->json(['error' => ['code' => '403', 'message' => 'Passenger does not apply to booking']], 403);
        }

        $currentBooking = Bookings::where('code', '=', $code)->first();
        $currentPassengers = Passengers::where('booking_id', '=', $currentBooking->id)->get();

        $bookingsFrom = Bookings::where('flight_from', '=', $currentBooking->flight_from)->where('date_from', '=', $currentBooking->date_from)->get();
        $passengersFrom = [];
        foreach ($bookingsFrom as $booking) {
            if (count(Passengers::where('booking_id', '=', $booking->id)->get()) > 0) {
                $passengersFrom[] = Passengers::where('booking_id', '=', $booking->id)->get();
            }
        }

        $bookingsBack = Bookings::where('flight_back', '=', $currentBooking->flight_back)->where('date_back', '=', $currentBooking->date_back)->get();
        $passengersBack = [];
        foreach ($bookingsBack as $booking) {
            if (count(Passengers::where('booking_id', '=', $booking->id)->get()) > 0) {
                $passengersBack[] = Passengers::where('booking_id', '=', $booking->id)->get();
            }
        }

        $occupied_from = [];
        foreach ($passengersFrom as $psFrom) {
            foreach ($psFrom as $pFrom) {
                $occupied_from[] = [
                    'passenger_id' => $pFrom->id,
                    'place' => $pFrom->place_from
                ];
            }
        }

        $occupied_back = [];
        foreach ($passengersBack as $psBack) {
            foreach ($psBack as $pBack) {
                $occupied_back[] = [
                    'passenger_id' => $pBack->id,
                    'place' => $pBack->place_back
                ];
            }
        }

        if ($request->type == 'from') {
            foreach ($occupied_from as $item) {
                if ($item['place'] == $request->seat) {
                    return response()->json(['error' => ['code' => '422', 'message' => 'Seat occupied']], 422);
                }
            }
            $passenger->place_from = $request->seat;
            $passenger->save();
        } elseif ($request->type == 'back') {
            foreach ($occupied_back as $item) {
                if ($item['place'] == $request->seat) {
                    return response()->json(['error' => ['code' => '422', 'message' => 'Seat occupied']], 422);
                }
            }
            $passenger->place_back = $request->seat;
            $passenger->save();
        } else {
            return response()->json(['error' => ['code' => '422', 'message' => 'Validation error', 'errors' => ['seat' => ['Seat value must be only from or back']]]], 422);
        }

        $data = [
            'id' => $passenger->id,
            'first_name' => $passenger->first_name,
            'last_name' => $passenger->last_name,
            'birth_date' => $passenger->birth_date,
            'document_number' => $passenger->document_number,
            'place_from' => $passenger->place_from,
            'place_back' => $passenger->place_back
        ];


        return response()->json(['data' => $data], 200);
    }

    public function getUserBooking(Request $request)
    {
        $userDocumentNumber = $request->user()->document_number;

        $passengerUser = Passengers::where('document_number', '=', $userDocumentNumber)->get();

        if (!count($passengerUser) > 0) {
            return response()->json(['data' => []], 200);
        }

        $userBookings = [];

        foreach ($passengerUser as $p) {
            $userBookings[] = Bookings::where('id', '=', $p->booking_id)->first();
        }

        $items = [];
        foreach ($userBookings as $booking) {

            $flights = Flights::where('id', '=', $booking->flight_from)->orWhere('id', '=', $booking->flight_back)->get();
        
            $flightRes = [];
            $cost = 0;
            foreach ($flights as $flight) {
                $airportFrom = Airports::find($flight->from_id);
                $airportTo = Airports::find($flight->to_id);
                $cost += $flight->cost;
                $flightRes[] = [
                    'flight_id' => $flight->id,
                    'flight_code' => $flight->flight_code,
                    'from' => [
                        'city' => $airportFrom->city,
                        'airport' => $airportFrom->name,
                        'iata' => $airportFrom->iata,
                        'date' => $booking->date_from,
                        'time' => $flight->time_from
                    ],
                    'to' => [
                        'city' => $airportTo->city,
                        'airport' => $airportTo->name,
                        'iata' => $airportTo->iata,
                        'date' => $booking->date_from,
                        'time' => $flight->time_to
                    ],
                    'cost' => $flight->cost,
                    'availability' => '56'
                ];
            }

            $passengers = Passengers::where('booking_id', '=', $booking->id)->get();
            $passengersRes = [];
            foreach ($passengers as $passenger) {
                $passengersRes[] = [
                    'id' => $passenger->id,
                    'first_name' => $passenger->first_name,
                    'last_name' => $passenger->last_name,
                    'birth_date' => $passenger->birth_date,
                    'document_number' => $passenger->document_number,
                    'place_from' => $passenger->place_from,
                    'place_back' => $passenger->place_back
                ];
            }
            $passengersCount = count($passengersRes);
            $cost *= $passengersCount;
            $items[] = [
                    'code' => $booking->code,
                    'cost' => $cost,
                    'flights' => $flightRes,
                    'passengers' => $passengersRes
            ];
        }

        return response()->json(['data' => ['items' => $items]], 200);
    }
}
