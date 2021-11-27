<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;

class UserController extends Controller
{
    public function register    (Request $request)
    {
        $val = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => 'required|string|unique:users',
            'document_number' => 'required|string|max:10',
            'password' => 'required|string'
        ]);

        if ($val->fails()) {
            return response()->json(['error' => ['code' => '422', 'message' => 'Validation error', 'errors' => $val->errors()]], 422);
        }

        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'document_number' => $request->document_number,
            'password' => $request->password
        ];

        User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'document_number' => $data['document_number'],
            'password' => Hash::make($data['password']),
            'api_token' => Str::random(15)
        ]);

        return response()->json('', 204, );
    }

    public function token(Request $request)
    {
        $val = Validator::make($request->all(), [
            'phone' => 'required',
            'password' => 'required'
        ]);

        if($val->fails()) {
            return response()->json(['error' => ['code' => '422', 'message' => 'Validation error', 'errors' => $val->errors()]], 422);
        }

        $data = [
            'phone' => $request->phone,
            'password' =>  $request->password
        ];

        $user = User::where('phone', $data['phone'])->first();

        if (is_null($user) || !Hash::check($data['password'], $user->password)) {
            return response()->json(['error' => ['code' => '401', 'message' => 'Unauthorized', 'errors' => ['phone' => 'phone or password incorrect']]], 401);
        }

        $token = Str::random(20);
        $user->api_token = Hash::make($token);
        $user->save();


        return response()->json(['data' => ['token' => $token]], 200);

    }

    public function getUser(Request $request)
    {
        $data = [
            'first_name' => $request->user()->first_name,
            'last_name' => $request->user()->last_name,
            'phone' => $request->user()->phone,
            'document_number' => $request->user()->document_number
        ];

        return response()->json($data, 200);
    }
}
