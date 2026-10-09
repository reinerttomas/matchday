<?php

declare(strict_types=1);

use App\Enums\TeamPageAction;
use App\Models\Team;
use App\Models\TeamPageEvent;

const IPHONE_SAFARI = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

test('records a subscribe tap from the public team page', function () {
    $team = Team::factory()->create(['slug' => 'kutna-hora-b']);

    $response = $this->withHeader('User-Agent', IPHONE_SAFARI)
        ->post('/t/kutna-hora-b/events', ['action' => 'google']);

    $response->assertNoContent();
    expect(TeamPageEvent::query()->sole())
        ->team->is($team)->toBeTrue()
        ->action->toBe(TeamPageAction::Google)
        ->in_app_browser->toBeNull()
        ->user_agent->toBe(IPHONE_SAFARI)
        ->created_at->not->toBeNull();
});

test('records the in-app browser the page was opened in', function () {
    Team::factory()->create(['slug' => 'kutna-hora-b']);

    $this->post('/t/kutna-hora-b/events', ['action' => 'webcal', 'in_app_browser' => 'messenger'])->assertNoContent();

    expect(TeamPageEvent::query()->sole())
        ->action->toBe(TeamPageAction::Webcal)
        ->in_app_browser->toBe('messenger');
});

test('records a tap on a button that leaves the in-app browser', function (string $action, TeamPageAction $recordedAction) {
    Team::factory()->create(['slug' => 'kutna-hora-b']);

    $this->post('/t/kutna-hora-b/events', ['action' => $action, 'in_app_browser' => 'messenger'])->assertNoContent();

    expect(TeamPageEvent::query()->sole())
        ->action->toBe($recordedAction)
        ->in_app_browser->toBe('messenger');
})->with([
    'Android escape' => ['escape_intent', TeamPageAction::EscapeIntent],
    'iOS escape' => ['escape_safari', TeamPageAction::EscapeSafari],
    'copy page link' => ['copy_page_link', TeamPageAction::CopyPageLink],
]);

test('rejects an unknown action with 422 although a beacon does not ask for json', function () {
    Team::factory()->create(['slug' => 'kutna-hora-b']);

    $response = $this->post('/t/kutna-hora-b/events', ['action' => 'download']);

    $response->assertUnprocessable()->assertJsonValidationErrorFor('action');
    expect(TeamPageEvent::query()->exists())->toBeFalse();
});

test('rejects an in-app browser name longer than 32 characters', function () {
    Team::factory()->create(['slug' => 'kutna-hora-b']);

    $response = $this->post('/t/kutna-hora-b/events', ['action' => 'google', 'in_app_browser' => str_repeat('a', 33)]);

    $response->assertUnprocessable()->assertJsonValidationErrorFor('in_app_browser');
    expect(TeamPageEvent::query()->exists())->toBeFalse();
});

test('returns 404 for an unknown slug', function () {
    $this->post('/t/unknown-team/events', ['action' => 'google'])->assertNotFound();

    expect(TeamPageEvent::query()->exists())->toBeFalse();
});

test('cuts the user agent to 512 characters', function () {
    Team::factory()->create(['slug' => 'kutna-hora-b']);

    $this->withHeader('User-Agent', str_repeat('a', 500).str_repeat('b', 100))
        ->post('/t/kutna-hora-b/events', ['action' => 'page_view'])
        ->assertNoContent();

    expect(TeamPageEvent::query()->sole()->user_agent)->toBe(str_repeat('a', 500).str_repeat('b', 12));
});

test('throttles a client that sends more than 30 events a minute', function () {
    Team::factory()->create(['slug' => 'kutna-hora-b']);

    foreach (range(1, 30) as $attempt) {
        $this->post('/t/kutna-hora-b/events', ['action' => 'page_view'])->assertNoContent();
    }

    $this->post('/t/kutna-hora-b/events', ['action' => 'page_view'])->assertTooManyRequests();
    expect(TeamPageEvent::query()->count())->toBe(30);
});
