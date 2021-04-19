<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\UserWallet;
use App\Models\CoreProduct;
use App\Models\CoreIncoming;
use App\Models\UserContract;
use Illuminate\Http\Request;
use App\Models\CoreMembership;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 ** Here I show the methods that I need to develop 
 *
 // 1. fetch_profile = use App\Http\Controllers\Users\Profile
 // 2. fetch_users
 // 3. identify_profile
 * 
 */

class Profile extends Controller
{

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
    public function fetch_users(int $is_admin, int $limit, int $offset, $searched = null) : object
    {
        try {

            if(is_null($searched) && empty($searched)) {
                $this->contract = UserContract::where('core_contracts.is_admin', $is_admin)
                                              ->join('users', 'core_contracts.user_id', '=', 'users.id')
                                              ->join('core_products', 'core_contracts.product_id', '=', 'core_products.id')
                                              ->select('core_contracts.meta', 'users.full_name', 'users.tell', 'core_products.features', 'core_products.type')
                                              ->offset($offset)
                                              ->limit($limit)
                                              ->get();
            } else {
                $this->contract = UserContract::where('is_admin', $is_admin)
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
                'error'     => $th->getMessage()
            ], 500);
        
        }
    }

    /**
     ** Fetch Lists By Features
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
                'debit_card'     => 'boolean|bail',
                'national_id'    => 'boolean|bail',
                'license_card'   => 'boolean|bail'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            $this->user_id = $user_id;
            $this->request = $request;

            if ($request->has('debit_card')) {
                $this->check_debit_card();
            }

            if ($request->has('national_id')) {
                $this->check_national_id();
            }

            if ($request->has('license_card')) {
                $this->check_license_card();
            }
            
            return response()->json([
                'stauts' => true,
            ], 200);

        } catch (\Throwable $th) {
                        
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        
        }
    }
    /**
     ** 
     * 
     * @param
     * @return void
     */
    protected function check_debit_card()
    {
        $this->updated['meta->financial->debit_card->validated'] = ($this->request->debit_card) ? true : false;
        User::where('id', $this->user_id)->update($updated);
    }
    /**
     ** 
     * 
     * @param
     * @return
     */
    protected function check_national_id()
    {
        $this->updated['meta->financial->national_id->validated'] = ($this->request->national_id) ? true : false;
        User::where('id', $this->user_id)->update($updated);
    }
    /**
     ** 
     * 
     * @param
     * @return
     */
    protected function check_license_card()
    {
        $this->updated['meta->financial->license_card->validated'] = ($this->request->license_card) ? true : false;
        User::where('id', $this->user_id)->update($updated);
    }
    
}
