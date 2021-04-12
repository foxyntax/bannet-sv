<?php

namespace Modules\Auth\Traits;

use App\Models\User;
use Kavenegar\KavenegarApi;
use Kavenegar\Exceptions\ApiException;
use Kavenegar\Exceptions\HttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait Kavenegar {
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
     * Send Token To Tell and Send Token To Client Side
     * 
     * @return void
    */
    protected function send_otp_code($receptor, $token, $template, $format = 'sms')
    {
        try{
            $api = new KavenegarApi(env('OTP_API_KEY'));
            $api->VerifyLookup($receptor, $token, '', '', $template, $format);
        }
        catch(ApiException $e){
            return response()->json($e->errorMessage(), 412);
        }
        catch(HttpException $e){
            return response()->json($e->errorMessage(), 412);
        }
    }

    
}