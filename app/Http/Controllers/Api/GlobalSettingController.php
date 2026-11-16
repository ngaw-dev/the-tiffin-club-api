<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GlobalSetting;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSettingController extends Controller
{
    use ApiResponse;

    /**
     * Public read-only store settings: delivery suburbs, the fallback
     * cutoff time, and the informational pickup window.
     */
    public function index(Request $request): JsonResponse
    {
        $settings = GlobalSetting::allSettings();

        return $this->successResponse([
            'delivery_suburbs' => json_decode($settings['delivery_suburbs'] ?? '[]', true),
            'order_cutoff_time' => self::hhmm($settings['order_cutoff_time'] ?? null),
            'pickup_start_time' => self::hhmm($settings['pickup_start_time'] ?? null),
            'pickup_end_time' => self::hhmm($settings['pickup_end_time'] ?? null),
            'store_phone' => $settings['store_phone'] ?? null,
            'store_phone_alternate' => $settings['store_phone_alternate'] ?? null,
            'store_email' => $settings['store_email'] ?? null,
            'pickup_address' => $settings['pickup_address'] ?? null,
            'pickup_map_url' => $settings['pickup_map_url'] ?? null,
        ], 'Store settings');
    }

    /**
     * Normalise a stored time value ("11:00" or "11:00:00") to H:i.
     */
    private static function hhmm(?string $value): ?string
    {
        return $value === null ? null : substr($value, 0, 5);
    }
}
