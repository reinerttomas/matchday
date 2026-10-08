# 02: Email import failures as a Mailable

**What to build:** When an import ends as error or aborted, every user gets the import failure email as before, but it is now a queued Mailable instead of a notification. It is one email addressed to all users at once (`Mail::to($users)`), not one per user. When there are no users, nothing is sent, because a mail with no recipients would be rejected by the provider. The subject, Czech copy (`lang/cs/imports.php` `notifications.failed.*`) and the existing `mail.import-failed` Markdown view stay as they are, and an import that did not fail still can't produce this email.

This ticket also prepares the test suite for Mailables: mail is faked in every Feature and Browser test (next to the notification fake, which stays because Fortify's emails are still notifications), and the arch rules that exempt framework classes from `readonly`/no-inheritance also exempt `App\Mail`. The Laravel arch preset already requires Mailables to be queued.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] `ImportFailed` is a final, queued Mailable in `App\Mail`; the notification class is gone
- [ ] A failed or aborted import queues exactly one `ImportFailed` mail addressed to every user, carrying that import
- [ ] With no users, a failed import queues no mail
- [ ] The mail's subject and content tests (error, aborted, team season named by its team's name, source URL in the HTML) are rewritten against the Mailable's own assertions and live with the other mail tests, not under Notifications
- [ ] The import command tests assert on queued mail instead of sent notifications
- [ ] Fortify's password reset and verification tests are unchanged and pass
- [ ] `composer ci:check` passes
