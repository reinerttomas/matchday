# 26 — Haly: fix venue addresses

**What to build:** Imports fill in venue addresses from the federation's match detail pages, but some can stay missing or wrong. The administrator sees which venues lack an address and fills it in, previewing how the location will appear in players' calendars. Venues otherwise stay in sync with the federation: no adding, renaming or deleting by hand.

**Blocked by:** 18 — Admin shell and the Importy page

**Status:** resolved

- [x] `/venues` lists the venues with name, address (or a "Doplnit adresu" link), number of fixtures and an edit button.
- [x] A sentence above the list says how many venues lack an address.
- [x] Editing a venue changes only its address, validated through a form request; the form previews the calendar location ("name, address") as the feed builds it.
- [x] An edited address shows up in the location of the venue's calendar events on the next feed request, and a later import doesn't overwrite it.
- [x] There is no way to add, rename or delete a venue.
- [x] UI is built from shadcn components (Table, Dialog, Input, Button, …).
- [x] Feature tests cover: the list props with fixture counts and the missing-address count, editing the address (and the feed's location afterwards), validation, and login required.
- [x] `composer ci:check` passes.

## Notes

- Spec: "Import module" (Venues), "Admin pages" (Haly), user stories 80–84. Venues aren't scoped to the selected season.
- Editing addresses isn't audited (spec "Out of Scope").
