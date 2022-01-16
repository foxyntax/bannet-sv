<?php
namespace App\Http\Controllers\User;

use Carbon\Carbon;
use App\Models\User;
use App\Models\CoreOption;
use App\Models\UserWallet;
use App\Models\UserContract;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use App\Traits\TransactionActions;
use App\Http\Controllers\Controller;
use App\Traits\Contract\ContractNotice;
use App\Http\Controllers\User\Membership;
use Illuminate\Support\Facades\Validator;

class Contract extends Controller
{
    use ContractNotice, TransactionActions;

    /**
     * @var int $contract
     */
    protected $contract;

    /**
     ** Create new contract
     // The request will be fired from a seller
     * 
     * @param Illuminate\Http\Request user_id
     * @param Illuminate\Http\Request product_id
     * @param Illuminate\Http\Request desc
     * @param Illuminate\Http\Request tyre_year
     * @param Illuminate\Http\Request count
     * @param Illuminate\Http\Request shipment_day
     * @return Illuminate\Http\Response
     */
    public function create(Request $request) : object
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id'       => 'bail|integer|required',
                'city'          => 'bail|string|required',
                'province'      => 'bail|string|required',
                'user_id'       => 'bail|integer|required',
                'product_id'    => 'bail|integer|required',
                'desc'          => 'bail|string|required',
                'tyre_year'     => 'bail|integer|required',
                'count'         => 'bail|integer|required',
                'cost'          => 'bail|integer|required',
                'shipment_day'  => 'bail|integer|required',
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            $has_membership = (new Membership)->is_membership_expired($request->user_id, 1);

            if($has_membership) {
                $exp_days = $this->get_contract_expiration();
                $new = $this->insert_new_contract($request, $exp_days->value);

                return response()->json([
                    'status'    => true,
                    // 'contract'  => $new
                ], 200);
            }

            return response()->json([
                'status'=> false,
                'desc'  => 'you haven\'t got any actived membership'
            ], 400);
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Update a new contract
     // The request will be fired from a seller
     * 
     * @param Illuminate\Http\Request desc
     * @param Illuminate\Http\Request tyre_year
     * @param Illuminate\Http\Request count
     * @param Illuminate\Http\Request shipment_day
     * @param Illuminate\Http\Request cost
     * @return Illuminate\Http\Response
     */
    public function update(int $contract_id, Request $request) : object
    {
        try {
            $validator = Validator::make($request->all(), [
                'desc'          => 'bail|string|required',
                'tyre_year'     => 'bail|string|required',
                'count'         => 'bail|string|required',
                'shipment_day'  => 'bail|string|required',
                'cost'          => 'bail|string|required',
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            $instance = UserContract::where([
                'id'        => $contract_id,
                'status'    => 0 
            ]);

            if($instance->count() > 0) {
                $contract = $instance->first();
                $contract->meta['desc']         = $request->desc;
                $contract->meta['tyre_year']    = $request->tyre_year;
                $contract->meta['count']        = $request->count;
                $contract->meta['cost']         = $request->cost;
                $contract->meta['shipment_day'] = $request->shipment_day;
                $contract->save();

                return response()->json([
                    'status'    => true
                ], 200);
            }

            return response()->json([
                'status'=> false
            ], 400);
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Generate Token for withdrawaling
     // The request will be fired from a customer
     * 
     * @param Illuminate\Http\Request contract_id
     * @return Illuminate\Http\Response
     */
    public function generate_withdrawal_req_token(Request $request) : object
    {
        try {
            $validator = Validator::make($request->all(), [
                'contract_id'=> 'integer|required',
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            $this->contract = UserContract::find($request->contract_id);
            if ($this->contract->status == 1) {
                // Generate token and change status for withdrawal
                $this->contract->status = 2;
                $this->contract->meta['token'] = rand(10000, 99999);
                $this->contract->meta['is_sent_token'] = 1;
                $this->contract->save();

                // Take a notice customer and seller by sending SMS
                $this->notice_generate_token();

                return response()->json([
                    'status' => true
                ], 200);
            }

            return response()->json([
                'status' => false
            ], 400);
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Cancel Contract
     // The request will be fired from a seller
     * 
     * @param Illuminate\Http\Request contract_id
     * @return Illuminate\Http\Response
     */
    public function cancel(Request $request) : object
    {
        try {
            $validator = Validator::make($request->all(), [
                'contract_id'=> 'integer|required',
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            $this->contract = UserContract::find($request->contract_id);
            $customer_wallet = UserWallet::find($this->contract->meta['customer_id']);

            // Withdrawal customer's cash
            $customer_wallet->pending_balance = $customer_wallet->pending_balance - $this->contract->meta['cost'];
            $customer_wallet->withdraw_balance = $customer_wallet->withdraw_balance + $this->contract->meta['cost'];
            $customer_wallet->save();

            // Change status of contract
            $this->contract->status = 4;
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
     ** Send shipment bill by seller to withdrawal after approving the receipt
     // The request will be fired from a seller
     // The withdrawal after
     * 
     * @param Illuminate\Http\Request contract_id
     * @return Illuminate\Http\Response
     */
    public function send_proven_shipment_receipt(Request $request) : object
    {
        try {

            $validator = Validator::make($request->all(), [
                'contract_id'   => 'integer|required',
                'bill'          => 'mimes:jpg,hevc,heif,png|required|bail'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            // Get contract detail
            $this->contract = UserContract::find($request->contract_id);
            
            // Check status of contract
            if ($this->contract->status == 1) {

                $this->contract->meta['proven_shipment']   = $request->file('bill')->store('users/' . $this->contract->user_id . '/shipment_docs');
                $this->contract->save();

                return response()->json([
                    'status' => true
                ], 200);
            }

            // You can't send your document until contract hasn't been started
            return response()->json([
                'status' => false
            ], 400);
            
            
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
                'token'      => 'string|required|bail'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            // get contract collections
            $this->contract = UserContract::find($request->contract_id);

            // Identify withdrawal token
            if($request->token == $this->contract->meta['token'] && $this->contract->status == 2) {

                // get user's and seller's wallet
                $seller_wallet = UserWallet::select('available_balance')->where('user_id', $this->contract->user_id)->first();
                $customer_wallet = UserWallet::find($request->user_id);

                // Update customer's wallet
                $customer_wallet->pending_balance = $customer_wallet->pending_balance - $this->contract->meta['cost'];
                $customer_wallet->save();

                // Update seller's wallet
                $seller_wallet->available_balance = $customer_wallet->available_balance + $this->contract->meta['cost'];
                $seller_wallet->save();

                $this->contract->status = 3;

                // Take a notice customer and seller by sending SMS
                $this->notice_withdrawal();

                return response()->json([
                    'status' => true,
                    'wallet' => $seller_wallet
                ], 200);
            }

            // You can't withdrawal until customer hasn't sent the token
            return response()->json([
                'status' => false
            ], 400);
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Review user [Seller or Customer] after ending contract
     * 
     * @param Illuminate\Http\Request user_id
     * @param Illuminate\Http\Request sender_id
     * @param Illuminate\Http\Request is_seller
     * @param Illuminate\Http\Request desc
     * @param Illuminate\Http\Request rate
     * @return Illuminate\Http\Response 
     */
    public function review_user(Request $request) : object
    {
        try {

            $validator = Validator::make($request->all(), [
                'to'            => 'integer|required|bail',
                'from'          => 'integer|required|bail',
                'contract_id'   => 'integer|required|bail',
                'is_seller'     => 'integer|required|bail|between:0,1',
                'rate'          => 'integer|required|bail|between:1,5',
                'desc'          => 'string|bail',
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            $sender   = User::where('id', $from)->select('full_name', 'meta')->first();
            $receiver = User::where('id', $to)->select('full_name', 'meta')->first();

            // Save for sender
            $sender->meta['scores'] = array_push($user->meta['scores'], [
                'to'            => $request->to,
                'from'          => $request->from,
                'contract_id'   => $request->contract_id,
                'sender'        => $sender->full_name,
                'receiver'      => $receiver->full_name,
                'is_seller'     => $request->is_seller,
                'desc'          => $request->has('desc') ? $request->desc : null,
                'rate'          => $request->rate
            ]);
            $sender->save();

            $receiver->meta['scores'] = array_push($user->meta['scores'], [
                'to'            => $request->to,
                'from'          => $request->from,
                'contract_id'   => $request->contract_id,
                'sender'        => $sender->full_name,
                'receiver'      => $receiver->full_name,
                'is_seller'     => $request->is_seller,
                'desc'          => $request->has('desc') ? $request->desc : null,
                'rate'          => $request->rate
            ]);
            $receiver->save();

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
     ** Buy ad by using Wallet
     * 
     * @param Illuminate\Http\Request user_id
     * @param Illuminate\Http\Request contract_id
     * @param Illuminate\Http\Request amount
     * @return Illuminate\Http\Response 
     */
    public function buy_ad_by_using_wallet (Request $request) : object
    {
        try {
            return $this->charge_pending_balance($request->user_id, $request->contract_id, $request->amount, true, true);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Get due option 
     * 
     * @return int
     */
    protected function get_contract_expiration()
    {
        try {
            return CoreOption::where('option', 'CONTRACT_EXPIRATION')
                            ->select('value')
                            ->first();
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }
    
    /**
     ** Insert contract detail to DB
     * 
     * @return int
     */
    protected function insert_new_contract(Request $request, $exp_days)
    {
        try {
            $new = new UserContract;
            $new->user_id       = $request->user_id;
            $new->product_id    = $request->product_id;
            $new->status        = 0;
            $new->meta = [
                'province'      => $request->province,
                'city'          => $request->city,
                'desc'          => $request->desc,
                'tyre_year'     => $request->tyre_year,
                'count'         => $request->count,
                'cost'          => $request->cost,
                'shipment_day'  => $request->shipment_day
            ];
            $new->expired_at    = Carbon::now()->addDays($exp_days);
            $new->save();

            return $new;
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }
    
}