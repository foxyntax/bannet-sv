<?php

namespace App\Traits\Profile;

use App\Models\User;
use App\Models\UserWallet;
use App\Models\CoreProduct;
use App\Models\CoreMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

trait UpdateProfile {
    

    /**
     ** Save User's Data based mode from client
     *
     * @param int $user_id
     * @param string $mode
     * @param Illuminate\Http\Request
     * @return Illuminate\Http\Response
     */
    public function update_user_data(int $user_id, string $mode = 'switcher', Request $request) : object
    {
        try {

            // Set reponse true before any progress to avoid get unnecessary error
            $this->response = true;

            // Fetch User's data
            $this->user = User::find($user_id);

            switch ($mode) {
                case 'basic':
                    $this->update_user_basic($request);
                    break;
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
            if(! $this->response || is_null($this->response)) {
                return response()->json([
                    'status' => $this->response,
                    'error'  => $this->validator->errors()
                ], 500);
            }

            if ($mode !== 'switcher' && $mode !== 'basic') {
                return response()->json($this->user->meta[$mode], 200);
                // return response()->json($request->has('assd'), 200);
            } else if ($mode === 'basic') {
                return response()->json([
                    'full_name' => $this->user->full_name
                ], 200);
            } else {
                return response()->json($this->response, 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Update user's basic information
     *
     * @param Illuminate\Http\Request full_name
     * @return void
     */
    protected function update_user_basic($request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'string'
            ]);
    
            if($validator->fails()) {
                $this->response = false;
                $this->validator = $validator->errors(); return;
            }

            // Save user's basic information
            $this->user->full_name = ($request->has('full_name')) ? $request->full_name : $this->user->full_name;

            $this->user->save();
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
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
                'phone'         => 'string|size:11|bail',
                'postal_code'   => 'string|size:10|bail'
            ]);
    
            if($validator->fails()) {
                $this->response = false;
                $this->validator = $validator->errors(); return;
            }
    
            // Delete avaialbe avatar first, if $request has new avatar or even just delete avatar
            if($request->has('avatar') && ! is_null($this->user->meta['personal']['avatar'])) {
                Storage::delete($this->user->meta['personal']['avatar']);
                $this->user->meta['personal']['avatar']   = $request->file('avatar')->store("users/".$this->user->id);
            } else if ($request->has('avatar')) {
                $this->user->meta['personal']['avatar']   = $request->file('avatar')->store("users/".$this->user->id);
            } else if ($request->has('deleted_avatar')) {
                Storage::delete($this->user->meta['personal']['avatar']);
                $this->user->meta['personal']['avatar']   = '';
            }

            // Save user's personal information
            $this->user->meta['personal']['province']    = $request->province ?? $this->user->meta['personal']['province'];
            $this->user->meta['personal']['city']        = $request->city ?? $this->user->meta['personal']['city'];
            $this->user->meta['personal']['address']     = $request->address ?? $this->user->meta['personal']['address'];
            $this->user->meta['personal']['postal_code'] = $request->postal_code ?? $this->user->meta['personal']['postal_code'];
            $this->user->meta['personal']['phone']       = $request->phone ?? $this->user->meta['personal']['phone'];

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
            $this->validator = Validator::make($request->all(), [
                'debit_card.*'      => 'mimes:jpg,hevc,heif,png|bail',
                'national_id.*'     => 'mimes:jpg,hevc,heif,png|bail',
                'license_card.*'    => 'mimes:jpg,hevc,heif,png|bail',
                'debit_card_value'  => 'string|size:16|bail',
                'national_id_value' => 'string|size:10|bail',
                'license_card_value'=> 'string|bail',
                'shabaa'            => 'string|bail'
            ]);
    
            if($this->validator->fails()) {
                $this->response = false;
                $this->validator = $validator->errors(); return;
            }

            // Get request instance for helpers
            $this->request = $request;

            // Save files if it is needed
            $this->save_updated_financial_file($request->has('debit_card'), 'debit_card');
            $this->save_updated_financial_file($request->has('license_card'), 'license_card');
            $this->save_updated_financial_file($request->has('national_id'), 'national_id');

            // Save user's financial information
            $this->save_updated_value($request->has('debit_card_value'), 'debit_card_value', 'financial', 'debit_card', 'value');
            $this->save_updated_value($request->has('national_id_value'), 'national_id_value', 'financial', 'national_id', 'value');
            $this->save_updated_value($request->has('license_card_value'), 'license_card_value', 'financial', 'license_card', 'value');
            $this->save_updated_value($request->has('shabaa'), 'shabaa', 'financial', 'shabaa');

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
                $this->validator = $validator->errors(); return;
            }

            // Save user's scores information
            array_push($this->user->meta['scores'], [
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
                $this->validator = $validator->errors(); return;
            }

            // Convert to Array
            if(!is_array($this->user->meta['favorites'])) {
                $this->user->meta['favorites'] = explode(',', (string) $this->user->meta['favorites']);
            }

            // Save or Remove ID
            if (in_array($request->product_id, $this->user->meta['favorites'])) {
                $key = array_search($request->product_id, $this->user->meta['favorites']);
                unset($this->user->meta['favorites'][$key]);
            } else {
                array_push($this->user->meta['favorites'], $request->product_id);
            }

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
                $this->validator = $validator->errors(); return;
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

    /**
     ** Mutate to upload files and save it in financial section
     * 
     * @param string $part
     * @return void
     */
    protected function save_updated_financial_file($condition, $part)
    {
        if($condition && $this->request->has($part)) {
            $this->user->meta['financial'][$part]['img']  = $this->request->file($part)->store("users/" . $this->user->id . "/$part");
            // Reset validation status
            $this->user->meta['financial'][$part]['validated'] = 0;
        }
    }
    
    /**
     ** Mutate to upload files and save it in financial section
     * 
     * @param string $req_name
     * @return void
     */
    protected function save_updated_value(bool $condition, string $req_name, string $grand_parent, string $parent = '', string $child = '')
    {
        if($condition) {
            if($parent === '') {
                $this->user->meta[$grand_parent] = $this->request->input($req_name);
            } else if ($child === '') {
                $this->user->meta[$grand_parent][$parent] = $this->request->input($req_name);
            } else {
                $this->user->meta[$grand_parent][$parent][$child] = $this->request->input($req_name);

                if ($grand_parent === 'financial') {
                    // Reset validation status
                    $this->user->meta[$grand_parent][$parent]['validated'] = 0;
                }
            }
        }
    }
}