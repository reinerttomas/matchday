<?php

declare(strict_types=1);

use App\Models\TeamPageEvent;

test('pruning deletes events older than 180 days', function () {
    $this->travelTo('2026-10-17 12:00:00');
    $kept = TeamPageEvent::factory()->create(['created_at' => now()->subDays(180)->addMinute()]);
    TeamPageEvent::factory()->create(['created_at' => now()->subDays(180)->subMinute()]);

    $this->artisan('model:prune')->assertSuccessful();

    expect(TeamPageEvent::query()->sole()->is($kept))->toBeTrue();
});
