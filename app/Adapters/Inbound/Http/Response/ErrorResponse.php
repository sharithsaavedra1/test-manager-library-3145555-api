<?php

namespace App\Adapters\Inbound\Http\Response;

use Illuminate\Http\JsonResponse;

final class ErrorResponse
{
    public static function create(string $code, string $message, int $statusCode, array $details = []): JsonResponse
    {
        return new JsonResponse([
            'code' => $code,
            'message' => $message,
            'details' => $details,
        ], $statusCode, [
            'Content-Type' => 'application/json',
        ]);
    }
}
