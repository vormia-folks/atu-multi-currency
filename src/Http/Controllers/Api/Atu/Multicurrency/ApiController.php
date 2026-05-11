<?php

namespace Vormia\ATUMultiCurrency\Http\Controllers\Api\Atu\Multicurrency;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

abstract class ApiController extends Controller
{
    protected function success($data = null, string $message = '', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if (! is_null($data)) {
            $response['data'] = $data;
        }

        if (config('app.debug')) {
            $start = (defined('LARAVEL_START') ? constant('LARAVEL_START') : microtime(true));
            $response['debug'] = [
                'execution_time' => microtime(true) - $start,
                'memory_usage' => memory_get_usage(),
            ];
        }

        return response()->json($response, $statusCode);
    }

    protected function successPaginated(array $items, array $pagination, string $message = '', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $items,
            'pagination' => $pagination,
        ];

        if (config('app.debug')) {
            $start = (defined('LARAVEL_START') ? constant('LARAVEL_START') : microtime(true));
            $response['debug'] = [
                'execution_time' => microtime(true) - $start,
                'memory_usage' => memory_get_usage(),
            ];
        }

        return response()->json($response, $statusCode);
    }

    protected function error(string $message = '', int $statusCode = 400, array $errors = [], $data = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        if (! is_null($data)) {
            $response['data'] = $data;
        }

        if (config('app.debug')) {
            $start = (defined('LARAVEL_START') ? constant('LARAVEL_START') : microtime(true));
            $response['debug'] = [
                'execution_time' => microtime(true) - $start,
                'memory_usage' => memory_get_usage(),
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            ];
        }

        return response()->json($response, $statusCode);
    }

    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }
}
