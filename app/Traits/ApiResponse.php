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

        return $this->errorResponse(
            errors: $userMessage,
            message: $this->getStatusMessage($statusCode),
            statusCode: $statusCode,
        );
    }

    protected function getStatusMessage(int $statusCode): string
    {
        $statusMessages = [
            200 => 'All good, thanks!',
            201 => 'All done — your new thing is ready to go',
            204 => 'Got it — nothing to send back, but we\'re good',
            400 => 'Sorry, we couldn\'t make sense of that request',
            401 => 'You\'ll need to log in before you can do that',
            403 => 'Ah, you don\'t have permission for that one',
            404 => 'We couldn\'t find what you\'re looking for',
            405 => 'That method isn\'t going to work on this endpoint',
            409 => 'Looks like there\'s a conflict — try refreshing',
            422 => 'Mind checking your input and trying again?',
            429 => 'Easy there — you\'re going a bit too fast',
            500 => 'Our bad — something broke on our end',
            503 => 'We\'ll be right back, hang tight',
        ];

        return $statusMessages[$statusCode] ?? 'Unknown Error';
    }
}
