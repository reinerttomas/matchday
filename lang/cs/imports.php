<?php

declare(strict_types=1);

return [

    'statuses' => [
        'running' => 'Probíhá',
        'ok' => 'OK',
        'error' => 'Chyba',
        'aborted' => 'Přerušeno',
    ],

    'change_summary' => [
        'header' => '📅 Změny v rozpisu :team_season',
        'bullet' => '• :day :home – :away: :revisions',
        'calendar' => 'Kalendář: :url',

        'added' => 'nový zápas v rozpisu, :details',

        'statuses' => [
            'scheduled' => 'znovu naplánováno',
            'reappeared' => 'znovu v rozpisu',
            'postponed' => 'odloženo, nový termín zatím není známý',
            'finished' => 'odehráno',
            'finished_with_score' => 'odehráno :score',
            'cancelled' => 'zrušeno',
        ],

        'score' => [
            'set' => 'skóre :score',
            'removed' => 'skóre odstraněno (původně :score_before)',
            'corrected' => 'opravené skóre :score (původně :score_before)',
        ],

        'date' => 'přeloženo, nový termín :date (původně :date_before)',

        'time' => [
            'tbd' => 'čas TBD',
            'back_to_tbd' => 'čas TBD (původně :time_before)',
            'set' => 'čas doplněn :time',
            'changed' => 'nový čas :time (původně :time_before)',
        ],

        'venue' => [
            'set' => 'hala doplněna :venue',
            'removed' => 'hala neuvedena (původně :venue_before)',
            'changed' => 'nová hala :venue (původně :venue_before)',
        ],

        'rescheduled' => 'nově dohrávka',
    ],

    'errors' => [
        'http' => [
            'forbidden' => 'HTTP :status – požadavek zablokován',
            'not_found' => 'HTTP :status – stránka nenalezena',
            'too_many_requests' => 'HTTP :status – příliš mnoho požadavků',
            'server_error' => 'HTTP :status – chyba serveru ceskyflorbal.cz',
            'unexpected' => 'HTTP :status – neočekávaná odpověď',
        ],
        'connection' => 'Nepodařilo se spojit s ceskyflorbal.cz',
        'unreadable_fixture_list' => 'Stránku s rozpisem zápasů nelze přečíst: :message',
        'season_mismatch' => 'Rozpis na stránce je ze sezony :page_season, ne :season',
        'fixtures_found' => '{1} Parser vrátil :count zápas|[2,4] Parser vrátil :count zápasy|[0,*] Parser vrátil :count zápasů',
        'fixtures_found_with_last_import' => '{1} Parser vrátil :count zápas (minule :last_import_count)|[2,4] Parser vrátil :count zápasy (minule :last_import_count)|[0,*] Parser vrátil :count zápasů (minule :last_import_count)',
        'unexpected' => 'Neočekávaná chyba při importu',
        'unfinished' => 'Import nebyl dokončen.',
    ],

    'notifications' => [
        'failed' => [
            'error' => [
                'subject' => 'Import rozpisu selhal: :team_season',
                'heading' => 'Import rozpisu selhal',
                'body' => 'Import rozpisu zápasů týmu **:team_season** z ceskyflorbal.cz skončil chybou. Uložený rozpis zápasů zůstal beze změny.',
            ],
            'aborted' => [
                'subject' => 'Import rozpisu přerušen: :team_season',
                'heading' => 'Import rozpisu byl přerušen',
                'body' => 'Import rozpisu zápasů týmu **:team_season** z ceskyflorbal.cz byl přerušen. Uložený rozpis zápasů zůstal beze změny.',
            ],
            'reason' => '**Důvod:** :reason',
            'button' => 'Otevřít rozpis na ceskyflorbal.cz',
        ],

        'fixture_list_revised' => [
            'subject' => 'Změny v rozpisu: :team_season',
            'heading' => 'Rozpis zápasů se změnil',
            'body' => 'Import rozpisu zápasů týmu **:team_season** z ceskyflorbal.cz našel změny. Tady je souhrn pro WhatsApp skupinu týmu:',
            'button' => 'Otevřít WhatsApp',
        ],
    ],

    'fixture_list' => [
        'last_finished' => 'Naposledy staženo :ago',
    ],

    'changes' => [
        'notified' => 'Odesláno týmu :date v :time',
        'initial_import' => '{1} :count zápas přidán do rozpisu|[2,4] :count zápasy přidány do rozpisu|[0,*] :count zápasů přidáno do rozpisu',
        'marked_as_sent' => 'Souhrn změn je označený jako odeslaný.',
    ],

    'manual_import' => [
        'queued' => 'Stahování rozpisu bylo spuštěno.',
    ],

    'team_page' => [
        'last_import' => ':date v :time',
    ],

    'history' => [
        'started_at' => ':date :time',
        'fixtures_found' => '{1} :count zápas|[2,4] :count zápasy|[0,*] :count zápasů',
        'revisions' => '{1} :count změna|[2,4] :count změny|[0,*] :count změn',
        'duration' => [
            'seconds' => ':seconds s',
            'minutes' => ':minutes min :seconds s',
        ],
    ],

];
