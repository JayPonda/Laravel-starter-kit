<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function ($request, $e) {
            if ($request->is('api/*')) {
                return true;
            }

            return $request->expectsJson();
        });

        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                $status = 500;

                if ($e instanceof HttpExceptionInterface) {
                    $status = (int) $e->getStatusCode();
                } elseif ($e instanceof ValidationException) {
                    $status = 422;
                } elseif (is_numeric($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600) {
                    // Some custom exceptions might set a valid HTTP code via getCode()
                    $status = (int) $e->getCode();
                }

                $response = [
                    'message' => $e->getMessage() ?: 'An unexpected error occurred.',
                    'exception' => get_class($e),
                    'status' => $status,
                ];

                if ($e instanceof ValidationException) {
                    $response['errors'] = $e->errors();
                }

                // In debug mode, you might want to see the stack trace even in JSON
                if (config('app.debug')) {
                    $response['trace'] = $e->getTrace();
                }

                return response()->json($response, $status);
            }
        });
    })->create();
