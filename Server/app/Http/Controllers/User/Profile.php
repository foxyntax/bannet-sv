<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use App\Models\UserWallet;
use App\Models\CoreProduct;
// use App\Models\CoreIncoming;
// use App\Models\UserContract;
use Illuminate\Http\Request;
use App\Models\CoreMembership;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
            $this->response = CoreMembership::where('status', 0)->get();

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
     ** Save User's Data based mode from client
     *
     * @param string $mode
     * @param int $user_id
     * @param Illuminate\Http\Request
     * @return Illuminate\Http\Response
     */
    public function update_user_data(string $mode = 'switcher', int $user_id, Request $request) : object
    {
        try {
            // Fetch User's data
            $this->user = User::find($user_id);

            switch ($mode) {
                case 'personal':
                    $this->update_user_personal($request);
                    break;
                case 'financial':
                    $this->update_user_financial($request);
                    break;
                case 'scores':
                    $this->update_user_scores($request);
                    break;
                case 'favorites':
                    $this->update_user_favorites($request);
                    break;
                default:
                    $this->switch_admin_mode($request);
            }

            // Catch err if there are any problem in top funcs
            if(! $this->response) {
                return response()->json([
                    'error' => $this->validator->errors()
                ], 500);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Update user's personal information
     *
     * @param Illuminate\Http\Request avatar
     * @param Illuminate\Http\Request province
     * @param Illuminate\Http\Request city
     * @param Illuminate\Http\Request address
     * @param Illuminate\Http\Request postal_code
     * @return void
     */
    protected function update_user_personal($request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'avatar'        => 'mimes:jpg,hevc,heif,png|bail',
                'province'      => 'string|bail',
                'city'          => 'string|bail',
                'address'       => 'string|bail',
                'postal_code'   => 'string|size:10|bail',
            ]);
    
            if($validator->fails()) {
                $this->response = false;
                $this->validator = $validator->errors();
            }
    
            // Delete avaialbe avatar first, if $request has new avatar
            if($request->has('avatar') && ! is_null($this->user->meta['personal']['avatar'])) {
                Storage::delete($this->user->meta['personal']['avatar']);
                $this->user->meta['personal']['avatar']   = $request->file('avatar')->store($this->user->id);
            } else if ($request->has('avatar')) {
                $this->user->meta['personal']['avatar']   = $request->file('avatar')->store($this->user->id);
            }

            // Save user's personal information
            $this->user->meta['personal']['province']     = $request->has('province') ? $request->province : isset($this->user->meta['personal']['province']);
            $this->user->meta['personal']['city']         = $request->has('city') ? $request->city : isset($this->user->meta['personal']['city']);
            $this->user->meta['personal']['address']      = $request->has('address') ? $request->address : isset($this->user->meta['personal']['address']);
            $this->user->meta['personal']['postal_code']  = $request->has('postal_code') ? $request->postal_code : isset($this->user->meta['personal']['postal_code']);

            $this->user->save();
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Update user's financial information
     *
     * @param Illuminate\Http\Request debit_card
     * @param Illuminate\Http\Request national_id
     * @param Illuminate\Http\Request license_card
     * @param Illuminate\Http\Request debit_card_value
     * @param Illuminate\Http\Request national_id_value
     * @param Illuminate\Http\Request license_card_value
     * @param Illuminate\Http\Request shabaa
     * @return void
     */
    protected function update_user_financial($request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'debit_card'        => 'mimes:jpg,hevc,heif,png|bail',
                'national_id'       => 'mimes:jpg,hevc,heif,png|bail',
                'license_card'      => 'mimes:jpg,hevc,heif,png|bail',
                'debit_card_value'  => 'string|size:12|bail',
                'national_id_value' => 'string|size:10|bail',
                'license_card_value'=> 'string|bail',
                'shabaa'            => 'string|bail'
            ]);
    
            if($validator->fails()) {
                $this->response = false;
                $this->validator = $validator->errors();
            }

            // Save files if it is needed
            if($request->has('debit_card')) {
                $this->user->meta['financial']['debit_card']['img']  = $request->file('debit_card')->store($this->user->id);
            }
            if($request->has('national_id')) {
                $this->user->meta['financial']['national_id']['img'] = $request->file('national_id')->store($this->user->id);
            }
            if($request->has('license_card')) {
                $this->user->meta['financial']['license_card']['img']= $request->file('license_card')->store($this->user->id);
            }

            // Save user's financial information
            $this->user->meta['financial']['debit_card']['value']    = $request->has('debit_card_value') ? $request->debit_card_value : isset($this->user->meta['financial']['debit_card']['value']); 
            $this->user->meta['financial']['national_id']['value']   = $request->has('national_id_value') ? $request->national_id_value : isset($this->user->meta['financial']['national_id']['value']); 
            $this->user->meta['financial']['license_card']['value']  = $request->has('license_card_value') ? $request->license_card_value : isset($this->user->meta['financial']['license_card']['value']); 
            $this->user->meta['financial']['shabaa']                 = $request->has('shabaa') ? $request->shabaa : isset($this->user->meta['financial']['shabaa']);

            $this->user->save();
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Update user's scores information
     *
     * @param Illuminate\Http\Request sender_id
     * @param Illuminate\Http\Request rate
     * @param Illuminate\Http\Request desc
     * @param Illuminate\Http\Request is_seller
     * @return void
     */
    protected function update_user_scores($request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'sender_id'  => 'integer|required|bail',
                'rate'       => 'integer|between:0,5|required|bail',
                'desc'       => 'string|bail',
                'is_seller'  => 'integer|size:1|bail'
            ]);
    
            if($validator->fails()) {
                $this->response = false;
                $this->validator = $validator->errors();
            }

            // Save user's scores information
            $this->user->meta['scores'] = array_push($this->user->meta['scores'], [
                'sender_id' => $request->sender_id,
                'rate'      => $request->rate,
                'desc'      => $request->desc,
                'is_seller' => $request->is_seller
            ]);

            $this->user->save();
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Update user's favorites information
     *
     * @param Illuminate\Http\Request product_id
     * @return void
     */
    protected function update_user_favorites($request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'product_id'  => 'integer|required'
            ]);
    
            if($validator->fails()) {
                $this->response = false;
                $this->validator = $validator->errors();
            }

            // Save user's favorites information
            $this->user->meta['favorites'] = array_push($this->user->meta['favorites'], $request->product_id);

            $this->user->save();
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Update user's administration information
     *
     * @param Illuminate\Http\Request is_admin
     * @return void
     */
    protected function switch_admin_mode($request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'is_admin'  => 'integer'
            ]);
    
            if($validator->fails()) {
                $this->response = false;
                $this->validator = $validator->errors();
            }

            // Save user's administration information
            if($request->is_admin) {
                $this->user->is_admin = $request->is_admin;
                $this->user->save();
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
