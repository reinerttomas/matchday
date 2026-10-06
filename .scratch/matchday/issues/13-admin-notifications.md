# 13 — Admin notifications

**What to build:** The failed-import and change-summary emails become queued Laravel notifications that each user receives through `$user->notify()`, instead of mailables sent with `Mail::to($user)->send()`. The emails the administrators receive stay the same. Further channels (e.g. database or Slack) can later be added in `via()` without touching the import.

**Blocked by:** 11 — Change summary email

**Status:** resolved

- [x] `App\Notifications\ImportFailed` and `App\Notifications\FixtureListRevised` replace the mailables in `App\Mail`. Each is queued and sends over the `mail` channel only.
- [x] `toMail()` renders the existing Markdown views (`mail.import-failed`, `mail.fixture-list-revised`) with the same subject and content as today, including the literal rendering of the change summary.
- [x] `ImportTeamSeason` notifies every user with `$user->notify()`; both `Mail::to()` calls are gone. The rules for when each one is sent don't change.
- [x] The arch rules that apply to `App\Mail` (queued, readonly exemption) apply to `App\Notifications` instead, and `App\Mail` is removed once empty.
- [x] The tests for sending use `Notification::fake()` and `assertSentTo()` for every user, and assert nothing is sent where nothing was sent before. The content tests render `toMail()` and assert the same texts as today.
- [x] `composer ci:check` passes.

## Notes

- `app/Notifications` is a new base folder, approved by the user.
- `User` already uses `Notifiable`.
- Ticket 12 moves the texts of both emails into `lang/cs/import.php`. It is blocked by this ticket, so it moves them from the notifications, not from the mailables.
