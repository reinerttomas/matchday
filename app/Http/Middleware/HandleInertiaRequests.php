<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AdminSelection;
use App\Services\UnsentChangeSummaries;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Create a new instance.
     */
    public function __construct(
        private readonly AdminSelection $adminSelection,
        private readonly UnsentChangeSummaries $unsentChangeSummaries,
    ) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            // Every admin page's sidebar switches the selection, so only guests go without it.
            'adminSelection' => fn (): ?array => $request->user() === null ? null : $this->adminSelection->present(),
            'unsentChangeSummaryCount' => fn (): ?int => $this->unsentChangeSummaryCount($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Count the selected team season's change summaries waiting to be sent, which the sidebar shows next to Změny; guests get none.
     */
    private function unsentChangeSummaryCount(Request $request): ?int
    {
        if ($request->user() === null) {
            return null;
        }

        $teamSeason = $this->adminSelection->teamSeason();

        return $teamSeason === null ? 0 : $this->unsentChangeSummaries->count($teamSeason);
    }
}
