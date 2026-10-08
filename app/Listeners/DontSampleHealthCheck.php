<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Laravel\Nightwatch\Facades\Nightwatch;

/**
 * Uptime checks hit the health route every few seconds and would drown out real traffic in Nightwatch.
 * The framework registers that route itself, so the Sample::never() route middleware can't be attached to it.
 */
final readonly class DontSampleHealthCheck
{
    /**
     * Keep the current health check request out of Nightwatch.
     */
    public function handle(DiagnosingHealth $event): void
    {
        Nightwatch::dontSample();
    }
}
