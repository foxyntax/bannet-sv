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
 // 3. approve_profile
 // 4. disapprove_profile
 * 
 */

class Profile extends Controller
{
    /**
     * @var Illuminate\Http\Response $response
     */
    protected $response;

    /**
     * @var int $user
     */
    protected $user;

    /**
     * @var Illuminate\Support\Facades\Validator $validator
     */
    protected $validator;

    /**
     ** Fetch Lists By Features
     * 
     * @param int $user_id
     * @return Illuminate\Http\Request waller
     * @return Illuminate\Http\Request membership
     * @return Illuminate\Http\Request invoice
     * @return Illuminate\Http\Request contract
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
}
