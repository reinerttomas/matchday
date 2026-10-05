<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\ImportStatus;
use App\Models\Import;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ImportFailed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create the message telling an administrator why an import ended as error or aborted.
     */
    public function __construct(public Import $import) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $outcome = $this->import->status === ImportStatus::Aborted ? 'přerušen' : 'selhal';

        return new Envelope(
            subject: "Import rozpisu {$outcome}: {$this->teamSeasonLabel()}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.import-failed',
            with: [
                'isAborted' => $this->import->status === ImportStatus::Aborted,
                'teamSeason' => $this->teamSeasonLabel(),
                'reason' => $this->import->error,
                'sourceUrl' => $this->import->teamSeason->source_url,
            ],
        );
    }

    /**
     * Name the team season by its team name and season, such as "FBC Kutná Hora B 2026/27".
     */
    private function teamSeasonLabel(): string
    {
        $teamSeason = $this->import->teamSeason;

        // A team season gets its name from its first ok import, so until then the team's slug stands in for it.
        return ($teamSeason->name ?? $teamSeason->team->slug).' '.$teamSeason->season->name;
    }
}
