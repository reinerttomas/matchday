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
        ->click('@login-button')
        ->assertPathIs('/dashboard')
        ->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticatedAs($user);
});
