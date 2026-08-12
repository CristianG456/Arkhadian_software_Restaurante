<?php

namespace App\Support\Http;

use Illuminate\Http\JsonResponse;

/**
 * The single shape every API endpoint responds with, success or failure,
 * so the frontend never has to branch on ad-hoc payload structures.
 */
final class ApiResponse
{
    public static function success(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    public static function error(string $code, string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}
