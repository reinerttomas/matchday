# 21 — Změny: the imports that changed the fixture list

**What to build:** The administrator sees what changed in the selected team season's fixture list and when, one entry per import that recorded revisions, and whether the team has already been told about it. The sidebar shows how many change summaries are still waiting to be sent. Sending a summary to WhatsApp is ticket 22.

**Blocked by:** 18 — Admin shell and the Importy page

**Status:** ready-for-agent

- [ ] `/changes` lists the selected team season's imports that recorded revisions, newest first.
- [ ] Each import shows its revised fixtures (date and "Home – Away") with each revision as "Pole: ~~staré~~ → nové", e.g. "Čas: ~~TBD~~ → 9:00", with values formatted for display (dates, TBD, venue names, status labels).
- [ ] A fixture that appeared after the initial import is listed as a new fixture.
- [ ] The initial import is shown last as one collapsed entry ("24 zápasů přidáno do rozpisu", from its `fixtures_found`) that can be expanded to list the fixtures; it is never offered for sending.
- [ ] Each other import shows "Odesláno týmu {kdy}" when it has `notified_at`, otherwise a "Poslat do WhatsAppu" button (the dialog it opens is ticket 22).
- [ ] The sidebar shows next to Změny the number of the selected team season's imports that have revisions, have no `notified_at` and are not the initial import; no number when it's zero.
- [ ] "Rozpis se od importu nezměnil" when there are no revisions.
- [ ] Domain texts the server formats (field names, values, entry headings) come from `lang/cs/`, reusing the change summary's formatting where it fits.
- [ ] UI is built from shadcn components (Card, Collapsible, Badge, Button, Empty, …).
- [ ] Feature tests cover: order, revisions per fixture and their display values, new fixtures, the collapsed initial import, sent vs. unsent, the sidebar count (excluding the initial import, notified imports and other team seasons), the empty state. No N+1.
- [ ] `composer ci:check` passes.

## Notes

- Spec: "Revisions", "Change summary", "Admin pages" (Změny), user stories 40 (the count), 68–72 and 77.
