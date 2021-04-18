<?php
namespace App\Http\Controllers\User;

use App\Models\User;
use App\Models\UserWallet;
use App\Models\UserContract;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Kavenegar\Exceptions\HttpException;
use Kavenegar\Exceptions\ApiException;

/**
 ** Here I show the methods that I need to develop 
 *
 // 1. approve_shipment
 // 2. disapprove_of_receipt
 // 3. expire_contracts
 // 4. fetch_contracts
 // 5. fetch_contract_detail
 * 
 */


class Contract extends Controller
{

    /**
     * @var int $contract
     */
    protected $contract;


    /**
     ** Withdrawal contract's cost from customer's pending balance
     * 
     * @param Illuminate\Http\Request user_id
     * @param Illuminate\Http\Request contract_id
     * @return Illuminate\Http\Response 
     */
    public function approve_shipment(Request $request) : object
    {
        try {

            $validator = Validator::make($request->all(), [
                'user_id'    => 'integer|required|bail',
                'contract_id'=> 'integer|required|bail'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            // get contract collections
            $this->contract = UserContract::find($request->contract_id);

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

            // Change status
            $this->contract->status = 2;
            $this->contract->save();

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
     ** Disapprove of shipment receipt
     * 
     * @param Illuminate\Http\Request user_id
     * @param Illuminate\Http\Request contract_id
     * @return Illuminate\Http\Response 
     */
    public function disapprove_of_receipt(Request $request) : object
    {
        try {

            $validator = Validator::make($request->all(), [
                'contract_id'=> 'integer|required|bail'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            $this->contract = UserContract::find($contract_id);

            // Delete proven shipment from local storage
            Storage::delete($this->contract->meta['proven_shipment']);

            // Remove it's address from db, too
            $this->contract->meta['proven_shipment'] = null;
            $this->contract->save();

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
     ** Review user [Seller or Customer] after ending contract
     * 
     * @param int $type
     * @param int $offset
     * @param int $limit
     * @param string $searched
     * @return Illuminate\Http\Response
     */
    public function fetch_contracts(int $status, int $limit, int $offset, $searched = null) : object
    {
        try {

            if (is_null($searched) && empty($searched)) {
                $this->contract = UserContract::where('core_contracts.status', $status)
                                              ->join('users', 'core_contracts.user_id', '=', 'users.id')
                                              ->join('core_products', 'core_contracts.product_id', '=', 'core_products.id')
                                              ->select('core_contracts.meta', 'users.full_name', 'users.tell', 'core_products.features', 'core_products.type')
                                              ->offset($offset)
                                              ->limit($limit)
                                              ->get();
            } else {
                $this->contract = UserContract::where('core_contracts.status', $status)
                                              ->where(function($query) use ($searched) {
                                                  $query->where('users.full_name', 'like', "%$searched%")
                                                        ->orWhere('users.full_name', 'like', "%$searched%");
                                              })
                                              ->select('core_contracts.meta', 'users.full_name', 'users.tell', 'core_products.features', 'core_products.type')
                                              ->offset($offset)
                                              ->limit($limit)
                                              ->get();
            }

            
            
            return response()->json([
                'contract'  => $this->contract,
                'count'     => (is_null($searched) && empty($searched)) ? UserContract::count() : count($this->contract)
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
    public function fetch_contract_detail(int $contract_id) : object
    {
        try {
            
            $this->contract = UserContract::find($contract_id);
            $this->contract->core_product;
            $this->contract->user;
            $this->contract['customer'] = User::find($this->contract->meta['customer_id']);

            return response()->json([
                'contract'  => $this->contract
            ], 200);
            
        } catch(ApiException $e){
            return response()->json($e->errorMessage(), 412);
        } catch(HttpException $e){
            return response()->json($e->errorMessage(), 412);
        }
    }

    /**
     ** Expire contracts
     * 
     * @return Illuminate\Http\Response 
     */
    public function expire_contracts() : object
    {
        try {

            // Fetch owners' expired contract
            $users = UserContract::where('status', 0)
                                 ->where('expired_at', '<', now())
                                 ->join('users', 'users.id', '=', 'user_contracts.user_id')
                                 ->select('full_name', 'tell', 'expired_at')
                                 ->get();

            // Expire all contracts which are timed out
            UserContract::where('status', 0)
                        ->where('expired_at', '<', now())
                        ->update(['stauts' => 4]);

            // Notice all owners' contract by sending SMS
            $this->notice_expired_contracts($user);

            // Remove it's address from db, too
            $this->contract->meta['proven_shipment'] = null;
            $this->contract->save();

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
    
    /** 
     ** Notice all owners' contract by sending SMS
     * 
     * @param collection $user
     * @return void/Object
    */
    protected function notice_expired_contracts($user) {
        try {
            // Send SMS to seller
            foreach ($users as $user) {
                Kavenegar::VerifyLookup($user->tell, $user->full_name, $user->expired_at, '', 'WithdrawalPendingBalanceForSeller', 'sms');
            }
            
        } catch(ApiException $e){
            return response()->json($e->errorMessage(), 412);
        } catch(HttpException $e){
            return response()->json($e->errorMessage(), 412);
        }
    }
}