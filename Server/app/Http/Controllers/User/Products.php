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
     * @var object $response
     */
    protected $response;

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
     * @return Illuminate\Http\Response
     */
    public function render_product_page($offset, $limit, $get_filters = 'nofilter', Request $request) : object
    {
        try {

            $validator = Validator::make($request->all(), [
                'type'          => 'boolean|bail',
                'full'          => 'boolean|bail',
                'searched'      => 'boolean|bail',
                'for_front'     => 'boolean|bail',
                'for_back'      => 'boolean|bail',
                'brand'         => 'string|bail',
                'width'         => 'string|integer|bail',
                'weight'        => 'string|integer|bail',
                'height'        => 'string|integer|bail',
                'tire_height'   => 'string|integer|bail'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            // Fetch products
            if($request->has('full')) {

                $this->response['products'] = CoreProduct::where('type', $request->type)->offset($offset)->limit($limit)->get();

            } else if($request->has('searched')) {

                $this->fetch_searched_lists($offset, $limit, $request->searched);

            } else {

                $this->fetch_filtered_lists($offset, $limit, $request);

            }

            // Fetch Filter Items
            if($get_filters === 'filter') {
                $this->fetch_filter_items();
            }

            return response()->json([
                'data' => $this->response
            ], 200);

        } catch (\Throwable $th) {
                        
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        
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
        $this->response['products'] = CoreProduct::JsonContains('features->name', $searched)
                                                    ->orWhere(function($query) use ($searched) {
                                                        $query->JsonContains('features->design_name', $searched);
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
                                                        $query->JsonContains('features->brand', $request->brand);
                                                    }

                                                    if ($request->has('width')) {
                                                        $query->JsonContains('features->width', $request->width);
                                                    }

                                                    if ($request->has('weight')) {
                                                        $query->JsonContains('features->weight', $request->weight);
                                                    }
                                                    
                                                    if ($request->has('height')) {
                                                        $query->JsonContains('features->height', $request->height);
                                                    }

                                                    if ($request->has('tire_height')) {
                                                        $query->JsonContains('features->tire_height', $request->tire_height);
                                                    }

                                                    if ($request->has('for_back')) {
                                                        $query->JsonContains('features->for_back', $request->for_back);
                                                    }

                                                    if ($request->has('for_front')) {
                                                        $query->JsonContains('features->for_front', $request->for_front);
                                                    }
                                                    
                                                })
                                                ->offset($offset)
                                                ->limit($limit)
                                                ->get();
    }

}
