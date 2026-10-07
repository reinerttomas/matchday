<?php

declare(strict_types=1);

use App\Models\User;

test('users can log in from the welcome page', function () {
    $user = User::factory()->create();

    visit('/')
        ->click('Log in')
        ->assertPathIs('/login')
        ->assertSee('Log in to your account')
        ->fill('email', $user->email)
        ->fill('password', 'password')
        ->assertSeeIn('@login-button', 'Login')
        ->click('@login-button')
        // The dashboard redirects to the fixture list.
        ->assertPathIs('/fixtures')
        ->assertSee('Rozpis zápasů')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticatedAs($user);
});
