<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Collection;

/**
 * @phpstan-type VenueProps array{id: int, name: string, address: string|null, fixturesCount: int}
 * @phpstan-type VenueListProps array{venues: list<VenueProps>, missingAddressSummary: string}
 */
final readonly class VenueListPresenter
{
    /**
     * Describe the venues for the Haly page by name, with how many fixtures of any season each hosts, and say how many still lack an address.
     *
     * @param  Collection<int, Venue>  $venues
     * @return VenueListProps
     */
    public function present(Collection $venues): array
    {
        $venues->loadCount('fixtures');
        $missingAddressCount = $venues->whereNull('address')->count();

        return [
            'venues' => array_values($venues
                ->map(fn (Venue $venue): array => $this->venue($venue))
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->all()),
            'missingAddressSummary' => trans_choice('venues.missing_addresses', $missingAddressCount, ['count' => $missingAddressCount]),
        ];
    }

    /**
     * Describe one venue as a row of the list.
     *
     * @return VenueProps
     */
    private function venue(Venue $venue): array
    {
        return [
            'id' => $venue->id,
            'name' => $venue->name,
            'address' => $venue->address,
            'fixturesCount' => (int) $venue->getAttribute('fixtures_count'),
        ];
    }
}
