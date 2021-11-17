<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Traits\Kavenegar;
use App\Models\UserWallet;
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
     * Token instance for validate user by OTP
     * 
     * @param int
     */
    protected $otp_token;

    /**
     * Saved token or False
     * 
     * @param int or boolean
     */
    protected $token;

    /**
     * name of API token
     * 
     * @param Illuminate\Support\Facades\Hash
     */
    protected $name;

    /**
     ** Define your Token Length
     */
    public function __construct() {
        $this->otp_token = rand(10000, 99999);
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
            $status = (! $this->token) ? 401 : 200;

            return response()->json([
                'token' => $this->token, // you must set it in Authorization header
                'name'  => $this->name, // you must set it in cookie
                'user'  => $this->user
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
     ** Check User existance in DB and Send User data
     * 
     * @param \Illuminate\Http\Request credentials 
     * @param \Illuminate\Http\Request tell
     * @return \Illuminate\Http\Response
     */
    public function check_user(Request $request) : object
    {
        try {
            // Get User and his/het wallet if Exists
            $this->user = User::where($request->credentials)->select('id', 'is_disabled')->first();

            $does_exist = (! is_null($this->user));
            if ($does_exist) {
                $is_wallet_registered = (UserWallet::where('user_id', $this->user->id)->count() !== 0);
            } else {
                $is_wallet_registered = false;
            }
            
            // Oh, User has been found but is disabled
            if($does_exist && $this->user->is_disabled === 1) {
                return response()->json([
                    'error'  => 'این کاربر بنا به دلایلی، غیرفعال شده است'  
                ], 400);
            }

            // User is not disabled, so we need to send token
            $this->send_otp($request->tell, $does_exist, $is_wallet_registered);

            return response()->json([
                'success'   => true,
                'status'    => ($does_exist && $is_wallet_registered) ? 'login' : 'register'
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Get Token from client with giving tell by OTP
     * 
     * @param \Illuminate\Http\Request token
     * @param \Illuminate\Http\Request credential_key
     * @param \Illuminate\Http\Request credential_val [or some col which you want to check]
     * @return \Illuminate\Http\Response api token
     */
    public function verify_user(Request $request) : object 
    {
        try {

            $this->user = User::where($request->credential_key, $request->credential_val)
                              ->where('otp', $request->token)
                              ->firstOrFail();

            // Create Name of API token
            $this->name = Hash::make($this->user->tell);

            // Renew API token
            $this->token = $this->user->createToken($this->name)->plainTextToken;

            return response()->json([
                'token' => $this->token, // you must set it in authorization header
                'name'  => $this->name, // you must set it in cookie
                'user'  => $this->user
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
     ** Send Token to client by giving Tell by OTP
     // NOTE: it's not part of login or register process,
     you can use it whenever you wanna send a token to a tell number seperately
     * 
     * @param \Illuminate\Http\Request tell
     * @return \Illuminate\Http\Response
     */
    public function send_otp_token(Request $request) : object 
    {
        try {

            $this->user = User::where('tell', $request->tell)->firstOrFail();
            $this->send_otp($request->tell);
            
            return response()->json([
                'success' => true
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
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
     ** Handle Auth Attempt
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
                $this->name = Hash::make($this->user->tell);

                // Create new API token
                $this->token = $this->user->createToken($this->name)->plainTextToken;
                return;
            }
            
            // Invalid User
            $this->token = false;
            $this->name = false;
            
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

    /**
     ** Send OTP to user who has been found 
     * 
     * @param int tell
     * @return void
     */
    protected function send_otp(string $tell , bool $does_exist, bool $is_wallet_registered)
    {
        if($does_exist) {
            $this->user->otp = (string) $this->otp_token;
            $this->user->save();
        }

        if (!$does_exist && !$is_wallet_registered) {
            User::create([
                'tell'  => $tell,
                'meta'  => User::$meta,
                'otp'   => (string) $this->otp_token
            ]);
        }

        $this->send_otp_code($tell, $this->otp_token, 'otp');
    }
}
