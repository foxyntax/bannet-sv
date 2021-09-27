<?php

namespace App\Traits\Profile;

use App\Models\UserWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

trait Wallet {
    

    /**
     ** Send withdrawal request to admin or add increase withdrawal balance
     *
     * @param int $user_id
     * @param Illuminate\Http\Request cost
     * @return Illuminate\Http\Response
     */
    public function withdrawal_request(int $user_id, Request $request) : object
    {
        try {

            $validator = Validator::make($request->all(), [
                'cost'  => 'integer|required'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            // Fetch User's wallet data 
            $wallet = UserWallet::where('user_id', $user_id)->select('withdraw_balance', 'available_balance')->firstOrFail();

            // Check if the cost is less than available balance
            if($wallet->available_balance >= $request->cost) {

                $wallet->withdraw_balance = $wallet->withdraw_balance + $request->cost;
                $wallet->save();

                return response()->json([
                    'status' => true
                ], 200);
            }

            return response()->json([
                'status' => false
            ], 400);

        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
    }

    /**
     ** Accept withdrawal request from admin
     *
     * @param int $user_id
     * @param Illuminate\Http\Request cost
     * @return Illuminate\Http\Response
     */
    public function accept_withdrawal_req(int $user_id, Request $request) : object
    {
        try {

            $validator = Validator::make($request->all(), [
                'cost'  => 'integer|required'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            // Fetch User's wallet data 
            $wallet = UserWallet::where('user_id', $user_id)->select('withdraw_balance')->firstOrFail();

            // Check if the cost is less than withdraw balance
            if($wallet->withdraw_balance >= $request->cost) {
                
                $wallet->withdraw_balance = $wallet->withdraw_balance - $request->cost;
                $wallet->save();

                return response()->json([
                    'status' => true
                ], 200);
            }

            return response()->json([
                'status' => false
            ], 400);

        } catch (\Throwable $th) {
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        }
    }
   
}