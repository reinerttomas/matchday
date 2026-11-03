<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;

final readonly class UpdateUser
{
    /**
     * @param  array{notify_import_failures: bool}  $attributes
     */
    public function handle(User $user, array $attributes): void
    {
        $user->update($attributes);
    }
}
