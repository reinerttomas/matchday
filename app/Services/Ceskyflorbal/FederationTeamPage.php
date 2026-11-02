<?php

declare(strict_types=1);

namespace App\Services\Ceskyflorbal;

use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Str;

/**
 * The addresses of a team season's page on ceskyflorbal.cz, all of them derived from the federation's team ID.
 */
final readonly class FederationTeamPage
{
    /**
     * Create a new instance.
     */
    public function __construct(
        #[Config('services.ceskyflorbal.url')]
        private string $ceskyflorbalUrl,
    ) {}

    /**
     * Read the federation's team ID from the address of any tab of a team's page on ceskyflorbal.cz, or null when the address is not one.
     */
    public function teamExternalId(string $address): ?int
    {
        // An address copied from the browser's address bar often comes without its scheme.
        $url = parse_url(Str::isMatch('#^https?://#i', $address) ? $address : "https://{$address}");

        if ($url === false || ! isset($url['host']) || $this->withoutWww($url['host']) !== $this->withoutWww($this->host())) {
            return null;
        }

        // Every tab (overview, matches, roster, …) sits at /team/detail/{tab}/{id}.
        $teamExternalId = Str::match('#^/team/detail/[a-z]+(?:-[a-z]+)*/([1-9]\d{0,17})/?$#i', $url['path'] ?? '');

        return $teamExternalId === '' ? null : (int) $teamExternalId;
    }

    /**
     * Get the address of the team's fixture list (the Zápasy tab), in the form the import downloads.
     */
    public function fixtureListAddress(int $teamExternalId): string
    {
        return "{$this->ceskyflorbalUrl}/team/detail/matches/{$teamExternalId}";
    }

    /**
     * Get the address of the team's overview (the Přehled tab).
     */
    public function overviewAddress(int $teamExternalId): string
    {
        return "{$this->ceskyflorbalUrl}/team/detail/overview/{$teamExternalId}";
    }

    /**
     * Get the host of ceskyflorbal.cz.
     */
    private function host(): string
    {
        return (string) parse_url($this->ceskyflorbalUrl, PHP_URL_HOST);
    }

    /**
     * Drop the "www." prefix, since ceskyflorbal.cz serves the same pages with and without it.
     */
    private function withoutWww(string $host): string
    {
        return Str::of($host)->lower()->chopStart('www.')->toString();
    }
}
