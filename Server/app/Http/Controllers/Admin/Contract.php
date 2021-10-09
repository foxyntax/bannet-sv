<?php
namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\UserWallet;
use App\Models\UserContract;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Contract\ContractNotice;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;;

/**
 ** Here I show the methods that I need to develop 
 *
 // 1. approve_shipment
 // 2. disapprove_of_receipt
 // 3. expire
 // 4. fetch
 // 5. fetch_detail
 * 
 */


class Contract extends Controller
{
    use ContractNotice;

    /**
     * @var int $contract
     */
    protected $contract;

    /**
     ** Withdrawal contract's cost from customer's pending balance for seller
     * 
     * @param Illuminate\Http\Request customer_id
     * @param Illuminate\Http\Request contract_id
     * @return Illuminate\Http\Response 
     */
    public function approve_shipment(Request $request) : object
    {
        try {

            $validator = Validator::make($request->all(), [
                'customer_id'   => 'integer|required|bail',
                'contract_id'   => 'integer|required|bail'
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
            $customer_wallet = UserWallet::select('available_balance', 'pending_balance')->where('user_id', $request->customer_id)->first();

            // Update customer's wallet
            $customer_wallet->pending_balance = $customer_wallet->pending_balance - $this->contract->meta['cost'];
            $customer_wallet->save();

            // Update seller's wallet
            $seller_wallet->available_balance = $customer_wallet->available_balance + $this->contract->meta['cost'];
            $seller_wallet->save();

            // Take a notice customer and seller by sending SMS
            // // $this->notice_withdrawal();

            // Change status
            $this->contract->status = 2;
            $this->contract->save();

            return response()->json([
                'status' => $this->contract->meta['cost']
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

            $this->contract = UserContract::find($request->contract_id);

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
     ** Fetch all contact with pagination
     * 
     * @param int $status
     * @param int $offset
     * @param int $limit
     * @param string $searched
     * @return Illuminate\Http\Response
     */
    public function fetch(int $status, int $offset, int $limit, $searched = null) : object
    {
        try {

            if (is_null($searched) && empty($searched)) {
                $this->contract = UserContract::where('user_contracts.status', $status)
                                              ->select('user_contracts.id', 'user_contracts.meta', 'users.full_name', 'users.tell', 'core_products.features', 'core_products.type')
                                              ->join('users', 'user_contracts.user_id', '=', 'users.id')
                                              ->join('core_products', 'user_contracts.product_id', '=', 'core_products.id');
            } else {
                $this->contract = UserContract::where('user_contracts.status', $status)
                                              ->where(function($query) use ($searched) {
                                                  $query->where('users.full_name', 'like', "%$searched%")
                                                        ->orWhere('users.tell', 'like', "%$searched%");
                                              })
                                              ->select('user_contracts.id', 'user_contracts.meta', 'users.full_name', 'users.tell', 'core_products.features', 'core_products.type')
                                              ->join('users', 'user_contracts.user_id', '=', 'users.id')
                                              ->join('core_products', 'user_contracts.product_id', '=', 'core_products.id');
                                              
                                              
            }
            
            return response()->json([
                'count'     => $this->contract->count(),
                'contract'  => $this->contract->offset($offset)->limit($limit)->get()
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /** 
     ** Fetch contract detail
     * 
     * @param in $contract_id
     * @return void/Object
    */
    public function fetch_detail(int $contract_id) : object
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
     ** Cancell all expired contracts
     * 
     * @return Illuminate\Http\Response 
     */
    public function expire() : object
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
                        ->update(['status' => 4]);

            // Notice all owners' contract by sending SMS
            // $this->notice_expired_contracts($user);

            return response()->json([
                'status' => true
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }
}