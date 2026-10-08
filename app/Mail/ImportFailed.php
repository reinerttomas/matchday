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
use LogicException;

final class ImportFailed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create the mail telling the administrators why an import ended as error or aborted.
     */
    public function __construct(public Import $import) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __("imports.notifications.failed.{$this->outcome()}.subject", ['team_season' => $this->teamSeasonName()]),
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
                'outcome' => $this->outcome(),
                'teamSeason' => $this->teamSeasonName(),
                'reason' => $this->import->error,
                'sourceUrl' => $this->import->teamSeason->source_url,
            ],
        );
    }

    /**
     * Get the outcome the mail reports, which picks its texts.
     */
    private function outcome(): string
    {
        return match ($this->import->status) {
            ImportStatus::Error => 'error',
            ImportStatus::Aborted => 'aborted',
            ImportStatus::Ok, ImportStatus::Running => throw new LogicException("Import {$this->import->id} did not fail, so there is no failure to report."),
        };
    }

    /**
     * Get the name of the imported team season, with its season.
     */
    private function teamSeasonName(): string
    {
        return $this->import->teamSeason->displayNameWithSeason();
    }
}
