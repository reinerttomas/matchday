<?php

declare(strict_types=1);

use App\Models\User;

test('guests are redirected from the home page to the login page', function () {
    $response = $this->get(route('home'));
    $response->assertRedirect(route('login'));
});

test('authenticated users are redirected from the home page to the fixture list', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('home'));
    $response->assertRedirect('/fixtures');
});
