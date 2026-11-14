<?php

namespace App\Http\Resources;

use App\Services\OrderingWindow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderingWindow */
class OrderingWindowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'state' => $this->state(),
            'open' => $this->isOpen(),
            'closing_soon' => $this->closingSoon(),
            'opens_at' => $this->opensAt->format('H:i'),
            'closes_at' => $this->closesAt->format('H:i'),
        ];
    }
}
