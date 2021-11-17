<?php

namespace App\Http\Controllers\User;

use Carbon\Carbon;
use App\Models\User;
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


    public function __construct() {
        $this->response['ads']          = [];
        $this->response['my_ads']       = [];
        $this->response['ad_avg']       = [];
        $this->response['is_saved']     = [];
        $this->response['ad_count']     = 0;
        $this->response['tyre_count']   = 0;
    }

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
            $this->fetch_ads($product_id, $user_id);

            // Fetch ads of user and if it's a favorite product, also if user exists
            if($user_id != 0) {
                $user = User::where('id', $user_id)->count();
                if ($user !== 0) {
                    $this->fetch_user_contract($product_id, $user_id);
                    $this->is_favorite($product_id, $user_id);
                }
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
     ** Fetch ads by product
     *  
     * @param int $product_id
     * @return void
     */
    protected function fetch_ads(int $product_id, int $user_id = 0)
    {
        // Fetch all ads 
        /**
         /* Note: You have to hide ads that logged user have them in client side.
         */
        $this->response['ads'] = UserContract::with('user')
                                                ->where([
                                                    'product_id' => $product_id,
                                                    'status'     => 0
                                                ]);
                                                // ->whereDate('expired_at', '>=',  Carbon::now()->toDateString());
        if ($user_id !== 0) {
            $this->response['ads'] = $this->response['ads']->where('user_id', '!=', $user_id)->get();
        } else {
            $this->response['ads'] = $this->response['ads']->get();
        }
        
        $this->get_avg_cost();
    }

    /**
     ** Fetch user information about the product
     *  
     * @param int $product_id
     * @param int $user_id
     * @return void
     */
    protected function fetch_user_contract(int $product_id, int $user_id)
    {
        // Fetch all ads
        $this->response['my_ads'] = UserContract::where([
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

        //             $this->response['my_ads'] = true; break;

        //         }

        //         $this->response['my_ads'] = false;
        //     }

        // } else {

        //     $this->response['is_favorite'] = false;

        // }
    }

    /**
     ** Get Average cost of ads
     * 
     * @return void
     */
    protected function get_avg_cost()
    {
        $totalCost = 0;
        $this->response['tyre_count'] = 0;
        $this->response['ad_count'] = count($this->response['ads']);

        if($this->response['ad_count'] > 0) {
            foreach ($this->response['ads'] as $contract) {
                $totalCost                    += (int) $contract->meta['cost'];
                $this->response['tyre_count'] += (int) $contract->meta['count'];
            }

            $this->response['ad_avg'] = round($totalCost / $this->response['tyre_count']);
        } else {
            $this->response['ad_avg'] = 0;
        }
    }

    /**
     ** Is a favorite product?
     *  
     * @param int $product_id
     * @param int $user_id
     * @return void
     */
    protected function is_favorite(int $product_id, int $user_id)
    {
        $meta_col = User::where('id', $user_id)->select('meta')->first();
        $this->response['is_saved'] = in_array($product_id, $meta_col->meta['favorites']);
    }
}
