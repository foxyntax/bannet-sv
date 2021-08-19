<?php
namespace App\Http\Controllers\User;

use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
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
     ** Check user's membership that is it expired or not? 
     * 
     * @param int $user_id
     */
    public function is_memebrship_expired(int $user_id) : object
    {
        try {
            $this->wallet = UserWallet::where('user_id', $user_id)->select('expired_at', 'membership_id')->first();

            if(! is_null($this->wallet->membership_id)) {
                if(Jalalian::forge($this->wallet->getRawOriginal('expired_at'))->getTimestamp() >= Jalalian::now()->getTimestamp()) {
                    return response()->json(['status' => true], 200);
                } else {
                    $this->wallet->membership_id = null;
                    $this->wallet->expired_at = null;
                    $this->wallet->save();
                    return response()->json(['status' => false], 200);
                    // Then you must delete all fetched data about old membership in client
                }
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
     * @param int $user_id
     * @param int $membership_id
     */
    public function buy_membership_from_wallet(int $user_id, int $membership_id) : object
    {
        try {
            return $this->set_membership($user_id, $membership_id, true, true);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
