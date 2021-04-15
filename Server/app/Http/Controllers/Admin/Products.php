<?php

namespace App\Http\Controllers\Admin;

use App\Models\CoreProduct;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Settings\Models\CoreOption;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException as ModelNotFound;

/**
 ** Here I show the methods that I need to develop 
 * 
 // 1. create_product
 // 2. update_product
 // 3. delete_product
 // 4. fetch_products
 // 5. fetch_product_detail
 * 
 */

class Products extends Controller
{
    /**
     * @var Illuminate\Http\Response response
     */
    protected $response;

    /**
     * @var collection product
     */
    protected $product;

    /**
     ** Create new product
    //  NOTE: I was not sure, how multiple uploads work on laravel?? please test it
     * 
     * @param Illuminate\Http\Request type
     * @param Illuminate\Http\Request name as feature.name
     * @param Illuminate\Http\Request design_name as feature.design_name
     * @param Illuminate\Http\Request brand as feature.brand
     * @param Illuminate\Http\Request diameter as feature.diameter
     * @param Illuminate\Http\Request color as feature.color
     * @param Illuminate\Http\Request country as feature.country
     * @param Illuminate\Http\Request for_back as feature.for_back
     * @param Illuminate\Http\Request for_front as feature.for_front
     * @param Illuminate\Http\Request height as feature.height
     * @param Illuminate\Http\Request tire_height as feature.tire_height
     * @param Illuminate\Http\Request tubless as feature.tubless
     * @param Illuminate\Http\Request speed as feature.speed
     * @param Illuminate\Http\Request width as feature.width
     * @param Illuminate\Http\Request weight as feature.weight
     * @param Illuminate\Http\Request src as feature.src
     * @return Illuminate\Http\Response
     */
    public function create_product(Request $request) : object
    {
        try {

            $validator = Validator::make($request->all(), [
                'type'          => 'integer|required|bail',
                'name'          => 'string|required|bail',
                'design_name'   => 'string|required|bail',
                'diameter'      => 'integer|required|bail',
                'color'         => 'string|required|bail',
                'country'       => 'string|required|bail',
                'for_back'      => 'boolean|required|bail',
                'for_front'     => 'boolean|required|bail',
                'height'        => 'integer|required|bail',
                'tubless'       => 'integer|required|bail',
                'speed'         => 'integer|required|bail',
                'tire_height'   => 'integer|required|bail',
                'width'         => 'integer|required|bail',
                'weight'        => 'integer|required|bail'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            // new product
            $this->product = new CoreProduct;
            $this->fill_product_model($request);
            
            // Upload src of pictures
            foreach ($request->file('src') as $src) {
                $this->product->src = array_push($this->product->src, $request->file($src)->store('product/' . $request->name));
            }

            $this->product->save();

            return response()->json([
                'status' => true
            ], 200);

        } catch (\Throwable $th) {
                        
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        
        }
    }

    
    /**
     ** Update a product
    //  NOTE: I was not sure, how multiple uploads work on laravel?? please test it
     * 
     * @param int $product_id
     * @param Illuminate\Http\Request type
     * @param Illuminate\Http\Request name as feature.name
     * @param Illuminate\Http\Request design_name as feature.design_name
     * @param Illuminate\Http\Request brand as feature.brand
     * @param Illuminate\Http\Request diameter as feature.diameter
     * @param Illuminate\Http\Request color as feature.color
     * @param Illuminate\Http\Request country as feature.country
     * @param Illuminate\Http\Request for_back as feature.for_back
     * @param Illuminate\Http\Request for_front as feature.for_front
     * @param Illuminate\Http\Request height as feature.height
     * @param Illuminate\Http\Request tire_height as feature.tire_height
     * @param Illuminate\Http\Request tubless as feature.tubless
     * @param Illuminate\Http\Request speed as feature.speed
     * @param Illuminate\Http\Request width as feature.width
     * @param Illuminate\Http\Request weight as feature.weight
     * @param Illuminate\Http\Request create_src as feature.src
     * @param Illuminate\Http\Request delete_src
     * @return Illuminate\Http\Response
     */
    public function update_product(int $product_id, Request $request) : object
    {
        try {

            $validator = Validator::make($request->all(), [
                'type'          => 'integer|required|bail',
                'name'          => 'string|required|bail',
                'design_name'   => 'string|required|bail',
                'diameter'      => 'integer|required|bail',
                'color'         => 'string|required|bail',
                'country'       => 'string|required|bail',
                'for_back'      => 'boolean|required|bail',
                'for_front'     => 'boolean|required|bail',
                'height'        => 'integer|required|bail',
                'tubless'       => 'integer|required|bail',
                'speed'         => 'integer|required|bail',
                'tire_height'   => 'integer|required|bail',
                'width'         => 'integer|required|bail',
                'weight'        => 'integer|required|bail'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }
    
            // Find product
            $this->product = CoreProduct::findOrFail($product_id);
            $this->fill_product_model($request);

            // Upload new images - if exist
            if ($request->has('create_src')) {
                foreach ($request->file('create_src') as $src) {
                    $this->product->src = array_push($this->product->src, $request->file($src)->store('product/' . $request->name));
                }
            }

            // Delete uploaded old images
            if ($request->has('delete_src')) {
                // update source addresses in db
                $this->product->src = array_diff($this->product->src, $request->delete_src);

                // remove deleted files
                foreach ($request->delete_src as $deleted) {
                    Storage::delete($deleted);
                }
            }

            $this->product->save();

            return response()->json([
                'status' => true
            ], 200);

            
        } catch (\Throwable $th) {
                        
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        
        } catch (ModelNotFound $th) {

            return response()->json([
                'error'     => $th->getMessage()
            ], 404);

        }
    }

    /**
     ** Delete a product
     * 
     * @param int $product_id
     * @return Illuminate\Http\Response
     */
    public function delete_product(int $product_id, Request $request) : object
    {
        try {
    
            // Delete product
            $this->product = CoreProduct::where('id', $product_id)->select('name')->frist();
            CoreProduct::where('id', $product_id)->delete();

            // Delete images of product
            Storage::deleteDirectory('product/' . $this->product->name);

            return response()->json([
                'status' => true
            ], 200);

        } catch (\Throwable $th) {
                        
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);

        }
    }

    /**
     ** Fetch all products
     *
     * @param int $type
     * @param int $offset
     * @param int $limit
     * @param string $searched
     * @return Illuminate\Http\Response
     */
    public function fetch_products(int $type, int $limit, int $offset, $searched = null) : object
    {
        try {

            if(is_null($searched) && empty($searched)) {
                $this->product = CoreProduct::where('type', $type)
                                            ->select('name', 'design_name', 'brand')
                                            ->offset($offset)
                                            ->limit($limit)
                                            ->get();
            } else {
                $this->product = CoreProduct::where('type', $type)
                                            ->where(function($query) use ($searched) {
                                                $query->where('name', 'like', "%$searched%")
                                                    ->orWhere('design_name', 'like', "%$searched%")
                                                    ->orWhere('brand', 'like', "%$searched%");
                                            })
                                            ->select('name', 'design_name', 'brand')
                                            ->offset($offset)
                                            ->limit($limit)
                                            ->get();
            }

            
            
            return response()->json([
                'product' => $this->product,
                'count' => (is_null($searched) && empty($searched)) ? CoreProduct::count() : count($this->product)
            ], 200);
            
        } catch (\Throwable $th) {
            
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        
        }
    }

    /**
     ** Fetch product detail
     *
     * @param int $product_id
     * @return Illuminate\Http\Response
     */
    public function fetch_product_detail(int $product_id) : object
    {
        try {

            /**
             * 
             ** In future, you can use analysis for each product 
             *
             */

            return response()->json([
                'product' => CoreProduct::find($product_id)
            ], 200);
            
        } catch (\Throwable $th) {
            
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        
        }
    }

    /**
     ** Fill product model
     *  
     * @param collection $request
     * @return void
     */
    protected function fill_product_model($request)
    {
        $this->product->type        = $request->type;
        $this->product->name        = $request->name;
        $this->product->design_name = $request->design_name;
        $this->product->diameter    = $request->diameter;
        $this->product->color       = $request->color;
        $this->product->country     = $request->country;
        $this->product->for_back    = $request->for_back;
        $this->product->for_front   = $request->for_front;
        $this->product->height      = $request->height;
        $this->product->tubless     = $request->tubless;
        $this->product->speed       = $request->speed;
        $this->product->tire_height = $request->tire_height;
        $this->product->width       = $request->width;
        $this->product->weight      = $request->weight;
    }
}
