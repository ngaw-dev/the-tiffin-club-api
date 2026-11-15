<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GlobalSetting;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use ApiResponse;

    /**
     * Public read-only store settings: delivery suburbs, the fallback
     * cutoff time, and the informational pickup window.
     */
    public function index(Request $request): JsonResponse
    {
        $settings = GlobalSetting::current();

        return $this->successResponse([
            'delivery_suburbs' => $settings->delivery_suburbs,
            'order_cutoff_time' => $settings->order_cutoff_time?->format('H:i'),
            'pickup_start_time' => $settings->pickup_start_time?->format('H:i'),
            'pickup_end_time' => $settings->pickup_end_time?->format('H:i'),
            'store_phone' => $settings->store_phone,
            'store_phone_alternate' => $settings->store_phone_alternate,
            'store_email' => $settings->store_email,
            'pickup_address' => $settings->pickup_address,
            'pickup_map_url' => $settings->pickup_map_url,
        ], 'Store settings');
    }
}
