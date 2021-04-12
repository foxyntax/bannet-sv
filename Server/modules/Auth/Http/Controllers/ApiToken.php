<?php

namespace Modules\Auth\Http\Controllers;

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
     * @param \Illuminate\Http\Request name
     * @return \Illuminate\Http\Response api token
     */
    public function check_api_token(Request $request) : object
    {
        try {

            $api_token = DB::table('personal_access_tokens')->where('name', $request->name)->first();
            $user = User::where('id', $api_token->tokenable_id)->first();

            // Delete Old API tokens
            $user->tokens()->delete();

            // Renew Name of API token
            $token_name = Hash::make($user->tell);

            // Renew API token
            $token = $user->createToken($token_name)->plainTextToken;

            return response()->json([
                'token'         => $token,
                'token_name'    => $token_name,
                'user'          => $user
            ], 200);
            
            
        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
    }
}
