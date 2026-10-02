<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LinkGoogleAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

final readonly class GoogleCallbackController
{
    /**
     * Log the user in after returning from Google.
     */
    public function __invoke(Request $request, LinkGoogleAccount $linkGoogleAccount): RedirectResponse
    {
        if ($request->has('error')) {
            return $this->failed(__('Google sign-in was cancelled.'));
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return $this->failed(__('Google sign-in failed. Please try again.'));
        }

        $user = $linkGoogleAccount->handle($googleUser);

        if ($user === null) {
            return $this->failed(__('No account is associated with this Google account.'));
        }

        // Google enforces its own MFA, so the Fortify two-factor challenge is intentionally skipped.
        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended(config('fortify.home'));
    }

    /**
     * Redirect back to the login page with an error toast.
     */
    private function failed(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

        return to_route('login');
    }
}
