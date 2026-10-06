<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Import;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

final class FixtureListRevised extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create the notification giving an administrator the change summary of an import that recorded revisions, ready to send to the team's WhatsApp group.
     */
    public function __construct(
        public Import $import,
        public string $summary,
        public string $whatsAppUrl,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Změny v rozpisu: {$this->import->teamSeason->displayNameWithSeason()}")
            ->markdown('mail.fixture-list-revised', [
                'teamSeason' => $this->import->teamSeason->displayNameWithSeason(),
                'summaryLines' => $this->summaryAsLiteralMarkdown(),
                'whatsAppUrl' => $this->whatsAppUrl,
            ]);
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
