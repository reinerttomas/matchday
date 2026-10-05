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
A playing year such as 2026/27, created by the administrator. Exactly one season is current; the current season decides what calendars show and which teams are imported automatically.

**Team**:
One of our teams that the app publishes a calendar for. Its slug and calendar address persist across seasons.
_Avoid_: Tracked team

**Team season**:
A team's participation in one season: the federation's team ID for that season, the team's name and competition that season, and the address of its fixture list.

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
