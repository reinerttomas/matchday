<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\ImportStatus;
use App\Models\Import;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ImportFailed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create the notification telling an administrator why an import ended as error or aborted.
     */
    public function __construct(public Import $import) {}

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
        $isAborted = $this->import->status === ImportStatus::Aborted;
        $outcome = $isAborted ? 'přerušen' : 'selhal';

        return (new MailMessage)
            ->subject("Import rozpisu {$outcome}: {$this->import->teamSeason->displayNameWithSeason()}")
            ->markdown('mail.import-failed', [
                'isAborted' => $isAborted,
                'teamSeason' => $this->import->teamSeason->displayNameWithSeason(),
                'reason' => $this->import->error,
                'sourceUrl' => $this->import->teamSeason->source_url,
            ]);
    }
}
