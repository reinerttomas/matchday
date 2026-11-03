<?php

declare(strict_types=1);

use App\Models\NotificationSubscription;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get('/settings/notifications')->assertRedirect('/login');
});

test('lists the current season\'s teams by name with the administrator\'s notification subscriptions', function () {
    $user = User::factory()->create();
    $current = Season::factory()->current()->create();
    $previous = Season::factory()->create();
    $kutnaHoraB = Team::factory()->create(['name' => 'FBC Kutná Hora B']);
    $kutnaHoraA = Team::factory()->create(['name' => 'FBC Kutná Hora A']);
    $carriedOver = Team::factory()->create(['name' => 'FBC Kolín']);
    $retired = Team::factory()->create(['name' => 'FBC Čáslav']);
    TeamSeason::factory()->for($kutnaHoraB)->for($current)->create(['competition_name' => 'Divize mužů']);
    TeamSeason::factory()->for($kutnaHoraA)->for($current)->notImported()->create();
    TeamSeason::factory()->for($carriedOver)->for($previous)->create(['competition_name' => 'Liga juniorů']);
    TeamSeason::factory()->for($carriedOver)->for($current)->create(['competition_name' => 'Liga dorostu']);
    TeamSeason::factory()->for($retired)->for($previous)->create();
    NotificationSubscription::factory()->for($user)->for($kutnaHoraB)->create();
    NotificationSubscription::factory()->for($user)->for($kutnaHoraA)->unsubscribed()->create();
    NotificationSubscription::factory()->for($user)->for($retired)->create();
    NotificationSubscription::factory()->for(User::factory())->for($carriedOver)->create();

    $this->actingAs($user)->get('/settings/notifications')->assertInertia(fn (Assert $page) => $page
        ->component('settings/notifications')
        ->where('notifyImportFailures', true)
        ->where('teams', [
            ['id' => $carriedOver->id, 'name' => 'FBC Kolín', 'competition' => 'Liga dorostu', 'subscribed' => false],
            ['id' => $kutnaHoraA->id, 'name' => 'FBC Kutná Hora A', 'competition' => null, 'subscribed' => false],
            ['id' => $kutnaHoraB->id, 'name' => 'FBC Kutná Hora B', 'competition' => 'Divize mužů', 'subscribed' => true],
        ]));
});

test('shows that the administrator has turned import failure notifications off', function () {
    $user = User::factory()->create(['notify_import_failures' => false]);

    $this->actingAs($user)->get('/settings/notifications')->assertInertia(fn (Assert $page) => $page
        ->where('notifyImportFailures', false)
        ->etc());
});

test('unsubscribes the administrator from a team\'s change summaries without touching another administrator', function () {
    $this->freezeSecond();
    $team = Team::factory()->create(['name' => 'FBC Kutná Hora B']);
    $user = User::factory()->subscribedTo($team)->create();
    $colleague = User::factory()->subscribedTo($team)->create();

    $response = $this->actingAs($user)
        ->from('/settings/notifications')
        ->put("/settings/notifications/teams/{$team->id}", ['subscribed' => false]);

    $response->assertRedirect('/settings/notifications')
        ->assertInertiaFlash('toast.message', 'Změny týmu FBC Kutná Hora B vám už e-mailem chodit nebudou.');
    expect($user->notificationSubscriptions()->sole()->unsubscribed_at)->toEqual(now())
        ->and($colleague->notificationSubscriptions()->sole()->unsubscribed_at)->toBeNull();
});

test('unsubscribing an unsubscribed administrator again keeps the date they first unsubscribed', function () {
    $team = Team::factory()->create();
    $user = User::factory()->unsubscribedFrom($team)->create();
    $unsubscribedAt = $user->notificationSubscriptions()->sole()->unsubscribed_at;

    $this->actingAs($user)->put("/settings/notifications/teams/{$team->id}", ['subscribed' => false]);

    expect($user->notificationSubscriptions()->sole()->unsubscribed_at)->toEqual($unsubscribedAt);
});

test('subscribes the administrator to a team\'s change summaries again', function () {
    $team = Team::factory()->create(['name' => 'FBC Kutná Hora B']);
    $user = User::factory()->unsubscribedFrom($team)->create();
    $colleague = User::factory()->unsubscribedFrom($team)->create();

    $response = $this->actingAs($user)
        ->from('/settings/notifications')
        ->put("/settings/notifications/teams/{$team->id}", ['subscribed' => true]);

    $response->assertRedirect('/settings/notifications')
        ->assertInertiaFlash('toast.message', 'Změny týmu FBC Kutná Hora B vám budou chodit e-mailem.');
    expect($user->notificationSubscriptions()->sole()->unsubscribed_at)->toBeNull()
        ->and($colleague->notificationSubscriptions()->sole()->unsubscribed_at)->not->toBeNull();
});

test('answers 404 for an unknown team', function () {
    $this->actingAs(User::factory()->create())
        ->put('/settings/notifications/teams/999', ['subscribed' => false])
        ->assertNotFound();
});

test('turns import failure notifications off for the signed-in administrator only', function () {
    $user = User::factory()->create();
    $colleague = User::factory()->create();

    $response = $this->actingAs($user)
        ->from('/settings/notifications')
        ->put('/settings/notifications/import-failures', ['notify_import_failures' => false]);

    $response->assertRedirect('/settings/notifications')
        ->assertInertiaFlash('toast.message', 'E-maily o chybách importu vám už chodit nebudou.');
    expect($user->fresh()->notify_import_failures)->toBeFalse()
        ->and($colleague->fresh()->notify_import_failures)->toBeTrue();
});

test('turns import failure notifications back on', function () {
    $user = User::factory()->create(['notify_import_failures' => false]);

    $this->actingAs($user)
        ->from('/settings/notifications')
        ->put('/settings/notifications/import-failures', ['notify_import_failures' => true])
        ->assertInertiaFlash('toast.message', 'E-maily o chybách importu vám budou chodit.');

    expect($user->fresh()->notify_import_failures)->toBeTrue();
});
