<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

test('prunes old subscription activity daily', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn (Event $event): bool => str_ends_with((string) $event->command, 'model:prune'));

    expect($events)->toHaveCount(1)
        ->and($events->sole()->expression)->toBe('0 0 * * *');
});
