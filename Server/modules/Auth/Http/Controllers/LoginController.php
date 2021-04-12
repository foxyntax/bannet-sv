<?php

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use Modules\Auth\Traits\Kavenegar;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    use Kavenegar;

    /**
     * User instance from database
     * 
     * @param App\User
     */
    protected $user;

    /**
     * Token instance for validate user
     * 
     * @param int
     */
    protected $otp_token;

    /**
     * Saved token or False
     * 
     * @param int or boolean
     */
    protected $auth;

    /**
     * name of API token
     * 
     * @param Illuminate\Support\Facades\Hash
     */
    protected $token_name;

    /**
     ** Define your Token Length
     */
    public function __construct() {
        $this->otp_token = rand(1000, 9999);
    }

    // =====================
    /**  
     ** ** ** ** ** Login By Basic ** ** ** ** ** 
     ** Login will be done with some data and password
     **/
    // =====================

    /**
     * Get Data which you want to attempt for credentials and set new token
     * by sanctum laravel package
     * 
     * @param \Illuminate\Http\Request credential_key
     * @param \Illuminate\Http\Request credential_val [or some col which you want to check]
     * @param \Illuminate\Http\Request password [without hash]
     * @return \Illuminate\Http\Response api token
     */
    public function login_basic(Request $request) : object
    {
        try {

            $this->authenticate($request);
            $status = (! $this->auth) ? 401 : 200;

            return response()->json([
                'auth'              => $this->auth,
                'token_name'        => $this->token_name,
                'user'              => $this->user,
            ], $status);
            
        } catch (\Throwable $th) {
            
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException $th) {

            return response()->json([
                'error'     => $th->getMessage()
            ], 401);

        }
    }

    // =====================
    /**
     ** ** ** ** ** Login By OTP ** ** ** ** ** 
     ** Loing will be done with some data and password
     **/
    // =====================

    /**
     * Check User existance in DB and Send Token any way
     * 
     * @param \Illuminate\Http\Request credentials 
     * @param \Illuminate\Http\Request tell
     * @return \Illuminate\Http\Response
     */
    public function check_user(Request $request) : object
    {
        try {
            // Get User if Exists
            $this->user = User::where($request->credentials)->first();

            // Oh, User has been founded but is disabled
            if(! is_null($this->user)) {
                if($this->user->is_disable === 1) {
                    return response()->json([
                        'token' => null,
                        'user'  => $this->user
                    ], 200);
                }
            }

            $this->send_otp_code($request->tell, $this->otp_token, 'otp');
            
            return response()->json([
                'token' => $this->otp_token,
                'user'  => $this->user
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Send Token to client by giving Tell by OTP
     * 
     * @param \Illuminate\Http\Request tell
     * @return \Illuminate\Http\Response api token
     */
    public function send_otp_token(Request $request) : object 
    {
        try {

            $this->send_otp_code($request->tell, $this->otp_token, 'otp');

            return response()->json([
                'token' => $this->otp_token,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
        
    }

    /**
     * Send Token to client with giving tell by OTP
     * 
     * @param \Illuminate\Http\Request credential_key
     * @param \Illuminate\Http\Request credential_val [or some col which you want to check]
     * @return \Illuminate\Http\Response api token
     */
    public function submit_otp(Request $request) : object 
    {
        try {

            $this->user = User::where($request->credential_key, $request->credential_val)->firstOrFail();

            // Create Name of API token
            $this->token_name = Hash::make($this->user->tell);

            // Renew API token
            $this->auth = $this->user->createToken($this->token_name)->plainTextToken;

            return response()->json([
                'auth'              => $this->auth,
                'token_name'        => $this->token_name,
                'user'              => $this->user
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 401);
        }
        
    }

    /**
     *
     *=============================================================================
     *=============================================================================
     *=============================================================================
     ** ========================== Protected Functions ========================= ** 
     *=============================================================================
     *=============================================================================
     *=============================================================================
     * 
     **/

    /**
     * Handle Auth Attempt
     * 
     * @param mixed $request
     * @return void or
     * @return  \Illuminate\Http\Response error message
     */
    protected function authenticate(object $request)
    {
        try {
            $credentials = [
                $request->credential_key  => $request->credential_val,
                'password'                => $request->password
            ];
            
            if (Auth::attempt($credentials)) {
                // Authentication passed... Let's create API Bearer Token
                $this->user = User::where($request->credential_key, $request->credential_val)->firstOrFail();

                // Let's clean old tokens if exist
                $this->user->tokens()->delete();

                // Renew Name of API token
                $this->token_name = Hash::make($this->user->tell);

                // Create new API token
                $this->auth = $this->user->createToken($this->token_name)->plainTextToken;
                return;
            }
            
            // Invalid User
            $this->auth = false;
            $this->token_name = false;
            
        } catch (\Throwable $th) {      
            return response()->json([
                'error' => $th->getMessage()
             ], 500);
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 401);
        }
    }
}
