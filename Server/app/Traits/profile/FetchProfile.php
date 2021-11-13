<?php

namespace App\Traits\Profile;

use App\Models\User;
use App\Models\UserWallet;
use App\Models\CoreProduct;
use App\Models\UserContract;
use Illuminate\Http\Request;
use App\Models\CoreMembership;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\User\Membership;
use Illuminate\Support\Facades\Validator;

trait FetchProfile {

     /**
     ** Fetch user's data
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
    public function fetch_user_data($user_id = null, Request $request) : object
    {
        try {

            $this->user = User::find($user_id);

            //  Get basic user's data
            if($request->has('basic')) {
                $this->response['basic'] = $this->user;

                // Remove favorites if you don't want them
                if (!$request->has('favorites') && $request->has('basic')) {
                    unset($this->response['basic']['meta']['favorites']);
                }

                // Remove scores if you don't want them
                if (!$request->has('scores') && $request->has('basic')) {
                    unset($this->response['basic']['meta']['scores']);
                }
            }

            // Get User's wallet
            if($request->has('wallet')) {
                $this->fetch_wallet($user_id, $request->has('membership'));
            }

            // Get user's receipt
            if($request->has('receipt')) {
                // $this->response['receipt'] = CoreIncoming::where('user_id', $user_id)->get();
                $this->response['receipt'] = $this->user->core_transaction;
            }

            // Get user's contracts
            if($request->has('contract')) {
                $this->response['contract'] = UserContract::where('user_id', $this->user->id)
                                                        ->orWhere('meta->customer_id', $this->user->id)
                                                        ->get();
                
                // we don't allowed to user see token in client
                foreach ($this->response['contract'] as $ad) {
                    if(isset($ad->meta['token'])) {
                        unset($ad->meta['token']);
                    }
                }
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
     ** Fetch public user's data
     * 
     * @param int $id
     * @return Illuminate\Http\Response
     */
    public function fetch_profile_as_guest($id) : object
    {
        try {

            $this->user = User::find($id);

            $this->response['full_name'] = $this->user->full_name;
            $this->response['avatar'] = $this->user->meta['avatar'] ?? null;
            
            $total_score = 0;
            foreach ($this->user->meta['scores'] as $key => $value) {
                // Get user's review and total scores
                if($value['to'] == $id) {
                    $this->response['reviews'][$key] = $value;
                    $total_score = $avg_score + $value['rate'];
                }
            }
            $review_length = $this->response['reviews'] ?? [];
            $this->response['avg_score'] = (count($review_length) > 0)
                ? round($total_score / count($review_length), 1)
                : 0;
            
            // Check user's reviews, if it doesn't exist, we will return 0
            $this->response['reviews'] = $this->response['reviews'] ?? [];

            // Get Successed Contract
            $this->response['successed_contract'] = UserContract::where([
                                                                    'status'    => 2,
                                                                    'user_id'   => $id
                                                                ])
                                                                ->orWhere([
                                                                    'status'            => 2,
                                                                    'meta->customer_id' => $id
                                                                ])->count();
            
            // Get user's activated contracts
            $user_contract = UserContract::where('user_id', $id)
                                        ->where('status', '<', 2)
                                        ->join('core_products', 'core_products.id', '=', 'user_contracts.product_id')
                                        ->select('user_contracts.id', 'user_contracts.meta', 'features')
                                        ->get();
            foreach ($user_contract as $key => $value) {
                $features = json_decode($value->features, true);
                $this->response['contract'][$key] = [
                    'id'    => $value->id,
                    'name'  => $features['name'],
                    'count' => $value->meta['count']
                ];
            }
            
            // Get count of user's activated contreacts
            $this->response['ad_count'] = count($user_contract);

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
            if ($favs !== []) {
                $this->response['favorites'] = CoreProduct::where(function($query) use ($favs) {
                    foreach ($favs as $id) {
                        $query->orWhere('id', $id);
                    }
                })->get();
            }
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
    protected function fetch_wallet(int $user_id, $want_membership)
    {
        try {
            // Check if has membershop
            $membership_class = (new Membership)->is_membership_expired($user_id, 1);
            $this->response['wallet'] = $membership_class['wallet'];

            // Get User's membership data
            if($want_membership) {
                $this->response['wallet']->core_membership;
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
    }
}