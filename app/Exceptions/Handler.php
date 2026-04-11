<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception): JsonResponse|\Illuminate\Http\Response
    {
        // Check if this is an API request
        if ($this->isApiRequest($request)) {
            return $this->handleApiException($exception);
        }

        return parent::render($request, $exception);
    }

    /**
     * Check if the request is expecting a JSON response
     */
    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->wantsJson();
    }

    /**
     * Handle API exceptions and return JSON response
     */
    private function handleApiException(Throwable $exception): JsonResponse
    {
        // Validation exception
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $exception->validator->errors()->toArray(),
            ], 422);
        }

        // Not found exception
        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found',
            ], 404);
        }

        // Authentication exception (401)
        if ($this->isAuthenticationException($exception)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Authorization exception (403)
        if ($this->isAuthorizationException($exception)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        // HTTP exceptions
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage() ?: $this->getHttpExceptionMessage($statusCode);

            return response()->json([
                'success' => false,
                'message' => $message,
            ], $statusCode);
        }

        // Production vs Development error handling
        $statusCode = 500;
        $message = 'Internal server error';

        if (config('app.debug')) {
            $message = $exception->getMessage() ?: 'An error occurred';
        }

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Check if exception is an authentication exception
     */
    private function isAuthenticationException(Throwable $exception): bool
    {
        $message = $exception->getMessage();
        return str_contains(strtolower($message), 'unauthenticated') ||
               str_contains(strtolower($message), 'unauthorized access') ||
               get_class($exception) === 'Illuminate\Auth\AuthenticationException';
    }

    /**
     * Check if exception is an authorization exception
     */
    private function isAuthorizationException(Throwable $exception): bool
    {
        return get_class($exception) === 'Illuminate\Auth\Access\AuthorizationException' ||
               str_contains(strtolower($exception->getMessage()), 'forbidden') ||
               str_contains(strtolower($exception->getMessage()), 'not authorized');
    }

    /**
     * Get HTTP exception message by status code
     */
    private function getHttpExceptionMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            405 => 'Method not allowed',
            409 => 'Conflict',
            422 => 'Unprocessable entity',
            429 => 'Too many requests',
            500 => 'Internal server error',
            503 => 'Service unavailable',
            default => 'An error occurred',
        };
    }
}
