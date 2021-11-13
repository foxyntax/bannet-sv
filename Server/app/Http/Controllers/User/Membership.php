<?php
namespace App\Http\Controllers\User;

use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use App\Models\CoreMembership;
use App\Traits\TransactionActions;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class Membership extends Controller
{
    use TransactionActions;

    /**
     * @var Illuminate\Http\Response $response
     */
    protected $response;

    /**
     * @var int $wallet
     */
    protected $wallet;

    /**
     * @var int $membership
     */
    protected $membership;

    /**
     ** Fetch Available Memberships
     * 
     * @return Illuminate\Http\Response
     */
    public function fetch_available_memberships() : object
    {
        try {
            $this->response = CoreMembership::where('status', 1)->get();

            return response()->json([
                'memberships' => $this->response
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Check user's membership that is it expired or not? 
     // it's a helper method, so you can fetch user wallet with membership
     * 
     * @param int $user_id
     * @return object
     */
    public function is_membership_expired(int $user_id, int $return = 0)
    {
        try {
            $this->wallet = UserWallet::where('user_id', $user_id)->first();
            
            // if function is the helper method.
            $is_helper = ($return == 1);

            if(! is_null($this->wallet->membership_id)) {
                if(Jalalian::forge($this->wallet->getRawOriginal('expired_at'))->getTimestamp() >= Jalalian::now()->getTimestamp()) {
                    return (! $is_helper)
                        ? response()->json(['status' => true], 200)
                        : [
                            'status' => true,
                            'wallet' => $this->wallet
                        ];
                } else {
                    $this->wallet->membership_id = null;
                    $this->wallet->expired_at = null;
                    $this->wallet->save();
                    return (! $is_helper)
                        ? response()->json(['status' => false], 200)
                        : [
                            'status' => true,
                            'wallet' => $this->wallet
                        ];

                    // Then you must delete all fetched data about old membership in client,
                    // however I'll do it in fetch_user_data if you use it
                }
            }
            
            if($is_helper) {
                return [
                    'status' => true,
                    'wallet' => $this->wallet
                ];
            }
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
        
    }
    
    /**
     ** Buy membership
     * 
     * @param Illuminate\Http\Request user_id
     * @param Illuminate\Http\Request contract_id
     */
    public function buy_membership_by_using_wallet(Request $request) : object
    {
        try {
            return $this->set_membership($request->user_id, $request->membership_id, true, true);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
