<?php

declare(strict_types=1);

use App\Models\Season;
use App\Models\TeamSeason;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('lists the seasons newest first with the current one marked and the number of team seasons in each', function () {
    $previous = Season::factory()->create(['name' => '2025/26']);
    $current = Season::factory()->current()->create(['name' => '2026/27']);
    $next = Season::factory()->create(['name' => '2027/28']);
    TeamSeason::factory()->count(3)->for($current)->create();
    TeamSeason::factory()->for($previous)->create();

    $response = $this->get('/seasons');

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('seasons/index')
        ->where('seasons', [
            ['id' => $next->id, 'name' => '2027/28', 'isCurrent' => false, 'teamSeasonsCount' => 0],
            ['id' => $current->id, 'name' => '2026/27', 'isCurrent' => true, 'teamSeasonsCount' => 3],
            ['id' => $previous->id, 'name' => '2025/26', 'isCurrent' => false, 'teamSeasonsCount' => 1],
        ]));
});

test('shows an empty list while there is no season', function () {
    $this->get('/seasons')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('seasons', [])
        ->etc());
});

test('redirects guests to the login page', function () {
    auth()->logout();

    $this->get('/seasons')->assertRedirect('/login');
});
