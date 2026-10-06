<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\ImportStatus;
use App\Models\Import;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use LogicException;

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
        $outcome = match ($this->import->status) {
            ImportStatus::Error => 'error',
            ImportStatus::Aborted => 'aborted',
            ImportStatus::Ok, ImportStatus::Running => throw new LogicException("Import {$this->import->id} did not fail, so there is no failure to report."),
        };
        $teamSeason = $this->import->teamSeason->displayNameWithSeason();

        return (new MailMessage)
            ->subject(__("imports.notifications.failed.{$outcome}.subject", ['team_season' => $teamSeason]))
            ->markdown('mail.import-failed', [
                'outcome' => $outcome,
                'teamSeason' => $teamSeason,
                'reason' => $this->import->error,
                'sourceUrl' => $this->import->teamSeason->source_url,
            ]);
    }
}
