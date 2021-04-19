<?php
namespace App\Http\Controllers\Admin;

use App\Models\UserWallet;
use Illuminate\Http\Request;
use App\Models\CoreMembership;
use App\Http\Controllers\Controller;
use Kavenegar\Exceptions\ApiException;
use Illuminate\Support\Facades\Storage;
use Kavenegar\Exceptions\HttpException;
use Illuminate\Support\Facades\Validator;

/**
 ** Here I show the methods that I need to develop 
 *
 // 1. Ffetch_memberships
 // 2. update_membership
 // 3. delete membership
 * 
 */


class Membership extends Controller
{

    /**
     * @var int $membership
     */
    protected $membership;

    /**
     ** Fetch all memberships with pagination
     * 
     * @param int $type
     * @param int $offset
     * @param int $limit
     * @param string $searched
     * @return Illuminate\Http\Response
     */
    public function fetch_memberships(int $status, int $limit, int $offset, $searched = null) : object
    {
        try {

            if (is_null($searched) && empty($searched)) {
                $this->membership = CoreMembership::where('status', $status)
                                                  ->offset($offset)
                                                  ->limit($limit)
                                                  ->get();
            } else {
                $this->membership = CoreMembership::where('status', $status)
                                                  ->where(function($query) use ($searched) {
                                                      $query->where('title', 'like', "%$searched%")
                                                            ->orWhere('days', 'like', "%$searched%");
                                                  })
                                                  ->offset($offset)
                                                  ->limit($limit)
                                                  ->get();
            }
            
            return response()->json([
                'membership'=> $this->membership,
                'count'     => (is_null($searched) && empty($searched)) ? CoreMembership::count() : count($this->membership)
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /** 
     ** Update membership detail
     * 
     * @param in $membership_id
     * @return Object
    */
    public function update_membership(int $membership_id, Request $request) : object
    {
        try {

            $validator = Validator::make($request->all(), [
                'title' => 'string|bail',
                'status'=> 'integer|bail',
                'cost'  => 'integer|bail',
                'days'  => 'integer|bail'
            ]);
    
            if($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()
                ], 500);
            }
            
            if ($request->has('title'))  $updated['title']          = $request->title;
            if ($request->has('status'))  $updated['status']        = ($request->status) ? 1 : 0;
            if ($request->has('cost'))   $updated['meta->cost']     = $request->cost;
            if ($request->has('days'))   $updated['days']           = $request->days;

            CoreMembership::where('id', $membership_id)->update($updated);

            return response()->json([
                'membership' => $this->membership
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /** 
     ** Delete memebership | or deactive it
     * 
     * @param in $membership_id
     * @return Object
    */
    public function delete_memebrship(int $membership_id) : object
    {
        try {
            
            // Check is it possible to delete
            $active_membership = UserWallet::firstWhere('membership_id', $membership_id);
            if($active_membership) {
                CoreMembership::where('id', $membership_id)->delete();
                return response()->json([
                    'status'  => true
                ], 200);
            } else {
                // we resort to deactive it, because users still use it.
                CoreMembership::where('id', $membership_id)->update(['status' => 0]);
                return response()->json([
                    'status'  => false
                ], 200);
            }
            
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }
}