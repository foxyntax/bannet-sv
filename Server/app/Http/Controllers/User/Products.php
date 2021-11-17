<?php

namespace App\Http\Controllers\User;

use Carbon\Carbon;
use App\Models\CoreOption;
use App\Models\CoreProduct;
use App\Models\UserContract;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class Products extends Controller
{
    /**
     * @var Illuminate\Http\Request
     */
    protected $request;


    /**
     * @var object $response
     */
    protected $response;

    /**
     * Http Status code instance
     * 
     * @var int $http
     */
    protected $http;

    /**
     * set 200 for default http status
     * 
     * @return void
     */
    public function __construct()
    {
        $this->http = 200;
    }
    

    /**
     ** Render product page [ for first / for show more products ]
     * 
     * @param int $offset
     * @param int $limit
     * @param int $get_filter
     * @param int $city
     * @param Illuminate\Http\Request $type
     * @param Illuminate\Http\Request $full
     * @param Illuminate\Http\Request $searched
     * @param Illuminate\Http\Request $for_front
     * @param Illuminate\Http\Request $for_back
     * @param Illuminate\Http\Request $brand
     * @param Illuminate\Http\Request $width
     * @param Illuminate\Http\Request $weight
     * @param Illuminate\Http\Request $height
     * @param Illuminate\Http\Request $tire_height
     * @return Illuminate\Http\Response
     */
    public function render_product_page(int $offset, int $limit, int $get_filters = 0, string $city, Request $request) : object
    {
        try {

            $validator = Validator::make($request->all(), [
                'type'          => 'int|required|bail',
                'for_front'     => 'int|bail',
                'for_back'      => 'int|bail',
                'brand'         => 'string|bail',
                'width'         => 'string|bail',
                'weight'        => 'string|bail',
                'height'        => 'string|bail',
                'tire_height'   => 'string|bail'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            if($this->safe_request($request)) {
                $this->fetch_products($offset, $limit, $city, $request);

                // Fetch Filter Items
                if($get_filters == 1) {
                    $this->fetch_filter_items();
                }

                // Decode Features column
                $this->decode_features();

                return response()->json($this->response, $this->http);
            } else {
                return response()->json([
                    'error' => 'You used atleast 2 types of request at same time.'
                ], 400);
            }
            

        } catch (\Throwable $th) {
                        
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        
        }
    }

    /**
     * Stop progress if we have bad request 
     * 
     * @param Illuminate\Http\Request $request
     * @return bool
     */
    protected function safe_request(Request $request)
    {
        $types = [
            ($request->has('full')),
            ($request->has('searched')),
            ($request->has('filtered'))
        ];

        $availabled_types = array_filter($types, function($type) {
            return ($type === true);
        });

        return (count($availabled_types) <= 1);
    }

    /**
     * fetch products, based on type request
     * 
     * @param int offset
     * @param int limit
     * @param Illuminate\Http\Request $request
     * @return object
     */
    protected function fetch_products($offset, $limit, $city, Request $request)
    {
        if($request->has('full')) {

            $this->fetch_lists($offset, $limit, $city, $request);

        } else if($request->has('searched')) {

            $this->fetch_searched_lists($offset, $limit, $city, $request);

        } else if($request->has('filtered')) {

            $this->fetch_filtered_lists($offset, $limit, $city, $request);

        } else {

            $this->response = 'You used unavailable type request to fetch data or you didn\'t use any type in.';
            $this->http = 400;
        }
    }

    /**
     * Fetch Filter Items into @var object $response
     * 
     * @return void
     */
    protected function fetch_filter_items()
    {
        // get brands
        $this->response['filters']['brands'] = CoreOption::where('option', 'SAVED_BRAND')->select('value')->first();
        $this->response['filters']['width'] = CoreOption::where('option', 'SAVED_WIDTH')->select('value')->first();
        $this->response['filters']['weight'] = CoreOption::where('option', 'SAVED_WEIGHT')->select('value')->first();
        $this->response['filters']['height'] = CoreOption::where('option', 'SAVED_HEIGHT')->select('value')->first();
        $this->response['filters']['tyre_height'] = CoreOption::where('option', 'SAVED_TYRE_HEIGHT')->select('value')->first();
    }

    /**
     ** fetch a product/products without any filters or search condition
     *  
     * @param int $offset
     * @param int $limit
     * @param string $city
     * @param Illuminate\Http\Request $request
     * @return void
     */
    protected function fetch_lists($offset, $limit, $city, $request)
    {
        $fechted = UserContract::where('meta->city', $city)
                               ->where('status', 0)
                            //    ->whereDate('expired_at', '>=',  Carbon::now()->toDateString())
                               ->where('core_products.type', $request->type)
                               ->join('core_products', 'user_contracts.product_id', '=', 'core_products.id')
                               ->select('product_id as id', 'features', 'type')
                               ->distinct();
        $this->response['products'] = $fechted->offset($offset)->limit($limit)->get();
        $this->response['count'] = $fechted->count('product_id');
    }


    /**
     ** fetch a product/products that was searched by client
     *  
     * @param int $offset
     * @param int $limit
     * @param string $city
     * @param Illuminate\Http\Request $request
     * @return void
     */
    protected function fetch_searched_lists($offset, $limit, $city, $request)
    {
        $fechted = UserContract::where('meta->city', $city)
                               ->where('status', 0)
                               ->whereDate('expired_at', '>=',  Carbon::now()->toDateString())
                               ->where('core_products.type', $request->type)
                               ->where(function($query) use ($request) {
                                    $query->where('features->name', 'like', "%$request->searched%")
                                          ->orWhere('features->design_name', 'like', "%$request->searched%");
                               })
                               ->join('core_products', 'user_contracts.product_id', '=', 'core_products.id')
                               ->select('product_id as id', 'features', 'type')
                               ->distinct();
                               
        $this->response['products'] = $fechted->offset($offset)->limit($limit)->get();
        $this->response['count'] = $fechted->count('product_id');
        $this->response['searched'] = $request->searched;
    }

    /**
     ** Fetch  product/products that was filtered by client
     * 
     * @param int $offset
     * @param int $limit
     * @param string $city
     * @param Illuminate\Http\Request $request
     * @return void
     */
    protected function fetch_filtered_lists($offset, $limit, $city, $request)
    {
        $fechted = UserContract::where('meta->city', $city)
                               ->where('status', 0)
                               ->whereDate('expired_at', '>=',  Carbon::now()->toDateString())
                               ->where('core_products.type', $request->type)
                               ->where(function($query) use ($request) {

                                    if ($request->has('brand')) {
                                        $query->where('features->brand', $request->brand);
                                    }

                                    if ($request->has('width')) {
                                        $query->where('features->width', $request->width);
                                    }

                                    if ($request->has('weight')) {
                                        $query->where('features->weight', $request->weight);
                                    }
                                    
                                    if ($request->has('height')) {
                                        $query->where('features->height', $request->height);
                                    }

                                    if ($request->has('tire_height')) {
                                        $query->where('features->tire_height', $request->tire_height);
                                    }

                                    if ($request->has('for_back')) {
                                        $query->where('features->for_back', $request->for_back);
                                    }

                                    if ($request->has('for_front')) {
                                        $query->where('features->for_front', $request->for_front);
                                    }
                                    
                                })
                               ->join('core_products', 'user_contracts.product_id', '=', 'core_products.id')
                               ->select('product_id as id', 'features', 'type')
                               ->distinct();
        $this->response['products'] = $fechted->offset($offset)->limit($limit)->get();
        $this->response['count'] = $fechted->count('product_id');
    }
    
    /**
     ** Decode Features column
     * 
     * @return void
     */
    protected function decode_features()
    {
        if (count($this->response['products']) !== 0) {
            $this->response['products'] = collect($this->response['products'])->map(function ($item) {
                $item->features = json_decode($item->features);
                return $item;
            });
        }
    }

}
