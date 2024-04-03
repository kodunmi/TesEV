<?php

use Illuminate\Http\Exceptions\HttpResponseException;
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


if (!function_exists('validationError')) {
    function respondValidationError(string $message, $errors = null)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'status_code' => 422,
                'message' => $message,
                'errors' => $errors,
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
