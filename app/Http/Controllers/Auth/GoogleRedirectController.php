<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

final readonly class GoogleRedirectController
{
    /**
     * Redirect the user to Google's authentication page.
     */
    public function __invoke(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }
}
