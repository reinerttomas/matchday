export type FixtureStatus =
    | 'scheduled'
    | 'postponed'
    | 'finished'
    | 'cancelled';

export type MatchupPart = {
    text: string;
    isOurTeam: boolean;
};

export type TeamPageFixture = {
    id: number;
    time: string | null;
    matchup: MatchupPart[];
    round: string | null;
    status: FixtureStatus;
    statusLabel: string | null;
    score: string | null;
    venue: string | null;
};

export type DateTile = {
    weekday: string;
    day: string;
    month: string;
};

export type MatchDay = {
    date: string;
    heading: string;
    dateTile: DateTile;
    relativeDay: string;
    venue: string | null;
    fixtures: TeamPageFixture[];
};

export type TeamPageTeamSeason = {
    name: string;
    competition: string | null;
    sourceUrl: string;
    lastImportedAt: string | null;
    matchDays: MatchDay[];
};

export type TeamPageAction =
    | 'page_view'
    | 'google'
    | 'webcal'
    | 'outlook'
    | 'copy_address'
    | 'help_open'
    | 'escape_intent'
    | 'escape_safari'
    | 'copy_page_link';

export type CalendarLinks = {
    address: string;
    google: string;
    webcal: string;
    outlook: string;
};
