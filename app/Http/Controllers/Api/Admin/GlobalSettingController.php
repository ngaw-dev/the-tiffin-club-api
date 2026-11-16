<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlobalSetting;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSettingController extends Controller
{
    use ApiResponse;

    /**
     * Same shape as the public GET /v1/settings, but with every
     * setting writable.
     */
    public function index(): JsonResponse
    {
        $settings = GlobalSetting::allSettings();

        return $this->successResponse([
            'delivery_suburbs' => json_decode($settings['delivery_suburbs'] ?? '[]', true),
            'order_cutoff_time' => $settings['order_cutoff_time'] ?? null,
            'pickup_start_time' => $settings['pickup_start_time'] ?? null,
            'pickup_end_time' => $settings['pickup_end_time'] ?? null,
            'store_phone' => $settings['store_phone'] ?? null,
            'store_phone_alternate' => $settings['store_phone_alternate'] ?? null,
            'store_email' => $settings['store_email'] ?? null,
            'pickup_address' => $settings['pickup_address'] ?? null,
            'pickup_map_url' => $settings['pickup_map_url'] ?? null,
        ], 'Store settings');
    }

    public function update(Request $request): JsonResponse
    {
        $rules = collect(GlobalSetting::KEYS)
            ->map(fn (): array => ['sometimes'])
            ->merge([
                'delivery_suburbs' => ['sometimes', 'array'],
                'delivery_suburbs.*' => ['string'],
                'order_cutoff_time' => ['sometimes', 'nullable', 'date_format:H:i'],
                'pickup_start_time' => ['sometimes', 'nullable', 'date_format:H:i'],
                'pickup_end_time' => ['sometimes', 'nullable', 'date_format:H:i'],
                'store_phone' => ['sometimes', 'nullable', 'string'],
                'store_phone_alternate' => ['sometimes', 'nullable', 'string'],
                'store_email' => ['sometimes', 'nullable', 'email'],
                'pickup_address' => ['sometimes', 'nullable', 'string'],
                'pickup_map_url' => ['sometimes', 'nullable', 'url'],
            ])
            ->all();

        $validated = $request->validate($rules);

        foreach ($validated as $key => $value) {
            if (array_key_exists($key, GlobalSetting::KEYS)) {
                GlobalSetting::set($key, $value);
            }
        }

        return $this->index();
    }
}
