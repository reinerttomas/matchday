<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('keeps the chosen appearance after a full page load', function () {
    $page = visit('/settings/appearance')->inLightMode();

    $page->click('Dark')
        ->assertScript('document.documentElement.classList.contains("dark")', true)
        ->assertScript('document.cookie.includes("appearance=dark")', true);

    // A new visit() would start with empty storage, so the same page loads again.
    $page->navigate('/settings/appearance')
        ->assertScript('document.documentElement.classList.contains("dark")', true)
        ->assertSeeIn('button[aria-pressed="true"]', 'Dark')
        ->click('System')
        ->assertScript('document.cookie.includes("appearance=system")', true)
        ->assertScript('document.documentElement.classList.contains("dark")', false)
        ->assertNoJavaScriptErrors();
});
