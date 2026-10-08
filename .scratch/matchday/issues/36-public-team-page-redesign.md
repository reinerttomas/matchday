# 36 — Public team page with a club hero and match day cards

**What to build:** The public team page gets a club-style hero with the team's name, a two-column body with the schedule on the left and a compact subscribe card on the right, and the schedule as one card per match day with a large date tile, the next match day highlighted. Subscribing becomes three buttons ordered for the device plus a "Nefunguje to?" dialog for the manual way; the QR code goes away.

**Blocked by:** —

**Status:** resolved

- [x] A full-width hero with a blue gradient (white text, dark mode included) shows the competition and season in small uppercase letters and the team name large. It shows no counts or next-match-day figures. The empty state for a team without a team season in the current season ("Pro sezonu X zatím není rozpis") keeps its wording inside the same hero layout.
- [x] Under the hero, on a wide screen the schedule sits on the left and the subscribe card in a right column (about 18rem) that stays in view while scrolling (sticky). On a phone the subscribe card comes first and the schedule follows it.
- [x] The subscribe card ("Zápasy do kalendáře") explains in one line that the calendar updates itself and moved fixtures move in it too, then shows three full-width buttons with icons: "iPhone / Mac" (webcal), "Google Kalendář" and "Outlook". The first button is the device's (iPhone / Mac on an Apple device, Google Kalendář otherwise, decided on the client as today) and is emphasized; the others are outline buttons.
- [x] Under the buttons, "Kopírovat adresu" copies the calendar address (shows "Adresa zkopírována" after copying; where the clipboard is blocked, the address input in the dialog stays selectable by hand as today), and "Nefunguje to?" opens a Dialog with the step-by-step instructions in the existing device tabs (Google, Android, iPhone, Outlook, preselected for the device) and the calendar address with its copy button. The note that changes are also announced in the WhatsApp group stays, in the card or the dialog.
- [x] The QR code and the `qrcode.react` dependency are removed.
- [x] The schedule ("Rozpis zápasů") lists one card per match day. On the left a date tile: the weekday abbreviation ("NE"), the day number large and the month abbreviation ("lis"). On the right, the venue once at the top when all the day's fixtures share it, then one line per fixture: the time (or the score of a finished fixture, or "TBD"), "Home – Away" with our team bold, and below it the round, the venue when the fixtures don't share one, and the status badge (Odloženo, Zrušeno). A cancelled fixture is struck through.
- [x] The next match day's card is highlighted: a blue date tile, a blue border and a badge with the relative day ("Dnes", "Zítra", "Za 3 dny", "Za 10 dní") next to the venue. The relative day and the date-tile parts come from the server, not from the browser's clock.
- [x] Everything else behaves as today: the first four match days with "Zobrazit celou sezonu" for the rest, the footer (data source, last update, what TBD means), "Sezona skončila" when no upcoming fixtures remain, 404 for an unknown slug, no horizontal scroll on a phone.
- [x] Spec user stories 18–25 and 28, the "Public team page" section and the browser test list (`.scratch/matchday/spec.md`) are updated to the new layout; story 21 (QR code) is removed.
- [x] UI is built from shadcn components (Card, Badge, Button, Dialog, Tabs, Input, …).
- [x] Feature tests cover the new props (the date-tile parts and the relative day of each match day). Browser tests cover the button order on an emulated iPhone vs. Android/desktop, copying the address, the "Nefunguje to?" dialog with the device's tab preselected, "Zobrazit celou sezonu", no horizontal scroll and no JavaScript errors on a phone; the QR code test goes away.
- [x] `composer ci:check` passes.

## Notes

- Decided by a UI prototype on 2026-10-07 over four rounds on `/t/{slug}?variant=`: the administrator liked the club hero of round one's C, then C4's two columns with the compact subscribe card (round two), tried and dropped schedules built from the reui Timeline (round three), and picked `E1`, match day cards with a date tile, with the hero stats removed (round four). The prototype lives on branch `prototype/public-team-page` (commits ed82714 and d752a63; `resources/js/pages/public/prototype/variants-list.tsx` `VariantE1`, `variants-c.tsx` `TwoColumns` and `SidebarSubscribe`, `variant-c.tsx` `Hero`). Rewrite it properly; don't copy the prototype code.
- The prototype computed the relative day and the date parts in the browser and told home from away by the matchup order; the real page gets the date parts and the relative day from the server (`fixtures.team_page` translations), and shows no home/away cue.
- The prototype showed every match day; keep the existing "first four + Zobrazit celou sezonu" behaviour (story 28) unless the administrator asks otherwise.
- The `@reui` registry and `@reui/timeline` were only used by dropped variants; they stay on the prototype branch.
- Domain texts formatted on the server come from `lang/cs/`; static UI copy is written in Czech in TSX (ticket 16).
- Spec: "Player — public team page", user stories 18–30.
