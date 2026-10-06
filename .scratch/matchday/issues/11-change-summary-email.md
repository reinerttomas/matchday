# 11 — Change summary email

**What to build:** After an import that found changes, the administrators get an email with a ready-made Czech change summary and a WhatsApp link, so they can tell the team in one tap without opening the app. The summary is generated on demand from an import's revisions and will be reused by the Změny page. See the spec (`.scratch/matchday/spec.md`, Implementation Decisions → Change summary and → Admin emails, user stories 69–70 and 85).

**Blocked by:** 07 — Venues from match detail pages, 09 — Missing and reappearing fixtures

**Status:** resolved

- [x] The change summary of an import is built from its revisions, one bullet per revised fixture ("NE 15. 11. Home – Away: …"), in the format of the spec's example, covering every revision kind: TBD → time, time change, date change (rescheduled, with the original date), venue, status (postponed, cancelled, finished with score, reappeared), rescheduled flag, and an added fixture.
- [x] The summary starts with "📅 Změny v rozpisu {team season name}" and ends with "Kalendář: {app URL}/t/{slug}".
- [x] A `https://wa.me/?text=…` link carries the URL-encoded summary.
- [x] After an `ok` import with revisions an email with the summary and the WhatsApp link goes to all users. The initial import and imports without revisions send nothing.
- [x] Feature tests run imports through the command and assert the summary text for each revision kind and the email (`Mail::fake()`).
- [x] `composer ci:check` passes.
