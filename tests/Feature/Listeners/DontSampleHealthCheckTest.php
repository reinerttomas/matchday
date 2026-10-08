<?php

declare(strict_types=1);

use Laravel\Nightwatch\Facades\Nightwatch;

test('health check requests are not sampled', function () {
    $this->get('/up')->assertOk();

    expect(Nightwatch::sampling())->toBeFalse();
});

test('other requests are still sampled', function () {
    $this->get(route('home'))->assertRedirect(route('login'));

    expect(Nightwatch::sampling())->toBeTrue();
});
