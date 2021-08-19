<?php

namespace App\Http\Controllers\User;

use App\Models\CoreProduct;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Settings\Models\CoreOption;
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
    public function render_product_page($offset, $limit, $get_filters = 0, Request $request) : object
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
                $this->fetch_products($offset, $limit, $request);

                // Fetch Filter Items
                if($get_filters == 1) {
                    $this->fetch_filter_items();
                }

                return response()->json([
                    'data' => $this->response
                ], $this->http);
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
    protected function fetch_products($offset, $limit, Request $request)
    {
        if($request->has('full')) {

            $this->response['products'] = CoreProduct::where('type', $request->type)->offset($offset)->limit($limit)->get();

        } else if($request->has('searched')) {

            $this->fetch_searched_lists($offset, $limit, $request->searched);

        } else if($request->has('filtered')) {

            $this->fetch_filtered_lists($offset, $limit, $request);

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
        $this->response['filters']['brands'] = CoreOption::where('option', 'saved_brand')->select('value')->first();
        $this->response['filters']['width'] = CoreOption::where('option', 'saved_width')->select('value')->first();
        $this->response['filters']['weight'] = CoreOption::where('option', 'saved_weight')->select('value')->first();
        $this->response['filters']['height'] = CoreOption::where('option', 'saved_height')->select('value')->first();
        $this->response['filters']['tire_height'] = CoreOption::where('option', 'saved_tire_height')->select('value')->first();
    }


    /**
     ** fetch a product/products that was searched by client
     *  
     * @param int $offset
     * @param int $limit
     * @param Illuminate\Http\Request $searched
     * @return void
     */
    protected function fetch_searched_lists($offset, $limit, $searched)
    {
        $this->response['products'] = CoreProduct::where('features->name', $searched)
                                                    ->orWhere(function($query) use ($searched) {
                                                        $query->where('features->design_name', $searched);
                                                    })
                                                    ->offset($offset)
                                                    ->limit($limit)
                                                    ->get();
    }

    /**
     ** Fetch  product/products that was filtered by client
     * 
     * @param int $offset
     * @param int $limit
     * @param Illuminate\Http\Request $request
     * @return void
     */
    protected function fetch_filtered_lists($offset, $limit, $request)
    {
        $this->response['products'] = CoreProduct::where(function($query) use ($request) {

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
                                                ->offset($offset)
                                                ->limit($limit)
                                                ->get();
    }

}
