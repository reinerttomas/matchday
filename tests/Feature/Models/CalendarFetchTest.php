<?php

declare(strict_types=1);

use App\Models\CalendarFetch;

test('pruning deletes fetch counts of days more than 180 days ago in Prague', function () {
    // 23:30 UTC is already 18 October in Prague.
    $this->travelTo('2026-10-17 23:30:00');
    $kept = CalendarFetch::factory()->create(['date' => '2026-04-21']);
    CalendarFetch::factory()->create(['date' => '2026-04-20']);

    $this->artisan('model:prune')->assertSuccessful();

    expect(CalendarFetch::query()->sole()->is($kept))->toBeTrue();
});
