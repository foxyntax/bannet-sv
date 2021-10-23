<?php

namespace App\Traits\Profile;

use App\Models\User;
use App\Models\UserWallet;
use App\Models\CoreProduct;
use Illuminate\Http\Request;
use App\Models\CoreMembership;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\User\Membership;
use Illuminate\Support\Facades\Validator;

trait FetchProfile {

     /**
     ** Fetch Lists By Features
     * 
     * @param int $user_id
     * @return Illuminate\Http\Request profile
     * @return Illuminate\Http\Request wallet
     * @return Illuminate\Http\Request membership
     * @return Illuminate\Http\Request receipt
     * @return Illuminate\Http\Request contract
     * @return Illuminate\Http\Request scores
     * @return Illuminate\Http\Response
     */
    public function fetch_user_data(int $user_id = null, Request $request) : object
    {
        try {

            $this->user = User::find($user_id);

            //  Get basic user's data
            if($request->has('profile')) {
                $this->response['profile'] = $this->user;

                // Remove favorites if you don't want them
                if (!$request->has('favorites') && $request->has('profile')) {
                    unset($this->response['profile']['meta']['favorites']);
                }

                // Remove scores if you don't want them
                if (!$request->has('scores') && $request->has('profile')) {
                    unset($this->response['profile']['meta']['scores']);
                }
            }

            // Is Neccesary 

            // Get User's wallet
            if($request->has('wallet')) {
                $this->fetch_wallet($user_id);
            }

            // Get user's receipt
            if($request->has('receipt')) {
                // $this->response['receipt'] = CoreIncoming::where('user_id', $user_id)->get();
                $this->response['receipt'] = $this->user->core_transaction;
            }

            // Get user's contracts
            if($request->has('contract')) {
                // $this->response['contract'] = UserContract::where('user_id', $user_id)->get();
                $this->response['contract'] = $this->user->user_contracts;
            }

            // Get user's favorites products
            if($request->has('favorites')) {
                $this->fetch_favorites($this->user->meta['favorites']);
            }

            return response()->json(
                $this->response
            , 200);

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
     ** Fetch user's wallet
     * 
     * @param int user_id
     * @return void
     */
    protected function fetch_wallet(int $user_id)
    {
        try {
            $this->response['wallet'] = UserWallet::where('user_id', $user_id)->first();

            // Check if has membershop
            $has_membership = Membership::is_memebrship_expired($user_id, 1);

            // Get User's membership data
            if($has_membership && $request->has('membership')) {
                $this->response['wallet']->core_membership;
            } else if (! $has_membership) {
                $this->response['wallet']->membership_id = null;
                $this->response['wallet']->expired_at = null;
                $this->response['wallet']->save();
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
    }
}