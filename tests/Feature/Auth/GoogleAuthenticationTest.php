<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

function fakeGoogleUser(string $id, string $email, bool $emailVerified = true): void
{
    Socialite::fake('google', (new SocialiteUser)
        ->setRaw(['sub' => $id, 'email' => $email, 'email_verified' => $emailVerified])
        ->map(['id' => $id, 'name' => 'Google User', 'email' => $email]));
}

test('google redirect sends the user to google', function () {
    Socialite::fake('google');

    $response = $this->get(route('auth.google.redirect'));

    $response->assertRedirectContains('google');
});

test('users linked to a google account can log in', function () {
    $user = User::factory()->withGoogle()->create();
    fakeGoogleUser($user->google_id, 'changed@example.com');

    $response = $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('existing users are linked by verified google email on first login', function () {
    $user = User::factory()->unverified()->create(['email' => 'player@example.com']);
    fakeGoogleUser('1234567890', 'Player@Example.com');

    $response = $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
    expect($user->refresh())
        ->google_id->toBe('1234567890')
        ->email_verified_at->not->toBeNull();
});

test('users are not linked when google has not verified the email', function () {
    $user = User::factory()->create(['email' => 'player@example.com']);
    fakeGoogleUser('1234567890', 'player@example.com', emailVerified: false);

    $response = $this->get(route('auth.google.callback'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    expect($user->refresh()->google_id)->toBeNull();
});

test('users already linked to another google account are not relinked by email', function () {
    $user = User::factory()->withGoogle()->create(['email' => 'player@example.com']);
    $originalGoogleId = $user->google_id;
    fakeGoogleUser('1234567890', 'player@example.com');

    $response = $this->get(route('auth.google.callback'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    expect($user->refresh()->google_id)->toBe($originalGoogleId);
});

test('google accounts without a matching user cannot log in or register', function () {
    fakeGoogleUser('1234567890', 'stranger@example.com');

    $response = $this->get(route('auth.google.callback'));

    $this->assertGuest();
    $response->assertRedirect(route('login'))
        ->assertInertiaFlash('toast.message', 'No account is associated with this Google account.');
    $this->assertDatabaseMissing('users', ['email' => 'stranger@example.com']);
});

test('cancelled google sign in redirects back to login', function () {
    Socialite::fake('google');

    $response = $this->get(route('auth.google.callback', ['error' => 'access_denied']));

    $this->assertGuest();
    $response->assertRedirect(route('login'))
        ->assertInertiaFlash('toast.message', 'Google sign-in was cancelled.');
});

test('invalid oauth state redirects back to login', function () {
    Socialite::fake('google', fn () => throw new InvalidStateException);

    $response = $this->get(route('auth.google.callback'));

    $this->assertGuest();
    $response->assertRedirect(route('login'))
        ->assertInertiaFlash('toast.message', 'Google sign-in failed. Please try again.');
});

test('authenticated users cannot start google sign in', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('auth.google.redirect'));

    $response->assertRedirect(route('dashboard', absolute: false));
});
