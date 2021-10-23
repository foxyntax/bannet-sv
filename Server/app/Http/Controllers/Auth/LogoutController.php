<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LogoutController extends Controller
{

    /**
     * Logout User
     * 
     * @param int $id
     */
    public function logout(int $user_id) : object
    {
        try {

            $user = User::find($user_id);
            $user->tokens()->delete();
            
            // Invalid User
            return response()->json([
                'success' => true
            ], 200);

            
        } catch (\Throwable $th) {
            
            return response()->json([
                'error'     => $th->getMessage()
            ], 500);
        
        }
    }
}
