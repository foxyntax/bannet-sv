<?php

namespace App\Http\Controllers\User;

use App\Traits\Profile\Wallet;
use App\Http\Controllers\Controller;
use App\Traits\Profile\FetchProfile;
use App\Traits\Profile\UpdateProfile;

class Profile extends Controller
{
    use UpdateProfile, FetchProfile, Wallet;
    
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
