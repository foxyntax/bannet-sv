<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Traits\Kavenegar;
use Illuminate\Http\Request;
use App\Mail\PasswordResetToken;
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
        $this->token = rand(10000, 99999);
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
     * @param   string $by : username || tell || email || id
     * @param   string $info
     * @return  \Illuminate\Http\Response
    */
    public function sms_to_new_tell(string $by, string $info) : object {

        try {

            $this->user = User::where($by, $info)->firstOrFail();
            $this->user->otp = $this->token;
            $this->user->save();
            $this->send_otp_code($this->user->tell, $this->token, 'pass-reset');

            return response()->json([
                'success'  => true
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return response()->json([
                'error' => $th->getMessage()
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
            $this->user->otp = $this->token;
            $this->user->save();
            Mail::to($this->user->email)->send(new PasswordResetToken($this->token));

            return response()->json([
                'success'  => true
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 404);
        }

    }

    /**
     * Set new tell
     * 
     * @param Illuminate\Http\Request id
     * @param Illuminate\Http\Request tell
     * @param Illuminate\Http\Request token
     * @return \Illuminate\Http\Response boolean success
     */
    public function update_tell(Request $request) : object {
        try {
            if (User::where('tell', $request->tell)->count() === 0) {
                $user = User::findOrFail($request->id);
                if ($user->otp == $request->token) {
                    $user->fill([
                        'tell'  => $request->tell
                    ])->save();
                } else {
                    return response()->json([
                        'success'   => false
                    ], 401);
                }

                return response()->json([
                    'success'   => true
                ], 200);
            }

            return response()->json([
                'success'   => false
            ], 400);

        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return response()->json([
                'error' => $th->getMessage()
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

            $user = User::findOrFail($request->id);
            if ($user->otp === $request->token) {
                $user->fill([
                    'mail'              => $request->mail,
                    'verified_email'    => 1
                ])->save();
            }
        
            return response()->json([
                'success'   => true
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 404);
        }
    }
}
