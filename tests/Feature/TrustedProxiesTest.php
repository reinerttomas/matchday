<?php

declare(strict_types=1);

test('redirects to https when the reverse proxy forwards an https request', function () {
    $response = $this
        ->withServerVariables(['REMOTE_ADDR' => '172.18.0.5'])
        ->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'matchday.example.com',
            'X-Forwarded-Port' => '443',
        ])
        ->get('http://localhost/dashboard');

    $response->assertRedirect('https://matchday.example.com/login');
});
