import { router, usePage } from '@inertiajs/react';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { update } from '@/routes/admin-selection';

/**
 * Picks the season and team the admin pages work on; the server remembers the choice and reloads the same page.
 */
export function AdminSelectionSwitcher() {
    const { adminSelection } = usePage().props;

    if (adminSelection === null || adminSelection.season === null) {
        return null;
    }

    const { season, teamSeason, seasons, teamSeasons } = adminSelection;

    const select = (seasonId: number, teamSeasonId: number | null) => {
        router.post(
            update.url(),
            { season_id: seasonId, team_season_id: teamSeasonId },
            { preserveScroll: true },
        );
    };

    return (
        <div className="flex flex-col gap-2 px-2 pb-2 group-data-[collapsible=icon]:hidden">
            <div className="flex flex-col gap-1">
                <Label
                    htmlFor="admin-selection-season"
                    className="text-xs text-muted-foreground"
                >
                    Sezona
                </Label>
                <Select
                    value={String(season.id)}
                    onValueChange={(value) => select(Number(value), null)}
                >
                    <SelectTrigger
                        id="admin-selection-season"
                        size="sm"
                        className="w-full bg-background"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {seasons.map((option) => (
                            <SelectItem
                                key={option.id}
                                value={String(option.id)}
                            >
                                {option.name}
                                {option.isCurrent && (
                                    <span className="text-muted-foreground">
                                        aktuální
                                    </span>
                                )}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="flex flex-col gap-1">
                <Label
                    htmlFor="admin-selection-team-season"
                    className="text-xs text-muted-foreground"
                >
                    Tým
                </Label>
                <Select
                    value={teamSeason === null ? '' : String(teamSeason.id)}
                    onValueChange={(value) => select(season.id, Number(value))}
                    disabled={teamSeasons.length === 0}
                >
                    <SelectTrigger
                        id="admin-selection-team-season"
                        size="sm"
                        className="w-full bg-background"
                    >
                        <SelectValue placeholder="Žádné týmy" />
                    </SelectTrigger>
                    <SelectContent>
                        {teamSeasons.map((option) => (
                            <SelectItem
                                key={option.id}
                                value={String(option.id)}
                            >
                                {option.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        </div>
    );
}
