<?php

namespace App\Traits\Profile;

use App\Models\User;
use App\Models\UserWallet;
use App\Models\CoreProduct;
use App\Models\CoreMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

trait FetchProfile {

     /**
     ** Fetch Lists By Features
     * 
     * @param int $user_id
     * @return Illuminate\Http\Request wallet
     * @return Illuminate\Http\Request membership
     * @return Illuminate\Http\Request receipt
     * @return Illuminate\Http\Request contract
     * @return Illuminate\Http\Response
     */
    public function fetch_user_data(int $user_id = null, Request $request) : object
    {
        try {
            

            //  Get basic user's data
            $this->response['profile'] = User::find($user_id);

            // Get User's wallet
            if($request->has('wallet')) {
                $this->response['wallet'] = UserWallet::where('user_id', $user_id)->first();

                // Get User's membership data
                if($request->has('membership')) {    
                    $this->response['wallet']['membership'] = $this->response['wallet']->core_membership;
                }
            }

            // Get user's receipt
            if($request->has('receipt')) {
                // $this->response['receipt'] = CoreIncoming::where('user_id', $user_id)->get();
                $this->response['receipt'] = $this->response['profile']->core_transaction;
            }

            // Get user's contracts
            if($request->has('contract')) {
                // $this->response['contract'] = UserContract::where('user_id', $user_id)->get();
                $this->response['contract'] = $this->response['profile']->user_contracts;
            }

            // Get user's favorites products
            if($request->has('favorites')) {
                $this->fetch_favorites($this->response['profile']->meta['favorites']);
            }

            return response()->json([
                'user' => $this->response
            ], 200);

        } catch (\Throwable $th) {
                        
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        
        }
    }

    /**
     ** Fetch user's favorites products
     * 
     * @param array favs
     * @return void
     */
    protected function fetch_favorites(array $favs)
    {
        try {
            $this->response['favorites'] = CoreProduct::where(function($query) use ($favs) {
                foreach ($favs as $id) {
                    $query->orWhere('id', $id);
                }
            })->get();
        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
    }

    
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
}