<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Traits\Kavenegar;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{

    /**
     ** The User who has been registered
     * 
     * @param App\Models\User
     */
    protected $user;

    /**
     ** Register User with Pass
     * 
     * @param \Illuminate\Http\Request newPassword
     * @param \Illuminate\Http\Request [optional]
     * @return \Illuminate\Http\Response 
     */
    public function register_by_pass(Request $request) : object
    {
        try {
            $request->password = Hash::make($request->newPassword);
            $this->user = User::create($request->all());

            // now, you can use $user for your new plans [please don't remove this note]
            return $this->register_actions();
        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Register User with Pass
     * 
     * @param \Illuminate\Http\Request [optional]
     * @return \Illuminate\Http\Response 
     */
    public function register_without_pass(Request $request) : object
    {
        try {
            $this->user = User::create($request->all());

            // now, you can use $user for your new plans [please don't remove this note]
            return $this->register_actions();
        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Actions which you need them
     * 
     * @param Illuminate\Http\Request $request
     * @return object
     */
    protected function register_actions(object $request = null) : object
    {
        try {

            /** 
             ** Your Plans ...
            */

            // Generate Name of API token
            $name = Hash::make($this->user->tell);

            // Create API token
            $token = $this->user->createToken($name)->plainTextToken;

            // Success Template
            return response()->json([
                'token' => $token, // you must set it in authorization header
                'name'  => $name, // you must set it in cookie
                'user'  => $this->user
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
    }
}
