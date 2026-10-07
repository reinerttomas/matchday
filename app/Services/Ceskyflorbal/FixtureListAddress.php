<?php

declare(strict_types=1);

namespace App\Services\Ceskyflorbal;

use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Str;

final readonly class FixtureListAddress
{
    /**
     * Create a new instance.
     */
    public function __construct(
        #[Config('services.ceskyflorbal.url')]
        private string $ceskyflorbalUrl,
    ) {}

    /**
     * Read the federation's team ID from the address of a team's fixture list on ceskyflorbal.cz, or null when the address is not one.
     */
    public function teamId(string $address): ?int
    {
        // An address copied from the browser's address bar often comes without its scheme.
        $url = parse_url(preg_match('#^https?://#i', $address) === 1 ? $address : "https://{$address}");

        if ($url === false || ! isset($url['host']) || $this->withoutWww($url['host']) !== $this->withoutWww($this->host())) {
            return null;
        }

        if (preg_match('#^/team/detail/matches/([1-9]\d{0,17})/?$#', $url['path'] ?? '', $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * Get the address of the fixture list of the federation's team, in the form the import downloads.
     */
    public function for(int $teamId): string
    {
        return "{$this->ceskyflorbalUrl}/team/detail/matches/{$teamId}";
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
