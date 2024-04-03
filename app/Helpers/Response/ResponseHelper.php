<?php

use Illuminate\Http\JsonResponse;

if (!function_exists('respondSuccess')) {
    function respondSuccess(string $message, array|object|null $data = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => empty($data) ? null : $data,
        ], $code);
    }
}

if (!function_exists('respondError')) {
    function respondError(string $message, array|object|null $data = null, int $code = 400): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => empty($data) ? null : $data,
        ], $code);
    }
}
