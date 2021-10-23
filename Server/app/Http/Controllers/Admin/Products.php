<?php

namespace App\Http\Controllers\Admin;

use App\Models\CoreProduct;
use App\Models\UserContract;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\CoreOption;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException as ModelNotFound;

/**
 ** Here I show the methods that I need to develop 
 * 
 // 1. create_product
 // 2. update_product
 // 3. delete_product
 // 4. fetch
 // 5. fetch_detail
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
                'diameter'      => 'required|bail',
                'color'         => 'string|required|bail',
                'country'       => 'string|required|bail',
                'for_back'      => 'boolean|required|bail',
                'for_front'     => 'boolean|required|bail',
                'height'        => 'integer|required|bail',
                'tubless'       => 'integer|required|bail',
                'speed'         => 'integer|required|bail',
                'tire_height'   => 'integer|required|bail',
                'width'         => 'integer|required|bail',
                'weight'        => 'integer|required|bail',
                'src'           => 'mimes:jpg,png|bail'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }

            // new product
            $this->product = new CoreProduct;
            $this->fill_product_model($request);
            $this->product->features['src'] = [];
            
            // Upload src of pictures
            if(is_array($request->file('src'))) {
                foreach ($request->file('src') as $src) {
                    array_push($this->product->features['src'], $src->store('product/' . $request->name));
                }
            } else {
                array_push($this->product->features['src'], $request->file('src')->store('product/' . $request->name));
            }

            $this->product->save();

            return response()->json([
                'status'    => true
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
     * @param Illuminate\Http\Request new_src as feature.src
     * @param Illuminate\Http\Request trash_src
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
                'weight'        => 'integer|required|bail',
                'src'           => 'mimes:jpg,png|bail'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }
    
            // Find product
            $this->product = CoreProduct::findOrFail($product_id);
            $this->fill_product_model($request);


            // Delete uploaded trash images
            if ($request->has('trash_src')) {
                // remove deleted files and their address
                foreach ($request->trash_src as $index) {
                    unset($this->product->features['src'][$index]);
                    Storage::delete($this->product->features['src'][$index]);
                }
            }

            // Upload new images - if exist
            if ($request->has('new_src')) {
                if(is_array($request->file('new_src'))) {
                    foreach ($request->file('new_src') as $src) {
                        array_push($this->product->features['src'], $src->store('product/' . $request->name));
                    }
                } else {
                    array_push($this->product->features['src'], $request->file('new_src')->store('product/' . $request->name));
                }
            }

            $this->product->save();

            return response()->json([
                'product'=> $this->product,
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

            // Is it used before for any contracts?
            $count = UserContract::where('product_id',$product_id)
                                 ->where('status', '<', 3)
                                 ->count();
    
            if($count == 0) {
                // Delete product
                CoreProduct::where('id', $product_id)->delete();

                // We don't have to delete, we use soft delete instead
                //  // Delete images of produc
                // // $this->product = CoreProduct::where('id', $product_id)->select('features')->first();
                // // Storage::deleteDirectory('product/' . $this->product->features['name']);

                return response()->json([
                    'status' => true
                ], 200);
            }

            return response()->json([
                'status' => false
            ], 400);

        } catch (\Throwable $th) {
                        
            return response()->json([
                'error' => $th->getMessage()
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
    public function fetch(int $type, int $offset, int $limit, $searched = null) : object
    {
        try {

            if(is_null($searched) && empty($searched)) {
                $this->product = CoreProduct::where('type', $type)
                                            ->select('id', 'features');
            } else {
                $this->product = CoreProduct::where('type', $type)
                                            ->where(function($query) use ($searched) {
                                                $query->where('features->name', 'like', "%$searched%")
                                                    ->orWhere('features->design_name', 'like', "%$searched%")
                                                    ->orWhere('features->brand', 'like', "%$searched%");
                                            })
                                            ->select('id', 'features');
            }
            
            return response()->json([
                'count'     => $this->product->count(),
                'product'   => $this->product->offset($offset)->limit($limit)->get()
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
    public function fetch_detail(int $product_id) : object
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
        $this->product->features    = [
            'name'        => $request->name,
            'design_name' => $request->design_name,
            'diameter'    => $request->diameter,
            'color'       => $request->color,
            'country'     => $request->country,
            'for_back'    => $request->for_back,
            'for_front'   => $request->for_front,
            'height'      => $request->height,
            'tubless'     => $request->tubless,
            'speed'       => $request->speed,
            'tire_height' => $request->tire_height,
            'width'       => $request->width,
            'weight'      => $request->weight
        ];
        
    }
}
