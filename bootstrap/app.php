<?php

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\NotFoundException;
use App\Exceptions\TransactionReversalException;
use App\Http\Middleware\JwtMiddleware;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt' => JwtMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (UnauthorizedException $e, \Illuminate\Http\Request $request): JsonResponse {
            return ApiResponse::error(
                $e->getMessage(),
                401,
            );
        });

        $exceptions->render(function (UnauthorizedHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(
                    'Não autorizado. Por favor, faça login novamente.',
                    401,
                    ['authentication' => 'Token não fornecido ou inválido.']
                );
            }
        });

        $exceptions->render(function (InsufficientBalanceException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error($e->getMessage(), 400, ['balance' => $e->getMessage()]);
            }
        });

        $exceptions->render(function (TransactionReversalException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error($e->getMessage(), 400, ['transaction' => $e->getMessage()]);
            }
        });

        $exceptions->render(function (NotFoundException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error($e->getMessage(), 404);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $modelName = class_basename($e->getModel());
                return ApiResponse::error("Recurso de {$modelName} não encontrado.", 404);
            }
        });

        $exceptions->render(function (ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error('Os dados fornecidos são inválidos.', 422, $e->errors());
            }
        });

        $exceptions->render(function (InvalidArgumentException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error($e->getMessage(), 400);
            }
        });



        // $exceptions->render(function (Throwable $e, \Illuminate\Http\Request $request) {
        //      if ($request->expectsJson() || $request->is('api/*')) {
        //         return ApiResponse::error($e->getMessage(), 500); // Para desenvolvimento/teste, útil ver a mensagem
        //      }
        // });

    })->create();
