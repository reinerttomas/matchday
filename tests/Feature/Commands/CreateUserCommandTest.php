<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\artisan;

test('user can be created from the console', function () {
    artisan('user:create')
        ->expectsQuestion('Name', 'Test Player')
        ->expectsQuestion('Email', 'Player@Example.com')
        ->expectsQuestion('Password', 'password')
        ->expectsQuestion('Confirm password', 'password')
        ->expectsOutputToContain('User player@example.com created.')
        ->assertSuccessful();

    $user = User::query()->sole();

    expect($user->name)->toBe('Test Player')
        ->and($user->email)->toBe('player@example.com')
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('user is not created when the input is invalid', function () {
    User::factory()->create(['email' => 'player@example.com']);

    artisan('user:create')
        ->expectsQuestion('Name', 'Test Player')
        ->expectsQuestion('Email', 'player@example.com')
        ->expectsQuestion('Password', 'password')
        ->expectsQuestion('Confirm password', 'different')
        ->expectsOutputToContain('The email has already been taken.')
        ->expectsOutputToContain('The password field confirmation does not match.')
        ->assertFailed();

    expect(User::query()->count())->toBe(1);
});
