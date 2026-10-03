<?php

declare(strict_types=1);

use App\Models\Venue;
use Illuminate\Database\UniqueConstraintViolationException;

test('federation arena id must be unique', function () {
    Venue::factory()->create(['external_id' => 1204]);

    expect(fn () => Venue::factory()->create(['external_id' => 1204]))
        ->toThrow(UniqueConstraintViolationException::class);
});
