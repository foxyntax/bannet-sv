<?php

namespace Modules\Auth\Http\Controllers;

use App\User;
use Modules\Auth\Traits\Kavenegar;
use Illuminate\Http\Request;
use Modules\Auth\Mail\PasswordResetToken;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ResetController extends Controller
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
    protected $token;


    public function __construct() {
        $this->token = rand(100000, 999999);
    }

    /**
     * Set new password
     * 
     * @param int with_old
     * @param Illuminate\Http\Request id
     * @param Illuminate\Http\Request password
     * @return \Illuminate\Http\Response boolean success
     */
    public function update_pass(int $with_old, Request $request) : object {
        try {

            if($with_old === 0) {
                User::findOrFail($request->id)->fill([
                    'password' => Hash::make($request->password)
                ])->save();
            } else {
                $user = User::findOrFail($request->id);
                if(Hash::check($request->old_pass, $user->password)) {
                    $user->fill([
                        'password'  => Hash::make($request->password)
                    ])->save();
                } else {
                    return response()->json([
                        'success'   => false,
                        'message'   => 'Old Password was not match with your input'
                    ], 200);
                }
            }
            

            return response()->json([
                'success'   => true
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 404);
        }
    }

    /** 
     * Send SMS incouded by Token to client 
     * and save it in client-side [application]
     * 
     * @param   string $by : username || tell || email
     * @param   string $info
     * @return  \Illuminate\Http\Response
    */
    public function sms_to_new_tell(string $by, string $info) : object {

        try {

            $this->user = User::where($by, $info)->firstOrFail();
            $this->send_otp_code($this->user->tell, $this->token, 'pass-reset');

            return response()->json([
                'token'     => $this->token
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 404);
        }

    }

    /**
     * Mail Token to client and save it in client-side [application]
     * 
     * @param   string $by : username || tell || email
     * @param   string $info
     * @return  \Illuminate\Http\Response
     */
    public function mail_token(string $by, string $info) : object {

        try {

            $this->user = User::where($by, $info)->firstOrFail();
            Mail::to($this->user->email)->send(new PasswordResetToken($this->token));

            return response()->json([
                'token'     => $this->token
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 404);
        }

    }

    /**
     * Set new tell
     * 
     * @param Illuminate\Http\Request id
     * @param Illuminate\Http\Request tell
     * @return \Illuminate\Http\Response boolean success
     */
    public function update_tell(Request $request) : object {
        try {

            User::findOrFail($request->id)->fill([
                'tell' => $request->tell
            ])->save();
        
            return response()->json([
                'success'   => true
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 404);
        }
    }

    /**
     * Set new mail
     * 
     * @param Illuminate\Http\Request id
     * @param Illuminate\Http\Request mail
     * @return \Illuminate\Http\Response boolean success
     */
    public function update_mail(Request $request) : object {
        try {

            User::findOrFail($request->id)->fill([
                'mail'              => $request->mail,
                'verified_email'    => 1
            ])->save();
        
            return response()->json([
                'success'   => true
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 404);
        }
    }
}
