<?php

declare(strict_types=1);

return [

    'statuses' => [
        'scheduled' => 'Naplánováno',
        'postponed' => 'Odloženo',
        'finished' => 'Odehráno',
        'cancelled' => 'Zrušeno',
    ],

    'day' => ':weekday :date',
    'matchup' => ':home – :away',
    'score' => ':home_score::away_score',

    'rounds' => [
        'default' => ':round. kolo',
        'rescheduled' => 'dohrávka :round. kola',
    ],

    'calendar' => [
        'titles' => [
            'default' => ':home – :away',
            'tbd_time' => ':home – :away (čas TBD)',
            'finished' => ':home – :away :home_score::away_score',
            'postponed' => 'ODLOŽENO: :home – :away',
            'cancelled' => 'ZRUŠENO: :home – :away',
        ],
        'location' => ':venue, :address',
        'match_detail' => 'Zápas na ceskyflorbal.cz: :url',
    ],

    'team_page' => [
        'match_day' => ':weekday :date',
        'match_day_with_year' => ':weekday :date :year',
    ],

    'list' => [
        'badges' => [
            'rescheduled' => 'Dohrávka',
            'win' => 'Výhra',
            'loss' => 'Prohra',
            'draw' => 'Remíza',
            'revised' => 'Změněno',
        ],
    ],

];
