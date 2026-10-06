# 15 — Special fixture variants in the calendar feed

**What to build:** The calendar feed shows the state of each fixture the way a player needs to see it. A fixture with a TBD time doesn't show a fake time and doesn't block the player's day. A finished fixture shows its result, so the calendar doubles as a results history. A postponed fixture is clearly marked so nobody turns up on the old date. A cancelled fixture shows as cancelled. A rescheduled fixture explains why the round order looks odd.

**Blocked by:** 14 — Calendar feed for a team's current season

**Status:** ready-for-agent

- [ ] TBD time: an all-day event on the fixture's date with "(čas TBD)" in the title, marked TRANSP:TRANSPARENT so it doesn't block the day.
- [ ] When a TBD time gets a known time, the event becomes a timed 55-minute event with the same UID and a higher SEQUENCE.
- [ ] Finished: the title carries the score in "Home – Away" order.
- [ ] Postponed: the title starts with "ODLOŽENO:".
- [ ] Cancelled: the event has STATUS:CANCELLED and stays in the feed, so subscribed calendars mark it cancelled instead of silently dropping it.
- [ ] Rescheduled fixture: the description says "dohrávka N. kola" in place of "N. kolo".
- [ ] Every new text comes from `lang/cs/`, following ticket 12.
- [ ] Feature tests cover each variant: the all-day TBD event with TRANSP, the score in the title, the "ODLOŽENO:" prefix, STATUS:CANCELLED and the "dohrávka" description.
- [ ] `composer ci:check` passes.

## Notes

- Spec: "Calendar feed (ICS)" → "Event variants", user stories 8–12.
- How the variants combine (e.g. a postponed fixture with a TBD time) is left to the implementer, as long as each rule above holds.
- Ticket 14 already renders a TBD-time fixture as a plain all-day event (no "(čas TBD)", no TRANSP, untested) so it never shows as a fake 00:00 event. Replace that branch in `CalendarWriter` instead of adding a second one, and add its test.
