# 13 — Admin notifications

**What to build:** The failed-import and change-summary emails become queued Laravel notifications sent to all users with one `Notification::send()` call, instead of a `Mail::to($user)->send()` loop per mailable. The emails the administrators receive stay the same. Further channels (e.g. database or Slack) can later be added in `via()` without touching the import.

**Blocked by:** 11 — Change summary email

**Status:** ready-for-agent

- [ ] `App\Notifications\ImportFailed` and `App\Notifications\FixtureListRevised` replace the mailables in `App\Mail`. Each is queued and sends over the `mail` channel only.
- [ ] `toMail()` renders the existing Markdown views (`mail.import-failed`, `mail.fixture-list-revised`) with the same subject and content as today, including the literal rendering of the change summary.
- [ ] `ImportTeamSeason` sends each notification to all users with `Notification::send()`; both per-user `Mail::to()` loops are gone. The rules for when each one is sent don't change.
- [ ] The arch rules that apply to `App\Mail` (queued, readonly exemption) apply to `App\Notifications` instead, and `App\Mail` is removed once empty.
- [ ] The tests for sending use `Notification::fake()` and `assertSentTo()` for every user, and assert nothing is sent where nothing was sent before. The content tests render `toMail()` and assert the same texts as today.
- [ ] `composer ci:check` passes.

## Notes

- `app/Notifications` is a new base folder, approved by the user.
- `User` already uses `Notifiable`.
- Ticket 12 moves the texts of both emails into `lang/cs/import.php`. It is blocked by this ticket, so it moves them from the notifications, not from the mailables.
