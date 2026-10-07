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
    startedAt: string;
    trigger: ImportTrigger;
    triggerLabel: string;
    status: ImportStatus;
    statusLabel: string;
    reason: string | null;
    duration: string | null;
    fixturesFound: number | null;
    revisionsCount: number;
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
    time: string | null;
    matchup: MatchupPart[];
    venue: string | null;
    status: FixtureStatus;
    badges: FixtureBadge[];
    score: string | null;
    revisions: string[];
};

export type FixtureListMonth = {
    month: string;
    heading: string;
    fixtures: FixtureListItem[];
};

export type ImportFailure = {
    status: Extract<ImportStatus, 'error' | 'aborted'>;
    reason: string | null;
};

export type FixtureList = {
    period: FixtureListPeriod;
    upcomingCount: number;
    seasonCount: number;
    isImported: boolean;
    lastImportFailure: ImportFailure | null;
    months: FixtureListMonth[];
};
