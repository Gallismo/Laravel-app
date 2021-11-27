<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Airports;
use App\Models\Flights;
use Illuminate\Support\Facades\Validator;

class AirportsController extends Controller
{
    public function searchAirports(Request $request)
    {
        $query = $request->query('query');
        $data = Airports::where('city', 'LIKE', "%$query%")->orWhere('name', "LIKE", "%$query%")->orWhere('iata', "LIKE", "%$query%")->first();
        if(is_null($data)) {
            return response()->json(['data' => ['items' => []]], 200);
        }
        return response()->json(['data' =>['items' => [['name' => $data->name, 'iata' => $data->iata]]]], 200);
    }


    public function flights(Request $request)
    {
        $val = Validator::make($request->all(), [
            'from' => 'required',
            'to' => 'required',
            'date1' => 'required|date_format:Y-m-d',
            'date2' => 'date_format:Y-m-d',
            'passengers' => 'required|integer|min:1|max:10'
        ]);

        if($val->fails()) {
            return response()->json(['error' =>['code' => '422', 'message' => 'Validation error', 'errors' => $val->errors()]], 422);
        }
        $data = [
            'from' => $request->from,
            'to' => $request->to,
            'date1' => $request->date1,
            'date2' => $request->date2,
            'passengers' => $request->passengers
        ];

        $airFrom = Airports::where('iata', '=', $data['from'])->first();
        $airTo = Airports::where('iata', '=', $data['to'])->first();

        $flightTo = Flights::where('from_id', '=', $airFrom->id)->where('to_id', '=', $airTo->id)->get();

        $result =[];

        foreach ($flightTo as $to) {
            $result['data']['flights_to'][] = [
                'flight_id' => $to->id,
                'flight_code' => $to->flight_code,
                'from' => [
                    'city' => $airFrom->city,
                    'airport' => $airFrom->name,
                    'iata' => $airFrom->iata,
                    'date' => $data['date1'],
                    'time' => $to->time_from
                ],
                'to' => [
                    'city' => $airTo->city,
                    'airport' => $airTo->name,
                    'iata' => $airTo->iata,
                    'date' => $data['date1'],
                    'time' => $to->time_to
                ],
                'cost' => $to->cost,
                'availability' => 156
            ];
        }

        if (!is_null($data['date2'])) {
            $flightBack = Flights::where('from_id', '=', $airTo->id)->where('to_id', '=', $airFrom->id)->get();
        }

        foreach ($flightBack as $to) {
            $result['data']['flights_back'][] = [
                'flight_id' => $to->id,
                'flight_code' => $to->flight_code,
                'from' => [
                    'city' => $airTo->city,
                    'airport' => $airTo->name,
                    'iata' => $airTo->iata,
                    'date' => $data['date2'],
                    'time' => $to->time_from
                ],
                'to' => [
                    'city' => $airFrom->city,
                    'airport' => $airFrom->name,
                    'iata' => $airFrom->iata,
                    'date' => $data['date2'],
                    'time' => $to->time_to
                ],
                'cost' => $to->cost,
                'availability' => 156
            ];
        }

        return response()->json($result, 200);
    }
}
