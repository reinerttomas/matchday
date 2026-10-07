# 22 — Send a change summary to WhatsApp

**What to build:** From Změny, the administrator opens a ready-made change summary for an import, tweaks the text if needed, sends it to the team's WhatsApp group in one tap, and marks it as sent, so the sidebar count reflects what is left to send.

**Blocked by:** 21 — Změny: the imports that changed the fixture list

**Status:** ready-for-agent

- [ ] "Poslat do WhatsAppu" opens a "Souhrn změn pro WhatsApp" dialog with the import's change summary in an editable text field.
- [ ] "Kopírovat text" copies the current (edited) text.
- [ ] "Otevřít WhatsApp" opens `https://wa.me/?text=…` with the current (edited) text, URL-encoded, in a new tab.
- [ ] "Označit jako odesláno" appears only after WhatsApp was opened. Marking sets the import's `notified_at`, closes the dialog, shows "Odesláno týmu {kdy}" and lowers the sidebar count.
- [ ] Edits to the text aren't persisted.
- [ ] Marking requires login, only works for an import with revisions that isn't the initial import, and marking an already sent import doesn't change its `notified_at`.
- [ ] UI is built from shadcn components (Dialog, Textarea, Button, …).
- [ ] Feature tests cover the summary text in the props (or the endpoint that serves it) and the mark-as-sent action with its failure modes.
- [ ] A browser test covers: edit the text → "Otevřít WhatsApp" carries the edited text in the wa.me link → "Označit jako odesláno" appears → after marking the dialog closes and the sidebar count drops.
- [ ] `composer ci:check` passes.

## Notes

- Spec: "Change summary", user stories 72–76; the browser test is listed in "Testing Decisions".
- Reuse the existing change summary writer and its WhatsApp link; don't generate the text on the client.
- An import whose only revision is `is_rescheduled` 1 → 0 is listed on Změny and counted as unsent (ticket 21 follows the spec's definition), but `ChangeSummaryWriter::write()` returns null for it, so there is no text to send. Decide how the dialog handles it, e.g. no text and only "Označit jako odesláno".
- Ticket 21 renders "Poslat do WhatsAppu" disabled in `resources/js/pages/changes/index.tsx`; wire it here. The imports to announce are `TeamSeason::revisingImports()`, which marking should reuse for its "has revisions and isn't the initial import" check.
