<?php

namespace App\Http\Controllers\User;

use Carbon\Carbon;
use App\Models\CoreProduct;
use App\Models\UserContract;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductDetail extends Controller
{

    /**
     * @var object $response
     */
    protected $response;

    /**
     ** Fetch Lists By Features
     * 
     * @param int $product_id
     * @param int $user_id
     * @return Illuminate\Http\Response
     */
    public function render_product_detail(int $product_id, $user_id = 0) : object
    {
        try {

            // Fetch product
            $this->fetch_product_information($product_id);

            // Fetch Advertisments of product
            $this->fetch_contracts($product_id);

            // Fetch ads of user, if user exists
            if($user_id != 0) {
                $this->fetch_user_contract($product_id, $user_id);
            }

            return response()->json([
                'data'     => $this->response
            ], 200);

        } catch (\Throwable $th) {
                        
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        
        }
    }

    /**
     ** Fetch information of certain product
     *  
     * @param int $product_id
     * @return void
     */
    protected function fetch_product_information(int $product_id)
    {
        $this->response['information'] = CoreProduct::find($product_id);
    }

    /**
     ** Fetch contracts by product
     *  
     * @param int $product_id
     * @return void
     */
    protected function fetch_contracts(int $product_id)
    {
        // Fetch all contracts 
        /**
         /* Note: You have to hide contracts that logged user have them in client side.
         */
        $this->response['contracts'] = UserContract::with('user')
                                                    ->where([
                                                        'product_id' => $product_id,
                                                        'status'     => 0
                                                    ])
                                                    ->whereDate('expired_at', '>=',  Carbon::now()->toDateString())
                                                    ->get();
        $this->get_avg_cost();
    }

    /**
     ** Fetch user information about the product
     *  
     * @param int $user_id
     * @return void
     */
    protected function fetch_user_contract(int $product_id, int $user_id)
    {
        // Fetch all contracts
        $this->response['my_contracts'] = UserContract::where([
                                                            'product_id' => $product_id,
                                                            'user_id'    => $user_id
                                                        ])
                                                        ->where('status', '!=', 3)
                                                        ->whereDate('expired_at', '>=',  Carbon::now()->toDateString())
                                                        ->get();

        /** 
         ** is product a favorite item for user? */
        /**
         /* Note: You can check favorite products from client side
         /* Note: for doing it, you can use data fetched from user controllers
         /* and get data what you need from their API
         /* Also I didn't delete below codes, because maybe it will be used
         /* in another situtations. 
         */
        // $user = User::find($user_id);
        // if(count($user->meta['favorites']) > 0) {
        //     foreach ($user->meta['favorites'] as $id) {

        //         if($id == $product_id) {

        //             $this->response['my_contracts'] = true; break;

        //         }

        //         $this->response['my_contracts'] = false;
        //     }

        // } else {

        //     $this->response['is_favorite'] = false;

        // }
    }

    /**
     ** Get Average cost of contracts
     * 
     * @return void
     */
    protected function get_avg_cost()
    {
        $total = 0;
        $this->response['contract_count'] =  count($this->response['contracts']);

        if($this->response['contract_count'] > 0) {
            foreach ($this->response['contracts'] as $contract) {
                $total += (int) $contract->meta['cost'];
            }

            $this->response['contract_avg'] = round($total / $this->response['contract_count']);

        }
    }
}
