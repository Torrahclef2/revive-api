<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

class ApiController extends Controller
{
    use AuthorizesRequests, ValidatesRequests, ApiResponse;

    /**
     * Base API Controller extended by all API controllers
     * 
     * All API controllers in this application should extend ApiController
     * to inherit consistent JSON response handling and other utilities.
     */
}
