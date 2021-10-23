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

/**
 ** Here I show the methods that I need to develop 
 *
 // 1. fetch_profile = use App\Http\Controllers\Users\Profile
 // 2. fetch
 // 3. identify_profile
 * 
 */

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
    public function fetch(int $is_admin, int $offset, int $limit, $searched = null) : object
    {
        try {
            if(is_null($searched) && empty($searched)) {
                $this->user = User::where('is_admin', $is_admin);
                // $this->user->user_contracts;
            } else {
                $this->user = User::where('users.is_admin', $is_admin)
                                  ->where(function($query) use ($searched) {
                                      $query->where('users.full_name', 'like', "%$searched%")
                                          ->orWhere('users.tell', 'like', "%$searched%");
                                  });
            }

            $count = $this->user->count();
            $this->user = $this->user->offset($offset)
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
