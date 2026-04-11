<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;

class ExampleController extends ApiController
{
    /**
     * Example API controller extending ApiController
     * 
     * All resource controllers should extend ApiController and use
     * the ApiResponse trait methods to maintain consistent JSON responses.
     * 
     * Response Methods Available:
     * - success($data, $message, $statusCode)     // 200
     * - created($data, $message)                  // 201
     * - paginated($paginated, $message)           // 200 with pagination meta
     * - noContent($message)                       // 204
     * - error($message, $statusCode, $errors)     // Custom error
     * - validationError($errors, $message)        // 422
     * - notFound($message)                        // 404
     * - unauthorized($message)                    // 401
     * - forbidden($message)                       // 403
     * - conflict($message, $errors)               // 409
     * - serverError($message)                     // 500
     */
}
