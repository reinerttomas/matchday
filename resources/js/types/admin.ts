import type { FixtureStatus, MatchupPart } from './team-page';

export type SelectableSeason = {
    id: number;
    name: string;
    isCurrent: boolean;
};

export type SelectableTeamSeason = {
    id: number;
    name: string;
    teamSlug: string;
};

export type AdminSelection = {
    season: SelectableSeason | null;
    teamSeason: SelectableTeamSeason | null;
    seasons: SelectableSeason[];
    teamSeasons: SelectableTeamSeason[];
};

export type ImportStatus = 'running' | 'ok' | 'error' | 'aborted';

export type ImportTrigger = 'schedule' | 'manual';

export type ImportHistoryItem = {
    id: number;
    startedOn: string;
    startedAt: string;
    trigger: ImportTrigger;
    status: ImportStatus;
    statusLabel: string;
    reason: string | null;
    duration: string | null;
    fixturesFoundLabel: string | null;
    revisionsCount: number;
    revisionsLabel: string;
};

export type ImportHistoryFilter = 'all' | 'revised' | 'failed';

export type ImportHistory = {
    filter: ImportHistoryFilter;
    allCount: number;
    revisedCount: number;
    failedCount: number;
    imports: Paginated<ImportHistoryItem>;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type FixtureListPeriod = 'upcoming' | 'season';

export type FixtureBadgeKind =
    | 'rescheduled'
    | 'postponed'
    | 'cancelled'
    | 'finished'
    | 'win'
    | 'loss'
    | 'draw'
    | 'revised';

export type FixtureBadge = {
    kind: FixtureBadgeKind;
    label: string;
};

export type FixtureListItem = {
    id: number;
    day: string;
    round: string | null;
    time: string | null;
    isHome: boolean;
    homeTeam: string;
    awayTeam: string;
    venue: string | null;
    status: FixtureStatus;
    badges: FixtureBadge[];
    score: string | null;
    revisions: string[];
};

export type ImportFailure = {
    status: Extract<ImportStatus, 'error' | 'aborted'>;
    reason: string | null;
};

export type FixtureList = {
    sourceUrl: string;
    period: FixtureListPeriod;
    upcomingCount: number;
    seasonCount: number;
    isImported: boolean;
    lastImportFinished: string | null;
    lastImportFailure: ImportFailure | null;
    fixtures: FixtureListItem[];
};

export type RevisionPartKind =
    | 'text'
    | 'field'
    | 'old_value'
    | 'new_value'
    | 'added';

export type RevisionPart = {
    text: string;
    kind: RevisionPartKind;
};

export type AddedFixture = {
    id: number;
    day: string;
    matchup: MatchupPart[];
};

export type RevisedFixture = AddedFixture & {
    revisions: RevisionPart[][];
};

export type RevisingImport = {
    id: number;
    startedOn: string;
    startedAt: string;
    revisionsLabel: string;
    notified: string | null;
    fixtures: RevisedFixture[];
};

export type InitialImport = {
    id: number;
    startedOn: string;
    startedAt: string;
    summary: string;
    fixtures: AddedFixture[];
};

export type RevisionHistoryFilter = 'unsent' | 'all';

export type RevisionHistory = {
    filter: RevisionHistoryFilter;
    unsentCount: number;
    allCount: number;
    imports: RevisingImport[];
    initialImport: InitialImport | null;
};

export type TeamSeasonImportFailure = ImportFailure & {
    statusLabel: string;
};

export type TeamSeasonLastImport = {
    startedAt: string;
    failure: TeamSeasonImportFailure | null;
};

export type TeamSeasonListItem = {
    id: number;
    teamId: number;
    name: string;
    competition: string | null;
    slug: string;
    autoImportEnabled: boolean;
    lastImport: TeamSeasonLastImport | null;
    calendarUrl: string;
    publicPageUrl: string;
    sourceUrl: string;
};

export type SeasonListItem = {
    id: number;
    name: string;
    isCurrent: boolean;
    teamSeasonsCount: number;
};

export type CarryOverTeam = {
    id: number;
    name: string;
    slug: string;
};

export type VenueListItem = {
    id: number;
    name: string;
    address: string | null;
    fixturesCount: number;
};
