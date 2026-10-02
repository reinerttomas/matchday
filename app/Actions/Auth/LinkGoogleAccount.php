<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

final readonly class LinkGoogleAccount
{
    /**
     * Find the user linked to the Google account, linking it by verified email on first sign-in.
     */
    public function handle(SocialiteUser $googleUser): ?User
    {
        $googleId = (string) $googleUser->getId();

        $user = User::query()->where('google_id', $googleId)->first();

        if ($user !== null) {
            return $user;
        }

        if (! $this->hasVerifiedEmail($googleUser)) {
            return null;
        }

        $user = User::query()
            ->where('email', Str::lower((string) $googleUser->getEmail()))
            ->whereNull('google_id')
            ->first();

        if ($user === null) {
            return null;
        }

        $user->google_id = $googleId;
        $user->save();

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return $user;
    }

    /**
     * Determine whether Google has verified the account's email address.
     */
    private function hasVerifiedEmail(SocialiteUser $googleUser): bool
    {
        if (! property_exists($googleUser, 'user') || ! is_array($googleUser->user)) {
            return false;
        }

        return ($googleUser->user['email_verified'] ?? false) === true;
    }
}
