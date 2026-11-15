<?php

namespace App\Http\Middleware;

use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets QA/staging fake the server clock by sending an absolute
 * timestamp in the X-Simulated-Time header. Never active in production.
 */
class SimulatedTime
{
    public const HEADER = 'X-Simulated-Time';

    public function handle(Request $request, Closure $next): Response
    {
        $simulated = $request->header(self::HEADER);

        if ($simulated === null || app()->environment('production')) {
            return $next($request);
        }

        try {
            Carbon::setTestNow(Carbon::parse($simulated));
        } catch (InvalidFormatException) {
            return response()->json([
                'message' => 'Invalid '.self::HEADER.' header, expected a parseable datetime.',
            ], 400);
        }

        $effective = Carbon::now()->toIso8601String();
        $response = $next($request);
        Carbon::setTestNow();

        return $response->header('X-App-Time-Now', $effective);
    }
}
