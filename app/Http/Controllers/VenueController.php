<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Venues\UpdateVenue;
use App\Http\Requests\UpdateVenueRequest;
use App\Models\Venue;
use App\Services\VenueListPresenter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class VenueController
{
    /**
     * Show every venue by name, whatever season its fixtures belong to, with how many still lack an address.
     */
    public function index(VenueListPresenter $venueListPresenter): Response
    {
        return Inertia::render('venues/index', [
            ...$venueListPresenter->present(Venue::query()->get()),
            // The edit form previews the calendar location in the format the feed writes it.
            'calendarLocationTemplate' => __('fixtures.calendar.location'),
        ]);
    }

    /**
     * Save the venue's address, and return to the page it was edited on.
     */
    public function update(UpdateVenueRequest $request, Venue $venue, UpdateVenue $updateVenue): RedirectResponse
    {
        $updateVenue->handle($venue, ['address' => $request->string('address')->toString()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('venues.updated', ['venue' => $venue->name])]);

        return back();
    }
}
