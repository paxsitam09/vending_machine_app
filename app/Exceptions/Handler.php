<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        // Add exception types that you don't want to report
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register()
    {
        // Rendering Custom Responses
        $this->renderable(function (NotFoundHttpException $e) {
            return response()->json([
                'message' => 'Record not found.'
            ], 404);
        });

        $this->renderable(function (Throwable $exception) {
            if ($exception instanceof \ErrorException && str_contains($exception->getMessage(), 'Attempt to read property "role" on null')) {
                return response()->view('errors.custom', ['message' => 'The user role is not available. Please try again later.'], 500);
            }
        });

        // Custom reporting logic
        $this->reportable(function (Throwable $e) {
            info('Custom Exception: ' . $e->getMessage());
        });
    }
}
