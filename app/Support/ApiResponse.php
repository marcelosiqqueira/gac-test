<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(array $data, int $statusCode = 200, string $message = null)
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? 'Operação realizada com sucesso.',
            'data' => $data,
        ], $statusCode);
    }

    public static function error(string $message = null, int $statusCode = 400, array $errors = [])
    {
        return response()->json([
            'success' => false,
            'message' => $message ?? 'Ocorreu um erro inesperado.',
            'errors' => $errors,
        ], $statusCode);
    }
}
