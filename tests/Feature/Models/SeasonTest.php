<?php

declare(strict_types=1);

use App\Models\Season;
use Illuminate\Database\UniqueConstraintViolationException;

test('season name must be unique', function () {
    Season::factory()->create(['name' => '2026/27']);

    expect(fn () => Season::factory()->create(['name' => '2026/27']))
        ->toThrow(UniqueConstraintViolationException::class);
});
