# 03: Email fixture list revisions as a Mailable

**What to build:** When an ok import has a change summary, every user gets the fixture list revised email as before, but it is now a queued Mailable instead of a notification. It is one email addressed to all users at once, and nothing is sent when there are no users. The subject, Czech copy (`lang/cs/imports.php` `notifications.fixture_list_revised.*`) and the `mail.fixture-list-revised` view stay the same. The change summary still renders line by line with its Markdown punctuation and HTML escaped, and the button still opens WhatsApp. A failure to send still can't turn the import into an error.

With both emails moved, the app has no notification classes of its own left, so the "notifications are queued" arch rule and the `App\Notifications` arch exemptions go. Fortify's notifications and the user's mail routing for them stay.

**Blocked by:** 02 (Email import failures as a Mailable)

**Status:** ready-for-agent

- [ ] `FixtureListRevised` is a final, queued Mailable in `App\Mail`; the notification class and the empty `app/Notifications` directory are gone
- [ ] An ok import with revisions worth announcing queues exactly one `FixtureListRevised` mail addressed to every user, carrying the import, summary and WhatsApp URL
- [ ] No mail is queued when there are no users, when the import isn't ok, or when its revisions make no summary
- [ ] The mail's content tests (subject, line-by-line summary, WhatsApp link, escaping) are rewritten against the Mailable's own assertions; the empty Notifications test directory is gone
- [ ] The arch test no longer mentions `App\Notifications`
- [ ] `composer ci:check` passes
