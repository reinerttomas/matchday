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
            subject: "Import rozpisu {$outcome}: {$this->import->teamSeason->displayNameWithSeason()}",
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
                'teamSeason' => $this->import->teamSeason->displayNameWithSeason(),
                'reason' => $this->import->error,
                'sourceUrl' => $this->import->teamSeason->source_url,
            ],
        );
    }
}
