# 07 — Venues from match detail pages

**What to build:** Fixtures get their venue with an address, so players can navigate from a calendar event. The import fetches a fixture's match detail page only when needed, gently, and creates or updates the venue from it. A venue change is recorded as a revision. See the spec (`.scratch/matchday/spec.md`, Implementation Decisions → Import module → Venues and → Revisions), `CONTEXT.md` and ADR-0002.

**Blocked by:** 06 — Revisions on re-import

**Status:** ready-for-agent

- [ ] The match detail page is fetched only for a new fixture, or when the venue text in the list differs from the stored venue's name. Finished rows ("odehráno" in the venue slot) never trigger a fetch or a venue change.
- [ ] Requests are paced with a pause between them; the pause is configurable so tests don't wait.
- [ ] From the detail page the import takes the arena ID, the name and the address; "Čáslavská 274<br>Kutná Hora" is stored as "Čáslavská 274, Kutná Hora".
- [ ] The venue is looked up by external ID: created with its address when missing, renamed when it exists under another name.
- [ ] A changed venue writes a revision whose old and new values are venue **names**.
- [ ] An HTML snapshot of a live match detail page is saved for tests (same rule as ticket 05: ask if blocked).
- [ ] Feature tests cover: a new fixture gets a venue with an address, an unchanged venue is not fetched again, a venue change writes a revision, a finished row doesn't touch the venue, an existing venue under a new name is renamed.
- [ ] `composer ci:check` passes.
