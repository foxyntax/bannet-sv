<?php
namespace App\Http\Controllers\User;

use App\Models\User;
use App\Models\UserWallet;
use App\Models\UserContract;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Modules\Auth\Traits\Kavenegar;
use App\Http\Controllers\Controller;
use Kavenegar\Exceptions\ApiException;
use Kavenegar\Exceptions\HttpException;
use Illuminate\Support\Facades\Validator;

class Contract extends Controller
{

    /**
     * @var Illuminate\Http\Response $response
     */
    protected $response;

    /**
     * @var int $contract
     */
    protected $contract;

    /**
     ** Generate Token for withdrawal by customer
     * 
     * @param int $contract_id
     * @return Illuminate\Http\Response
     */
    public function generate_withdrawal_req_token(int $contract_id) : object
    {
        try {
            // Generate token
            $this->contract = find($contract_id);
            $this->contract->status = 2;
            $this->contract->meta['token'] = rand(100000000, 999999999);
            $this->contract->save();

            // Take a notice customer and seller by sending SMS
            $this->notice_generate_token();

            return response()->json([
                'status' => true
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Cancel Contract
     * 
     * @param int $contract_id
     * @return Illuminate\Http\Response
     */
    public function cancel_contract(int $contract_id) : object
    {
        try {
            // Withdrawal cusomter's cash
            $customer_wallet->pending_balance = $customer_wallet->pending_balance - $this->contract->meta['cost'];
            $customer_wallet->withdraw_balance = $customer_wallet->withdraw_balance + $this->contract->meta['cost'];
            $customer_wallet->save();

            // Change status of contract
            $this->contract = find($contract_id);
            $this->contract->status = 3;
            $this->contract->save();

            // Take a notice customer and seller by sending SMS
            $this->notice_cancel_contract();

            return response()->json([
                'status' => true
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Withdrawal contract's cost from customer's pending balance
     ** & Charge seller's available balance
     // The request will be fired from a seller
     * 
     * @param Illuminate\Http\Request user_id
     * @param Illuminate\Http\Request contract_id
     * @param Illuminate\Http\Request token
     * @return Illuminate\Http\Response 
     */
    public function withdrawal_pending_balance(Request $request) : object
    {
        try {

            $validator = Validator::make($request->all(), [
                'user_id'    => 'integer|required|bail',
                'contract_id'=> 'integer|required|bail',
                'token'      => 'integer|required|bail'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            // get contract collections
            $this->contract = UserContract::find($request->contract_id);

            // Identify withdrawal token
            if($request->token == $this->contract->meta['token']) {

                // get user's and seller's wallet
                $seller_wallet = UserWallet::select('available_balance')->where('user_id', $this->contract->user_id)->first();
                $customer_wallet = UserWallet::find($request->user_id);

                // Update customer's wallet
                $customer_wallet->pending_balance = $customer_wallet->pending_balance - $this->contract->meta['cost'];
                $customer_wallet->save();

                // Update seller's wallet
                $seller_wallet->available_balance = $customer_wallet->available_balance + $this->contract->meta['cost'];
                $seller_wallet->save();

                // Take a notice customer and seller by sending SMS
                $this->notice_withdrawal();
            }

            return response()->json([
                'status' => true,
                'wallet' => $seller_wallet
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Review user [Seller or Customer] after ending contract
    //  Not have been Finished yet
     * 
     * @param int $user_id
     * @param int $is_seller
     * @param int $desc
     * @param int $rate
     * @return Illuminate\Http\Response 
     */
    public function review_user(Request $request) : object
    {
        try {

            $validator = Validator::make($request->all(), [
                'user_id'    => 'integer|required|bail',
                'contract_id'=> 'integer|required|bail',
                'token'      => 'integer|required|bail'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            return response()->json([
                'status' => true,
                'wallet' => $customer_wallet
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /** 
     ** Take a notice customer sends a withdrawal token to seller by SMS
     * 
     * @return void/Object
    */
    protected function notice_generate_token() {
        try {
            // Send SMS to seller
            $seller = User::select('tell')->where('user_id', $this->contract->user_id)->first();
            Kavenegar::VerifyLookup($seller->tell, $this->contract->meta['token'], '', '', 'GetWithdrawalTokenForSeller', 'sms');
            
        } catch(ApiException $e){
            return response()->json($e->errorMessage(), 412);
        } catch(HttpException $e){
            return response()->json($e->errorMessage(), 412);
        }
    }

    /** 
     ** Take a notice that seller cancels contract by SMS
     * 
     * @return void/Object
    */
    protected function notice_cancel_contract() {
        try {
            // Send SMS to Customer
            $customer = User::select('tell')->where('user_id', $this->contract->user_id)->first();
            Kavenegar::VerifyLookup($customer->tell, $this->contract->meta['cost'], '', '', 'CancelContractForCustomer', 'sms');
            
        } catch(ApiException $e){
            return response()->json($e->errorMessage(), 412);
        } catch(HttpException $e){
            return response()->json($e->errorMessage(), 412);
        }
    }

    /** 
     ** Take a notice that seller gets his money by SMS
     * 
     * @return void/Object
    */
    protected function notice_withdrawal() {
        try {
            // Send SMS to seller
            $seller = User::select('tell')->where('user_id', $this->contract->user_id)->first();
            Kavenegar::VerifyLookup($seller->tell, $this->contract->meta['cost'], '', '', 'WithdrawalPendingBalanceForSeller', 'sms');

            // Send SMS to Customer
            $customer = User::select('tell')->where('user_id', $this->contract->user_id)->first();
            Kavenegar::VerifyLookup($customer->tell, $this->contract->meta['cost'], '', '', 'WithdrawalPendingBalanceForCustomer', 'sms');
            
        } catch(ApiException $e){
            return response()->json($e->errorMessage(), 412);
        } catch(HttpException $e){
            return response()->json($e->errorMessage(), 412);
        }
    }
    
}