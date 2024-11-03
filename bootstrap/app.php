<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Tymon\JWTAuth\Providers\LumenServiceProvider;
use App\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'jwt.auth' => \Tymon\JWTAuth\Http\Middleware\Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (Throwable $exception) {

            $errorMessage = $exception->getMessage();
            $requestPath = request()->path();

            if (str_starts_with($requestPath, 'api/')) {
                // Return a JSON response if 'api/*' is found in the message
                return response()->json([
                    'message' => $errorMessage,
                    'code' => $exception->getCode() ?: 500,
                ], 500);
            } else {
                // Return a generic error message and code for non-API related errors
                return response()->view('errors.generic', [
                    'message' => $errorMessage,
                    'code' => $exception->getCode() ?: 500,
                ], 500);
            }
        });
    
    })->create();
