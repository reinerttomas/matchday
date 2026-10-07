<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Enums\ImportStatus;
use App\Enums\ImportTrigger;
use App\Models\Import;
use App\Models\TeamSeason;
use App\Models\User;
use App\Notifications\FixtureListRevised;
use App\Notifications\ImportFailed;
use App\Services\Ceskyflorbal\CeskyflorbalClient;
use App\Services\Ceskyflorbal\FixtureListPageData;
use App\Services\Ceskyflorbal\MatchDetailPageData;
use App\Services\ChangeSummaryWriter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;
use UnexpectedValueException;

final readonly class ImportTeamSeason
{
    /**
     * Create a new instance.
     */
    public function __construct(
        private CeskyflorbalClient $ceskyflorbal,
        private ApplyFixtureList $applyFixtureList,
        private ChangeSummaryWriter $changeSummaryWriter,
    ) {}

    /**
     * Download the team season's fixture list and the match detail pages it needs from ceskyflorbal.cz and apply them, recording the run as an import.
     *
     * Returns null without importing while another import of the team season is running. A fixture list that fails to download or read ends the import as error, and one that looks broken aborts it; either way no data changes and every user is emailed the reason. Any other failure also ends the import as error before it is rethrown. An ok import whose revisions make a change summary emails it to every user.
     */
    public function handle(TeamSeason $teamSeason, ImportTrigger $trigger): ?Import
    {
        $import = $this->start($teamSeason, $trigger);

        if ($import === null) {
            return null;
        }

        try {
            $this->downloadAndApply($teamSeason, $import);
        } catch (Throwable $exception) {
            // The fixture list's transaction has rolled back by now, but the import may still hold the ok state written inside it.
            $import->refresh();
            $this->finishWithoutApplying($import, ImportStatus::Error, __('imports.errors.unexpected'));

            throw $exception;
        }

        // Outside the try, because the fixture list is committed by now, so a failure to send can't turn the import into an error.
        $this->announceRevisions($import);

        return $import;
    }

    /**
     * Record a running import of the team season, or return null when another import of it is running.
     *
     * The lock covers only the check and the creation, so two triggers arriving at once can't both start. When another process holds it, that process is starting an import of the team season right now, so this one is skipped instead of waiting.
     */
    private function start(TeamSeason $teamSeason, ImportTrigger $trigger): ?Import
    {
        $import = Cache::lock("imports:team-season:{$teamSeason->id}", 10)->get(function () use ($teamSeason, $trigger): ?Import {
            if ($teamSeason->imports()->alive()->exists()) {
                return null;
            }

            // No running import is alive by now, so every one left is dead.
            foreach ($teamSeason->imports()->where('status', ImportStatus::Running)->get() as $deadImport) {
                $this->endDeadImport($deadImport);
            }

            // Created outside the fixture list's transaction so a running import is visible while the pages download, and a failed one stays in the history.
            return $teamSeason->imports()->create([
                'trigger' => $trigger,
                'status' => ImportStatus::Running,
                'started_at' => now(),
            ]);
        });

        return $import instanceof Import ? $import : null;
    }

    /**
     * End a running import whose worker or process was killed, so it no longer blocks the team season's imports.
     *
     * Nobody is emailed; the error log reaches Nightwatch instead.
     */
    private function endDeadImport(Import $import): void
    {
        $import->update([
            'status' => ImportStatus::Error,
            'finished_at' => now(),
            'error' => __('imports.errors.unfinished'),
        ]);

        Log::error('A running import was never finished, so it ends as error.', [
            'import_id' => $import->id,
            'team_season_id' => $import->team_season_id,
        ]);
    }

    /**
     * Download the fixture list and the match detail pages it needs, and apply them unless the download failed or the fixture list looks broken.
     */
    private function downloadAndApply(TeamSeason $teamSeason, Import $import): Import
    {
        try {
            $page = $this->ceskyflorbal->fixtureList($teamSeason);
        } catch (RequestException $exception) {
            return $this->finishWithoutApplying($import, ImportStatus::Error, $this->httpErrorReason($exception->response->status()));
        } catch (ConnectionException $exception) {
            Log::warning('Connecting to ceskyflorbal.cz failed.', [
                'team_season_id' => $teamSeason->id,
                'error' => $exception->getMessage(),
            ]);

            return $this->finishWithoutApplying($import, ImportStatus::Error, __('imports.errors.connection'));
        } catch (UnexpectedValueException $exception) {
            return $this->finishWithoutApplying($import, ImportStatus::Error, __('imports.errors.unreadable_fixture_list', ['message' => $exception->getMessage()]));
        }

        $abortReason = $this->abortReason($teamSeason, $page);

        if ($abortReason !== null) {
            return $this->finishWithoutApplying($import, ImportStatus::Aborted, $abortReason, count($page->rows));
        }

        // Downloaded before the fixture list is applied, so its transaction never holds the database locked while the paced requests wait.
        $matchDetails = $this->matchDetails($teamSeason, $page);

        $this->applyFixtureList->handle($teamSeason, $import, $page, $matchDetails);

        return $import;
    }

    /**
     * Download the match detail page of each fixture that is new or whose venue in the fixture list differs from the stored venue's name.
     *
     * Finished rows show no venue, so they never need one. A page that fails is skipped; its fixture still differs from the list, so a later import tries it again.
     *
     * @return array<int, MatchDetailPageData> keyed by the fixture's external ID
     */
    private function matchDetails(TeamSeason $teamSeason, FixtureListPageData $page): array
    {
        $storedFixtures = $teamSeason->fixtures()->with('venue')->get()->keyBy('external_id');
        $matchDetails = [];

        foreach ($page->rows as $row) {
            if ($row->venueName !== null && $row->venueName !== $storedFixtures->get($row->externalId)?->venue?->name) {
                try {
                    $matchDetail = $this->ceskyflorbal->matchDetail($row->externalId);
                } catch (HttpClientException|UnexpectedValueException $exception) {
                    Log::warning('The match detail page failed, so the fixture keeps its venue until a later import.', [
                        'team_season_id' => $teamSeason->id,
                        'external_id' => $row->externalId,
                        'error' => $exception->getMessage(),
                    ]);

                    continue;
                }

                if ($matchDetail->venueName !== $row->venueName) {
                    Log::warning('The match detail page names the venue differently than the fixture list, so it is downloaded again on every import.', [
                        'external_id' => $row->externalId,
                        'fixture_list_venue' => $row->venueName,
                        'match_detail_venue' => $matchDetail->venueName,
                    ]);
                }

                $matchDetails[$row->externalId] = $matchDetail;
            }
        }

        return $matchDetails;
    }

    /**
     * Explain why a fixture list looks too broken to apply, or return null when it can be applied.
     */
    private function abortReason(TeamSeason $teamSeason, FixtureListPageData $page): ?string
    {
        // Checked first, because a page of another season is most likely a fixture list address left unchanged after copying a team to a new season.
        if ($page->seasonName !== $teamSeason->season->name) {
            return __('imports.errors.season_mismatch', ['page_season' => $page->seasonName, 'season' => $teamSeason->season->name]);
        }

        $fixturesFound = count($page->rows);
        $fixturesFoundBefore = $teamSeason->imports()->where('status', ImportStatus::Ok)->latest('id')->first()?->fixtures_found;

        if ($fixturesFound > 0 && ($fixturesFoundBefore === null || $fixturesFound >= $fixturesFoundBefore / 2)) {
            return null;
        }

        return $fixturesFoundBefore === null
            ? trans_choice('imports.errors.fixtures_found', $fixturesFound)
            : trans_choice('imports.errors.fixtures_found_with_last_import', $fixturesFound, ['last_import_count' => $fixturesFoundBefore]);
    }

    /**
     * Finish an import that changed no data, and email every user the reason.
     */
    private function finishWithoutApplying(Import $import, ImportStatus $status, string $reason, ?int $fixturesFound = null): Import
    {
        $import->update([
            'status' => $status,
            'finished_at' => now(),
            'fixtures_found' => $fixturesFound,
            'error' => $reason,
        ]);

        User::query()->get()->each(fn (User $user) => $user->notify(new ImportFailed($import)));

        return $import;
    }

    /**
     * Email every user the change summary of an ok import, unless none of its revisions is worth telling the team.
     *
     * The initial import records no revisions, so its additions are never announced.
     */
    private function announceRevisions(Import $import): void
    {
        $summary = $import->status === ImportStatus::Ok ? $this->changeSummaryWriter->write($import) : null;

        if ($summary === null) {
            return;
        }

        $whatsAppUrl = $this->changeSummaryWriter->whatsAppUrl($summary);

        User::query()->get()->each(fn (User $user) => $user->notify(new FixtureListRevised($import, $summary, $whatsAppUrl)));
    }

    /**
     * Describe why ceskyflorbal.cz answered with a non-2xx status.
     */
    private function httpErrorReason(int $status): string
    {
        $reason = match (true) {
            $status === 403 => 'forbidden',
            $status === 404 => 'not_found',
            $status === 429 => 'too_many_requests',
            $status >= 500 => 'server_error',
            default => 'unexpected',
        };

        return __("imports.errors.http.{$reason}", ['status' => $status]);
    }
}
