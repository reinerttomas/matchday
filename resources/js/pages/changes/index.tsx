import { Head } from '@inertiajs/react';
import { CheckCheck, ChevronDown, History } from 'lucide-react';
import { useState } from 'react';
import { ChangeSummaryDialog } from '@/components/change-summary-dialog';
import Heading from '@/components/heading';
import { Matchup } from '@/components/matchup';
import { NoTeamSeasons } from '@/components/no-team-seasons';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { index } from '@/routes/changes';
import type {
    AddedFixture,
    InitialImport,
    RevisedFixture,
    RevisingImport,
    RevisionHistory,
    RevisionPart,
} from '@/types';

type Props = {
    revisionHistory: RevisionHistory | null;
};

export default function Changes({ revisionHistory }: Props) {
    return (
        <>
            <Head title="Změny" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Změny"
                    description="Co se v rozpisu změnilo, kdy, a jestli už to tým ví."
                />
                {revisionHistory === null ? (
                    <NoTeamSeasons />
                ) : (
                    <>
                        {revisionHistory.imports.length === 0 && (
                            <NoRevisions
                                isImported={
                                    revisionHistory.initialImport !== null
                                }
                            />
                        )}
                        {revisionHistory.imports.map((revisingImport) => (
                            <RevisingImportCard
                                key={revisingImport.id}
                                revisingImport={revisingImport}
                            />
                        ))}
                        {revisionHistory.initialImport !== null && (
                            <InitialImportCard
                                initialImport={revisionHistory.initialImport}
                            />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

Changes.layout = {
    breadcrumbs: [
        {
            title: 'Změny',
            href: index(),
        },
    ],
};

function NoRevisions({ isImported }: { isImported: boolean }) {
    return (
        <Empty className="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <History />
                </EmptyMedia>
                <EmptyTitle>
                    {isImported
                        ? 'Rozpis se od importu nezměnil'
                        : 'Rozpis zatím nebyl stažen'}
                </EmptyTitle>
                <EmptyDescription>
                    {isImported
                        ? 'Jakmile import najde v rozpisu změnu, objeví se tady.'
                        : 'Změny se tu objeví po prvním importu rozpisu.'}
                </EmptyDescription>
            </EmptyHeader>
        </Empty>
    );
}

function RevisingImportCard({
    revisingImport,
}: {
    revisingImport: RevisingImport;
}) {
    return (
        <Card className="gap-4">
            <CardHeader className="flex-row flex-wrap items-center justify-between gap-2">
                <CardTitle className="tabular-nums">
                    {revisingImport.startedAt}
                </CardTitle>
                {revisingImport.notified !== null ? (
                    <Badge
                        variant="outline"
                        className="border-emerald-600/30 bg-emerald-50 text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-950 dark:text-emerald-300"
                    >
                        <CheckCheck />
                        {revisingImport.notified}
                    </Badge>
                ) : (
                    <ChangeSummaryDialog importId={revisingImport.id} />
                )}
            </CardHeader>
            <CardContent>
                <ul className="divide-y">
                    {revisingImport.fixtures.map((fixture) => (
                        <RevisedFixtureItem
                            key={fixture.id}
                            fixture={fixture}
                        />
                    ))}
                </ul>
            </CardContent>
        </Card>
    );
}

function RevisedFixtureItem({ fixture }: { fixture: RevisedFixture }) {
    return (
        <li className="flex flex-col gap-1.5 py-3 first:pt-0 last:pb-0">
            <FixtureHeading fixture={fixture} />
            <ul className="flex flex-col gap-1 text-sm">
                {fixture.revisions.map((parts, position) => (
                    <li key={position} className="break-words">
                        <Revision parts={parts} />
                    </li>
                ))}
            </ul>
        </li>
    );
}

function FixtureHeading({ fixture }: { fixture: AddedFixture }) {
    return (
        <div className="flex flex-wrap items-baseline gap-x-3 gap-y-0.5 text-sm">
            <span className="font-medium tabular-nums">{fixture.day}</span>
            <Matchup parts={fixture.matchup} className="min-w-0" />
        </div>
    );
}

function Revision({ parts }: { parts: RevisionPart[] }) {
    return parts.map((part, position) => {
        switch (part.kind) {
            case 'added':
                return (
                    <Badge
                        key={position}
                        variant="outline"
                        className="border-sky-600/30 bg-sky-50 text-sky-700 dark:border-sky-400/30 dark:bg-sky-950 dark:text-sky-300"
                    >
                        {part.text}
                    </Badge>
                );
            case 'old_value':
                return (
                    <s key={position} className="text-muted-foreground">
                        {part.text}
                    </s>
                );
            case 'new_value':
                return (
                    <span key={position} className="font-medium">
                        {part.text}
                    </span>
                );
            default:
                return (
                    <span key={position} className="text-muted-foreground">
                        {part.text}
                    </span>
                );
        }
    });
}

function InitialImportCard({
    initialImport,
}: {
    initialImport: InitialImport;
}) {
    const [isOpen, setIsOpen] = useState(false);

    return (
        <Collapsible open={isOpen} onOpenChange={setIsOpen} asChild>
            <Card className="gap-4">
                <CardHeader className="flex-row flex-wrap items-center justify-between gap-2">
                    <div className="flex flex-col gap-1.5">
                        <CardTitle>{initialImport.summary}</CardTitle>
                        <CardDescription className="tabular-nums">
                            První import {initialImport.startedAt}, týmu se
                            neoznamuje.
                        </CardDescription>
                    </div>
                    <CollapsibleTrigger asChild>
                        <Button variant="ghost" size="sm">
                            <ChevronDown
                                className={
                                    isOpen
                                        ? 'rotate-180 transition-transform'
                                        : 'transition-transform'
                                }
                            />
                            {isOpen ? 'Skrýt zápasy' : 'Zobrazit zápasy'}
                        </Button>
                    </CollapsibleTrigger>
                </CardHeader>
                <CollapsibleContent>
                    <CardContent>
                        <ul className="divide-y">
                            {initialImport.fixtures.map((fixture) => (
                                <li
                                    key={fixture.id}
                                    className="py-2 first:pt-0 last:pb-0"
                                >
                                    <FixtureHeading fixture={fixture} />
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </CollapsibleContent>
            </Card>
        </Collapsible>
    );
}
