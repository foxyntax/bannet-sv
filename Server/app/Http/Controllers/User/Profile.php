<?php

namespace App\Http\Controllers\User;

use App\Traits\Profile\FetchProfile;
use App\Traits\Profile\UpdateProfile;
use App\Http\Controllers\Controller;

class Profile extends Controller
{
    use UpdateProfile, FetchProfile;
    
    /**
     * @var Illuminate\Http\Response $response
     */
    protected $response;

    /**
     * @var int $user
     */
    protected $user;

    /**
     * @var Illuminate\Support\Facades\Validator $validator
     */
    protected $validator;
}
