<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class EnsureUserIsValidated
{
    /**
     * URL parameters
     * 
     * @var string $params
     */
    protected $params;

    /**
     * User id
     * 
     * @var string $params
     */
    protected $user_id;

    /**
     * URL parameters
     * 
     * @var string $params
     */
    protected $request;



    /**
     ** Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // The request is not performs for specify user, so let it go
        $this->params = $request->route()->parameters();
        if(!$request->has('user_id') && !isset($this->params['user_id'])) {
            return $next($request);
        }

        $this->request = $request;
        if($this->is_user_validated() || ($this->is_local_req() && $this->is_admin())) {
            return $next($request);
        }

        return response()->json([
            'status' => false,
            'desc'   => 'This is a custom request! Big mistake ...',
        ], 401);
    }

    /**
     ** Request IP Address
     * 
     * @return bool
     */
    protected function is_user_validated() : bool
    {
        // Get id and token from authorization header
        [$id, $token] = explode('|', $this->request->bearerToken(), 2);
        $access_user = PersonalAccessToken::where('id', $id)->select('tokenable_id')->first();

        // Get User instance
        $this->user_id = ($this->request->has('user_id'))
                                                            ? $this->request->user_id
                                                            : $this->params['user_id'];
                    
        // Is validated user with this authorization header
        return ($access_user->tokenable_id == $this->user_id);
    }

    /**
     ** Check localization of IP request
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function is_local_req() : bool
    {
        return ($this->request->ip() === $_SERVER['SERVER_ADDR']);
    }

    /**
     ** Check uset type
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function is_admin() : bool
    {
        $user = User::where('id', $this->user_id)->select('is_admin')->first();
        return ($user->is_admin === 1);
    }
}
