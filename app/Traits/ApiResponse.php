<?php

namespace App\Traits;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Log;

trait ApiResponse
{
    protected function successResponse(
        mixed $data = null,
        string $message = 'All done!',
        int $statusCode = 200,
        array $extra = [],
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (! empty($extra)) {
            $response['extra'] = $extra;
        }

        return response()->json($response, $statusCode);
    }

    protected function paginatedResponse(
        ResourceCollection $collection,
        string $message = 'Here\'s everything we found',
        int $statusCode = 200,
    ): JsonResponse {
        $paginator = $collection->response()->getData(true);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'resource' => $paginator['data'],
                'links' => $paginator['links'],
                'meta' => $paginator['meta'],
            ],
        ], $statusCode);
    }

    protected function errorResponse(
        array|string $errors = 'Hmm, something didn\'t work',
        string $message = 'We couldn\'t process that',
        int $statusCode = 400,
        array $extra = [],
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'errors' => is_array($errors) ? $errors : ['error' => $errors],
        ];

        if (! empty($extra)) {
            $response['extra'] = $extra;
        }

        return response()->json($response, $statusCode);
    }

    protected function handleException(
        Exception $e,
        array $context = [],
        string $userMessage = 'Our bad — something went wrong on our end',
        int $statusCode = 500,
    ): JsonResponse {
        Log::error($e->getMessage(), [
            ...$context,
            'exception' => $e::class,
        ]);

        $message = config('app.env') === 'production' ? 'Error' : $e->getMessage();

        return $this->errorResponse($userMessage, $message, $statusCode);
    }
}
