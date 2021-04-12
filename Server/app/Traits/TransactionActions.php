<?php

namespace App\Traits;

use App\Models\UserWallet;
use App\Models\CoreMembership;
use Kavenegar\KavenegarApi;
use Kavenegar\Exceptions\ApiException;
use Kavenegar\Exceptions\HttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait TransactionActions {

    /**
     * @var int $wallet
     */
    protected $wallet;

    /**
     * @var int $contract
     */
    protected $contract;

    /**
     * @var int $membership
     */
    protected $membership;

    /**
     ** Set new membership for user
     NOTE: You must check that has user got a membership before??? This request considers, user hasn't any membership now
     * @param int $user_id
     * @param int $membership_id
     * @return void/boolean
     */
    protected function set_membership(int $user_id, int $membership_id, bool $return = false, bool $use_avl_balance = false) {
        try {
            // Get wallet
            $this->wallet = UserWallet::where('user_id', $user_id)->first();
            $this->wallet->membership_id = $membership_id;

            // Update expiration for wallet
            $this->wallet->expired_at = (!is_null($this->membership->days))
                ? now() + ($this->membership->days * 3600)
                : null;

            // Use available balance if it's needed
            if($use_avl_balance) {
                // Diff cost from available balance
                $this->membership = CoreMembership::find($membership_id);
                $this->wallet->available_balance = $this->wallet->available_balance - $this->membership->meta['cost'];
            }
            
            $this->wallet->save();

            if($return) { return response()->json(['status' => true], 200); }
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Charge use's pending balance
     *
     * @param int $user_id
     * @param int $amount
     * @return void/boolean
     */
    protected function charge_pending_balance(int $user_id, int $contract_id, int $amount) {
        try {
            // Get wallet
            $this->wallet = UserWallet::where('user_id', $user_id)->first();
            $this->wallet->pending_balance = $this->wallet->pending_balance + $amount;
            $this->wallet->save();

            // Change status and customer_id in contract
            $this->contract = UserContract::find($contract_id);
            $this->contract->status = 1;
            $this->contract->meta['customer_id'] = $user_id;
            $this->contract->save();
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

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