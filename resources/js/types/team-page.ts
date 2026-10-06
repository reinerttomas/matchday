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

export type MatchDay = {
    date: string;
    heading: string;
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
