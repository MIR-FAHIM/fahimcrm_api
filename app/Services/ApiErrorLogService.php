<?php

namespace App\Services;

use App\Models\ApiErrorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ApiErrorLogService
{
    /**
     * Log a Throwable/Exception to the api_error_logs database table.
     *
     * @param Throwable $exception
     * @param Request|null $request
     * @param array $extraData
     * @return ApiErrorLog|null
     */
    public static function logException(Throwable $exception, ?Request $request = null, array $extraData = []): ?ApiErrorLog
    {
        try {
            $request = $request ?? request();

            $statusCode = 500;
            if (method_exists($exception, 'getStatusCode')) {
                $statusCode = $exception->getStatusCode();
            } elseif (method_exists($exception, 'getCode') && is_int($exception->getCode()) && $exception->getCode() >= 400 && $exception->getCode() < 600) {
                $statusCode = $exception->getCode();
            }

            $userId = Auth::id()
                ?? ($request ? ($request->user_id ?? $request->user()?->id) : null)
                ?? ($extraData['user_id'] ?? null);

            $requestData = $request
                ? $request->except(['password', 'password_confirmation', 'token', 'secret', 'auth_token'])
                : [];

            if (isset($extraData['request_data']) && is_array($extraData['request_data'])) {
                $requestData = array_merge($requestData, $extraData['request_data']);
            }

            return ApiErrorLog::create([
                'user_id' => $userId,
                'method' => strtoupper($extraData['method'] ?? ($request ? ($request->method() ?? 'GET') : 'GET')),
                'url' => $extraData['url'] ?? ($request ? $request->fullUrl() : ''),
                'status_code' => $extraData['status_code'] ?? $statusCode,
                'error_message' => $extraData['error_message'] ?? ($exception->getMessage() ?: get_class($exception)),
                'exception_class' => get_class($exception),
                'stack_trace' => $exception->getTraceAsString(),
                'request_data' => $requestData,
                'ip_address' => $request ? $request->ip() : ($extraData['ip_address'] ?? null),
                'user_agent' => $request ? $request->header('User-Agent') : ($extraData['user_agent'] ?? null),
                'is_resolved' => false,
            ]);
        } catch (Throwable $e) {
            // Silently swallow errors during logging to prevent error loops
            return null;
        }
    }

    /**
     * Log a custom error message or API failure directly from controllers or functions.
     *
     * @param string $message
     * @param int $statusCode
     * @param Throwable|null $exception
     * @param Request|null $request
     * @param array $extraData
     * @return ApiErrorLog|null
     */
    public static function logError(
        string $message,
        int $statusCode = 500,
        ?Throwable $exception = null,
        ?Request $request = null,
        array $extraData = []
    ): ?ApiErrorLog {
        try {
            $request = $request ?? request();

            $userId = Auth::id()
                ?? ($request ? ($request->user_id ?? $request->user()?->id) : null)
                ?? ($extraData['user_id'] ?? null);

            $requestData = $request
                ? $request->except(['password', 'password_confirmation', 'token', 'secret', 'auth_token'])
                : [];

            if (isset($extraData['request_data']) && is_array($extraData['request_data'])) {
                $requestData = array_merge($requestData, $extraData['request_data']);
            }

            return ApiErrorLog::create([
                'user_id' => $userId,
                'method' => strtoupper($extraData['method'] ?? ($request ? ($request->method() ?? 'GET') : 'GET')),
                'url' => $extraData['url'] ?? ($request ? $request->fullUrl() : ''),
                'status_code' => $statusCode,
                'error_message' => $message,
                'exception_class' => $exception ? get_class($exception) : ($extraData['exception_class'] ?? null),
                'stack_trace' => $exception ? $exception->getTraceAsString() : ($extraData['stack_trace'] ?? null),
                'request_data' => $requestData,
                'ip_address' => $request ? $request->ip() : ($extraData['ip_address'] ?? null),
                'user_agent' => $request ? $request->header('User-Agent') : ($extraData['user_agent'] ?? null),
                'is_resolved' => false,
            ]);
        } catch (Throwable $e) {
            return null;
        }
    }
}
