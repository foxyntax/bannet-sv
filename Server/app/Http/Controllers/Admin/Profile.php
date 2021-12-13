<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\UserWallet;
use App\Models\CoreProduct;
use App\Models\CoreIncoming;
use App\Models\UserContract;
use Illuminate\Http\Request;
use App\Models\CoreMembership;
use App\Traits\Profile\Wallet;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class Profile extends Controller
{
    use Wallet;
    
    /**
     * @var int $user
     */
    protected $user;

    /**
     * @var int $user_id
     */
    protected $user_id;

    /**
     * @var array
     */
    protected $request;

    /**
     * @var array
     */
    protected $updated;

    /**
     ** Fetch Lists By Features
     * 
     * @param int $is_admin
     * @param int $limit
     * @param int $offset
     * @param string $searched
     * @return Illuminate\Http\Response
     */
    public function fetch(string $mode, int $offset, int $limit, $searched = null) : object
    {
        try {

            switch ($mode) {
                case 'withdrawal':
                    $this->user = User::where('users.is_admin', 0)
                                      ->where('user_wallet.withdraw_balance', '>', 0);
                break;

                case 'admin':
                    $this->user = User::where('users.is_admin', 1);
                break;

                case 'user':
                    $this->user = User::where('users.is_admin', 0);
                break;

                case 'verify':
                    $this->user = User::where('users.is_admin', 0)
                                      ->where(function($query) {
                                            $query->where(function($query) {
                                                $query->where('meta->financial->debit_card->validated', 0)
                                                      ->where('meta->financial->debit_card->value', null)
                                                      ->where('meta->financial->debit_card->img', null);
                                            })
                                            ->orWhere(function($query) {
                                                $query->where('meta->financial->national_id->validated', 0)
                                                      ->where('meta->financial->national_id->value', null)
                                                      ->where('meta->financial->national_id->img', null);
                                            })
                                            ->orWhere(function($query) {
                                                $query->where('meta->financial->license_card->validated', 0)
                                                      ->where('meta->financial->license_card->value', null)
                                                      ->where('meta->financial->license_card->img', null);
                                            });
                                      });
                break;
                
                default:
                    return response()->json([
                        'error' => 'Invalid Mode',
                    ], 500);  
            }

            if(!is_null($searched) && !empty($searched)) {
                $this->user->where(function($query) use ($searched) {
                                $query->where('users.full_name', 'like', "%$searched%")
                                        ->orWhere('users.tell', 'like', "%$searched%");
                            });
            }

            $count = $this->user->count();
            $this->user = $this->user->leftJoin('user_wallet', 'user_wallet.user_id', '=', 'users.id')
                                     ->select('users.*', 'user_wallet.withdraw_balance')
                                     ->offset($offset)
                                     ->limit($limit)
                                     ->get();
            
            return response()->json([
                'users' => $this->user,
                'count' => $count
            ], 200);

        } catch (\Throwable $th) {
                        
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        
        }
    }

    /**
     ** Identity user's profile
     * 
     * @param int $user_id
     * @return Illuminate\Http\Request debit_card
     * @return Illuminate\Http\Request national_id
     * @return Illuminate\Http\Request license_card
     * @return Illuminate\Http\Response
     */
    public function identify_profile(int $user_id, Request $request) : object
    {
        try {

            $validator = Validator::make($request->all(), [
                'debit_card'     => 'integer|bail',
                'national_id'    => 'integer|bail',
                'license_card'   => 'integer|bail'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            $this->user_id = $user_id;
            $this->request = $request;


            if ($request->has('debit_card')) {
                $this->check_docs('debit_card', $this->request->debit_card);
            }

            if ($request->has('national_id')) {
                $this->check_docs('national_id', $this->request->national_id);
            }

            if ($request->has('license_card')) {
                $this->check_docs('license_card', $this->request->license_card);
            }
            
            return response()->json([
                'stauts' => true
            ], 200);

        } catch (\Throwable $th) {
                        
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        
        }
    }

    /**
     ** Update document validation
     * 
     * @return void
     */
    protected function check_docs(string $doc_name, $req)
    {
        $this->updated['meta->financial->'.$doc_name.'->validated'] = ($req == 1);

        if ($req == 0) {
            $user = User::where('id', $this->user_id)->select('meta')->first();
            Storage::delete($user->meta['financial'][$doc_name]['img']);
            $this->updated['meta->financial->'.$doc_name.'->img'] = null;
        }

        $this->update_financial_meta();
    }

    /**
     ** Update user meta [financial section]
     *
     * @return void 
     */
    private function update_financial_meta()
    {
        User::where('id', $this->user_id)->update($this->updated);
    }
}
