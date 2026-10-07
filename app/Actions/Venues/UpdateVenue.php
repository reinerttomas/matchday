<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Models\Venue;

final readonly class UpdateVenue
{
    /**
     * Update the venue's address; the next calendar feed request shows it in the location, and later imports keep it, as they only fill in a missing address.
     *
     * @param  array{address: string}  $attributes
     */
    public function handle(Venue $venue, array $attributes): void
    {
        $venue->update($attributes);
    }
}
