# 17 — Subscribe to the calendar from the public team page

**What to build:** The public team page gets a player subscribed to the team's calendar in about a minute. It offers one-tap buttons for Google Kalendář, iPhone / Mac and Outlook, with the option most relevant to the player's device first, step-by-step instructions per device, and a QR code on a wide screen so a player at a computer can continue on their phone. It never offers a calendar file download, because an imported static copy would never update.

**Blocked by:** 16 — Public team page with upcoming fixtures

**Status:** resolved

- [x] The page receives the team's calendar URLs, all derived from the permanent `/calendar/{slug}.ics` address: a Google Calendar subscribe link, a `webcal://` link for iPhone / Mac, and an Outlook subscribe link.
- [x] Button order is decided on the client: on an Apple device "iPhone / Mac" comes first; on any other device "Google Kalendář" comes first.
- [x] The player can copy the calendar address.
- [x] Step-by-step instructions are shown in tabs: Google, Android, iPhone, Outlook.
- [x] The page tells the player that changes show up within a few hours and are also announced in the team's WhatsApp group.
- [x] No link or button downloads the `.ics` file.
- [x] On a wide screen the page shows a QR code linking to the page itself; on a phone it is hidden.
- [x] `qrcode.react` is installed and renders the QR code.
- [x] A feature test covers the calendar URL props.
- [x] Browser tests cover: the button order on an emulated iPhone vs. Android and desktop; switching the instruction tabs; copying the address; the QR code visible on a wide screen only; no horizontal scroll and no JavaScript errors on mobile.
- [x] `composer ci:check` passes.

## Notes

- Spec: "Public team page", user stories 18–23 and 33.
- The user approved adding `qrcode.react` (spec "Further Notes" left the QR code library open). The QR code is rendered on the client because only wide screens show it.
- Static UI copy (button labels, instructions) is written in Czech in TSX, as decided for ticket 16.
