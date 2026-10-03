<?php

declare(strict_types=1);

use App\Models\Team;
use Illuminate\Database\UniqueConstraintViolationException;

test('team slug must be unique', function () {
    Team::factory()->create(['slug' => 'kh-b']);

    expect(fn () => Team::factory()->create(['slug' => 'kh-b']))
        ->toThrow(UniqueConstraintViolationException::class);
});
