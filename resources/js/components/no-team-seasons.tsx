import { usePage } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyContent,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';

/**
 * Shown by the team pages while there is no season or the selected season has no team seasons, pointing the administrator to Sezony or Týmy.
 */
export function NoTeamSeasons() {
    const { adminSelection } = usePage().props;
    const season = adminSelection?.season ?? null;

    return (
        <Empty className="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <Users />
                </EmptyMedia>
                <EmptyTitle>
                    {season === null
                        ? 'Zatím nejsou žádné sezony'
                        : `V sezoně ${season.name} zatím nejsou žádné týmy`}
                </EmptyTitle>
            </EmptyHeader>
            <EmptyContent>
                {/* Sezony and Týmy have no pages yet; the button links to them once their routes exist. */}
                <Button disabled>
                    {season === null ? 'Přejít na Sezony' : 'Přejít na Týmy'}
                </Button>
            </EmptyContent>
        </Empty>
    );
}
