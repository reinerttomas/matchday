<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Import;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

final class FixtureListRevised extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create the mail giving the administrators the change summary of an import that recorded revisions, ready to send to the team's WhatsApp group.
     */
    public function __construct(
        public Import $import,
        public string $summary,
        public string $whatsAppUrl,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('imports.notifications.fixture_list_revised.subject', ['team_season' => $this->teamSeasonName()]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.fixture-list-revised',
            with: [
                'teamSeason' => $this->teamSeasonName(),
                'summaryLines' => $this->summaryAsLiteralMarkdown(),
                'whatsAppUrl' => $this->whatsAppUrl,
            ],
        );
    }

    /**
     * Get the name of the imported team season, with its season.
     */
    private function teamSeasonName(): string
    {
        return $this->import->teamSeason->displayNameWithSeason();
    }

    /**
     * Prepare the summary for the Markdown panel so it shows exactly as the team will read it, one line per line.
     *
     * Every ASCII punctuation mark becomes a numeric HTML entity, which Markdown shows literally and the plain-text version decodes back, so a name such as "*Sparta*" never turns into emphasis. The view outputs the result unescaped, which is safe only because `<`, `>`, `&` and quotes are among the encoded marks. Each line ends in a hard line break, as Markdown would otherwise join the lines into one paragraph.
     */
    private function summaryAsLiteralMarkdown(): string
    {
        return Str::of($this->summary)
            ->replaceMatches('/[!-\/:-@\[-`{-~]/u', fn (array $match): string => '&#'.ord($match[0]).';')
            ->replace("\n", "  \n")
            ->toString();
    }
}
