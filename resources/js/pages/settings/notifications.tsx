import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSeparator,
    FieldSet,
} from '@/components/ui/field';
import { Switch } from '@/components/ui/switch';
import { update as updateImportFailureNotifications } from '@/routes/import-failure-notifications';
import { update as updateNotificationSubscription } from '@/routes/notification-subscriptions';
import { edit as editNotifications } from '@/routes/notifications';

type Team = {
    id: number;
    name: string;
    competition: string | null;
    subscribed: boolean;
};

type Props = {
    notifyImportFailures: boolean;
    teams: Team[];
};

export default function Notifications({ notifyImportFailures, teams }: Props) {
    // Each switch flips at once; Inertia puts it back if the server refuses the change.
    function toggleImportFailures(isOn: boolean): void {
        router
            .optimistic<Props>(() => ({ notifyImportFailures: isOn }))
            .put(
                updateImportFailureNotifications.url(),
                { notify_import_failures: isOn },
                { preserveScroll: true },
            );
    }

    function toggleTeam(team: Team, isSubscribed: boolean): void {
        router
            .optimistic<Props>((props) => ({
                teams: props.teams.map((item) =>
                    item.id === team.id
                        ? { ...item, subscribed: isSubscribed }
                        : item,
                ),
            }))
            .put(
                updateNotificationSubscription.url(team.id),
                { subscribed: isSubscribed },
                { preserveScroll: true },
            );
    }

    return (
        <>
            <Head title="Upozornění" />

            <h1 className="sr-only">Upozornění</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Upozornění"
                    description="Vyberte, jaké e-maily vám mají chodit"
                />

                <FieldGroup>
                    <FieldSet>
                        <FieldLegend>Chyby importu</FieldLegend>
                        <Field orientation="horizontal">
                            <FieldContent>
                                <FieldLabel htmlFor="import-failures">
                                    Posílat e-mail, když import selže
                                </FieldLabel>
                                <FieldDescription>
                                    Platí pro všechny týmy, i ty, jejichž změny
                                    neodebíráte.
                                </FieldDescription>
                            </FieldContent>
                            <Switch
                                id="import-failures"
                                checked={notifyImportFailures}
                                onCheckedChange={toggleImportFailures}
                            />
                        </Field>
                    </FieldSet>

                    <FieldSeparator />

                    <FieldSet>
                        <FieldLegend>Změny v rozpisu zápasů</FieldLegend>
                        <FieldDescription>
                            Shrnutí změn, které pak pošlete do WhatsAppu týmu.
                        </FieldDescription>
                        {teams.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Aktuální sezona nemá žádné týmy.
                            </p>
                        ) : (
                            <FieldGroup className="gap-4">
                                {teams.map((team) => (
                                    <Field
                                        key={team.id}
                                        orientation="horizontal"
                                    >
                                        <FieldContent>
                                            <FieldLabel
                                                htmlFor={`team-${team.id}`}
                                            >
                                                {team.name}
                                            </FieldLabel>
                                            {team.competition && (
                                                <FieldDescription>
                                                    {team.competition}
                                                </FieldDescription>
                                            )}
                                        </FieldContent>
                                        <Switch
                                            id={`team-${team.id}`}
                                            checked={team.subscribed}
                                            onCheckedChange={(isSubscribed) =>
                                                toggleTeam(team, isSubscribed)
                                            }
                                        />
                                    </Field>
                                ))}
                            </FieldGroup>
                        )}
                    </FieldSet>
                </FieldGroup>
            </div>
        </>
    );
}

Notifications.layout = {
    breadcrumbs: [
        {
            title: 'Upozornění',
            href: editNotifications(),
        },
    ],
};
