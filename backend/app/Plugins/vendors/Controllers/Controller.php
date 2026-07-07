<?php

namespace App\Plugins\Vendors\Controllers;

use App\Core\Exceptions\GameException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    use AuthorizesRequests;

    protected function successResponse(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        $response = ['success' => true, 'message' => $message];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    protected function errorResponse(string $message = 'Error', int $status = 400, mixed $errors = null): JsonResponse
    {
        $response = ['success' => false, 'message' => $message];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    protected function handleGameException(\Throwable $e, int $gameErrorStatus = 422): JsonResponse
    {
        if ($e instanceof GameException) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], $gameErrorStatus);
        }

        Log::error(static::class . ': ' . $e->getMessage(), ['exception' => $e]);

        return response()->json(['success' => false, 'error' => 'An unexpected error occurred. Please try again.'], 500);
    }

    protected function paginatedResponse(mixed $paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
