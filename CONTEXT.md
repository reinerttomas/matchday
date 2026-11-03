# Matchday

Publishes a floorball team's fixture list scraped from ceskyflorbal.cz as a subscribable calendar and tells the team what changed.

## Language

### Fixtures

**Fixture list**:
All fixtures of one team season, as the federation publishes them on ceskyflorbal.cz and as the app stores them. Shown as "Rozpis zápasů".
_Avoid_: Schedule, Matches

**Fixture**:
One scheduled game in a team season's fixture list, in any state (scheduled, postponed, finished, cancelled).
_Avoid_: Match, Game

**Rescheduled fixture**:
A postponed fixture that already has a replacement date; it stays scheduled. Shown as "dohrávka".
_Avoid_: Make-up game

**Postponed fixture**:
A fixture moved off its date with no replacement date known yet.

**Cancelled fixture**:
A fixture that will not be played — either cancelled at the source or missing from the source in two consecutive syncs.

**TBD time**:
A fixture whose start time the federation has not set yet (the source shows 00:00).

**Opponent**:
The other team in a fixture. Known only by name; it has no identity across seasons.

### Teams and places

**Season**:
A playing year such as 2026/2027, created by the administrator. Exactly one season is current; the current season decides what calendars show and which teams are imported automatically.

**Team**:
One of our teams that the app publishes a calendar for. The administrator names it when adding it, usually as the federation names it; the name is what the app shows wherever the team is named. Since our teams can share a name, a team is told apart by its name and category together. Its name, category, slug (derived from the name and category when the team is added) and calendar address persist across seasons.
_Avoid_: Tracked team

**Category**:
The age and gender group a team plays in, such as men, women or juniors. It belongs to the team and holds across seasons, while the competition can change from season to season. Two of our teams can share a federation name and differ only in category.
_Avoid_: Gender, Division

**Team season**:
A team's participation in one season: the federation's team ID for that season, and the name and competition the federation gives the team that season. Every address of its **federation team page**, the fixture list included, is derived from that ID. The imported name is kept but not shown; the team's own name is.

**Federation team page**:
A team season's page on ceskyflorbal.cz, with tabs such as Přehled (overview) and Zápasy (the fixture list). Every tab carries the same federation team ID, so the address of any tab identifies the team season.
_Avoid_: Overview, Team detail

**Calendar feed**:
A team's public, subscribable calendar address. Anyone with the address can subscribe; the app does not know who subscribes.
_Avoid_: Calendar subscription

**Venue**:
A sports hall where fixtures are played. Name and federation ID come from the source; the address comes from a fixture's detail page.
_Avoid_: Hall, Arena, Place

### Keeping fixtures current

**Import**:
One run of downloading and applying a team season's fixture list from the source. Ends as ok, error or aborted.
_Avoid_: Sync run, Fetch, Crawl

**Initial import**:
A team season's first successful import. Its additions are not announced to the team.

**Revision**:
One recorded change of one fixture field (old value → new value), or a fixture that appeared after the initial import. Shown in the UI as "změna".
_Avoid_: Change, Audit, Activity

**Change summary**:
The text sent to the team's WhatsApp group describing the revisions of one import.

### People

**Administrator**:
A user who can sign in to the app. Every user is an administrator; there is no self-registration.
_Avoid_: Member, Subscriber

**Notification subscription**:
An administrator's choice to receive the change summaries of one team. Holds across seasons. Every administrator is subscribed to every team, including teams added later, until they unsubscribe; unsubscribing keeps the date it happened.
_Avoid_: Subscription (unqualified), Calendar subscription

**Import failure notification**:
The email telling an administrator that an import ended as error or aborted. Not tied to any notification subscription: an administrator either receives it for every team or for none. On unless the administrator turns it off.
