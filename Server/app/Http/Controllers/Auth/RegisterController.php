<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Traits\Kavenegar;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use App\Models\CoreMembership;
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
     // this function is not working well for this app
     * 
     * @param \Illuminate\Http\Request token
     * @param \Illuminate\Http\Request tell
     * @return \Illuminate\Http\Response 
     */
    public function register_without_pass(Request $request) : object
    {
        try {

            $this->user = User::where([
                'tell'  => $request->tell,
                'otp'   => $request->token
            ])->first();

            if(!$this->user) {
                return response()->json([
                    'status'=> false,
                    'error' => 'کاربری با این شماره تلفن یافت نشد'
                ], 400);
            }

            // now, you can use $user for your new plans [please don't remove this note]
            return $this->register_actions();

        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Actions which you need them
     // this function is not working well for this app
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
            // Assign a free membership plan to the user
            $free_membership = CoreMembership::where('meta->cost', '0')->select('id')->first();

            // Create wallet for new user
            UserWallet::create([
                'user_id'       => $this->user->id,
                'membership_id' => $free_membership->id,
            ]);


            /**
             ** General Actions [please don't remove these codes] 
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
