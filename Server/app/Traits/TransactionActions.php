<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\User;
use App\Models\UserWallet;
use Kavenegar\KavenegarApi;
use App\Models\UserContract;
use App\Models\CoreMembership;
use Modules\Auth\Traits\Kavenegar;
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
     * @param bool $return
     * @param bool $use_avl_balance
     * @return void/boolean
     */
    protected function set_membership(int $user_id, int $membership_id, bool $return = false, bool $use_avl_balance = false) {
        try {
            // Get wallet & membership
            $this->wallet = UserWallet::where('user_id', $user_id)->first();
            $this->membership = CoreMembership::find($membership_id);

            // Use available balance if it's needed
            if($use_avl_balance) {
                // Diff cost from available balance
                if($this->wallet->available_balance >= $this->membership->meta['cost']) {
                    $this->wallet->available_balance = $this->wallet->available_balance - $this->membership->meta['cost']; 
                } else {
                    // return any way, because your req is failed
                    return response()->json(['status' => false], 400);
                }
            }

            // Update memebership id
            $this->wallet->membership_id = $membership_id;

            // Update expiration for wallet
            $this->wallet->expired_at = (!is_null($this->membership->days))
                ? Carbon::now()->addDays($this->membership->days)
                : null;
            
            $this->wallet->save();

            if($return) { return response()->json(['status' => true, 'exp' => $this->wallet->expired_at], 200); }
            
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
    protected function charge_pending_balance(int $user_id, int $contract_id, int $amount, bool $return = false, bool $use_avl_balance = false) {
        try {
            // Let see status of ad [contract], first
            $this->contract = UserContract::find($contract_id);
            if ($this->contract->status !== 0 && (isset($this->contract->meta['customer_id']) && $this->contract->meta['customer_id'] !== 0)) {
                // You can't buy this ad
                return response()->json([
                    'status' => false,
                    'desc'  => 'Ad has been already bought'
                ], 400);
            }

            // Get wallet
            $this->wallet = UserWallet::where('user_id', $user_id)->first();

            // Use available balance if it's needed
            if($use_avl_balance) {
                // Diff amount from available balance
                if($this->wallet->available_balance >= $amount) {
                    $this->wallet->available_balance = $this->wallet->available_balance - $amount; 
                } else {
                    // return any way, because your req is failed
                    return response()->json([
                        'status'=> false,
                        'desc'  => 'You don\'t have enough balance'
                    ], 400);
                }
            }

            // Charge Pending Balance
            $this->wallet->pending_balance = $this->wallet->pending_balance + $amount;
            $this->wallet->save();

            // Change status and customer_id in contract
            $this->contract->status = 1;
            $this->contract->meta['customer_id'] = $user_id;
            $this->contract->save();

            // Take a notice customer and seller by sending SMS
            // $this->notice_users();

            if($return) { return response()->json(['status' => true], 200); }
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /** 
     ** Take a notice customer and seller by sending SMS
     * 
     * @return void/Object
    */
    protected function notice_users() {
        try {
            // Send SMS to seller
            $seller = User::select('tell')->where('id', $this->contract->user_id)->first();
            KavenegarApi::VerifyLookup($seller->tell, '', '', '', 'StartingContractForSeller', 'sms');

            // Send SMS to Customer
            $customer = User::select('tell')->where('id', $this->contract->user_id)->first();
            KavenegarApi::VerifyLookup($customer->tell, '', '', '', 'StartingContractForCustomer', 'sms');
            
        } catch(ApiException $e){
            return response()->json($e->errorMessage(), 412);
        } catch(HttpException $e){
            return response()->json($e->errorMessage(), 412);
        }
    }
}