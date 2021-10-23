<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class ApiToken extends Controller
{


    /**
     * Check Token and renew it if is valid
     * 
     * @param \Illuminate\Http\Request mode
     * @return \Illuminate\Http\Response api token
     */
    public function check_api_token(string $mode, Request $request) : object
    {
        try {

            $api_token = DB::table('personal_access_tokens')->where($mode, $request->input($mode))->first();
            $user = User::where('id', $api_token->tokenable_id)->first();

            // Delete Old API tokens
            $user->tokens()->delete();

            // Renew Name of API token
            $name = Hash::make($user->tell);

            // Renew API token
            $token = $user->createToken($name)->plainTextToken;

            return response()->json([
                'token' => $token, // you must set it in Authorization header
                'name'  => $name, // you must set it in cookie
                'user'  => $user
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
    }
}
