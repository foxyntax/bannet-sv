<?php

namespace App\Traits\Contract;

use App\Models\CoreProduct;
use Kavenegar\KavenegarApi;
use Kavenegar\Exceptions\ApiException;
use Kavenegar\Exceptions\HttpException;



trait ContractNotice {
     /** 
     ** Take a notice that seller gets his money by SMS
     * 
     * @return void/Object
    */
    protected function notice_withdrawal() {
        try {
            // Get product's name
            $product = CoreProduct::select('features')->where('id', $this->contract->meta['product_id'])->first();

            // Send SMS to seller
            $seller = User::select('tell')->where('id', $this->contract->user_id)->first();
            KavenegarApi::VerifyLookup($seller->tell, $this->contract->meta['cost'], $product->features['name'], '', 'WithdrawalPendingBalanceForSeller', 'sms');

            // Send SMS to Customer
            $customer = User::select('tell')->where('id', $this->contract->user_id)->first();
            KavenegarApi::VerifyLookup($customer->tell, $this->contract->meta['cost'], $product->features['name'], '', 'WithdrawalPendingBalanceForCustomer', 'sms');
            
        } catch(ApiException $e){
            return response()->json($e->errorMessage(), 412);
        } catch(HttpException $e){
            return response()->json($e->errorMessage(), 412);
        }
    }
    
    /** 
     ** Notice all owners' contract by sending SMS
     * 
     * @param collection $user
     * @return void/Object
    */
    protected function notice_expired_contracts($user) {
        try {
            // Send SMS to seller
            foreach ($users as $user) {
                KavenegarApi::VerifyLookup($user->tell, $user->full_name, $user->expired_at, $user->product_features['name'], 'ExpireAllAds', 'sms');
            }
            
        } catch(ApiException $e){
            return response()->json($e->errorMessage(), 412);
        } catch(HttpException $e){
            return response()->json($e->errorMessage(), 412);
        }
    }

    /** 
     ** Take a notice for sending a withdrawal token to seller by SMS
     * 
     * @return void/Object
    */
    protected function notice_generate_token() {
        try {
            // Send SMS to seller
            $seller = User::select('tell')->where('id', $this->contract->user_id)->first();
            $customer = User::select('full_name')->where('id', $this->contract->meta['customer_id'])->first();
            $product = CoreProduct::select('features')->where('id', $this->contract->meta['product_id'])->first();
            KavenegarApi::VerifyLookup($seller->tell, $this->contract->meta['token'], $customer->full_name, $product->features['name'], 'GetWithdrawalTokenForSeller', 'sms');
            
        } catch(ApiException $e){
            return response()->json($e->errorMessage(), 412);
        } catch(HttpException $e){
            return response()->json($e->errorMessage(), 412);
        }
    }

    /** 
     ** Take a notice that seller cancels contract by SMS
     * 
     * @return void/Object
    */
    protected function notice_cancel_contract() {
        try {
            // Send SMS to Customer
            $customer = User::select('tell')->where('id', $this->contract->user_id)->first();
            $product = CoreProduct::select('features')->where('id', $this->contract->meta['product_id'])->first();
            KavenegarApi::VerifyLookup($customer->tell, $this->contract->meta['cost'], $product->features['name'], '', 'CancelContractForCustomer', 'sms');
            
        } catch(ApiException $e){
            return response()->json($e->errorMessage(), 412);
        } catch(HttpException $e){
            return response()->json($e->errorMessage(), 412);
        }
    }

    
}