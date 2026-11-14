<?php

namespace App\Services;

use App\Models\Menu;
use Carbon\CarbonInterface;

/**
 * The server-authoritative ordering window for a given menu.
 * All instants are Pacific/Auckland; the client clock is never used.
 */
final class OrderingWindow
{
    public function __construct(
        public readonly CarbonInterface $opensAt,
        public readonly CarbonInterface $closesAt,
        public readonly CarbonInterface $now,
    ) {}

    public function isOpen(): bool
    {
        return $this->now->between($this->opensAt, $this->closesAt);
    }

    public function closingSoon(): bool
    {
        return $this->isOpen()
            && $this->now->greaterThan($this->closesAt->copy()->subHour());
    }

    /**
     * Machine-readable state for API consumers: open | opening_later |
     * closing_soon | closed.
     */
    public function state(): string
    {
        if (! $this->isOpen()) {
            return $this->now->isBefore($this->opensAt) ? 'opening_later' : 'closed';
        }

        return $this->closingSoon() ? 'closing_soon' : 'open';
    }
}
