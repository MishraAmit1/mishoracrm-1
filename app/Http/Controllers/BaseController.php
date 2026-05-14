<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseController extends Controller
{
    // API success response
    protected function apiSuccess($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    // API error response
    protected function apiError(string $message = 'Error', int $code = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }

    // Paginated API response
    protected function apiPaginated($paginator, $resource): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $resource::collection($paginator->items()),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    // Web ya API — automatically detect karo
    protected function respond(Request $request, $view, array $data = [], $apiData = null, int $apiCode = 200)
    {
        if ($request->expectsJson()) {
            return $this->apiSuccess($apiData ?? $data, 'Success', $apiCode);
        }
        return view($view, $data);
    }
}