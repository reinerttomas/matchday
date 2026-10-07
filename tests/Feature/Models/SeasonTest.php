<?php

declare(strict_types=1);

use App\Models\Season;
use Illuminate\Database\UniqueConstraintViolationException;

test('season name must be unique', function () {
    Season::factory()->create(['name' => '2026/2027']);

    expect(fn () => Season::factory()->create(['name' => '2026/2027']))
        ->toThrow(UniqueConstraintViolationException::class);
});
