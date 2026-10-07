<?php

declare(strict_types=1);

return [

    'fields' => [
        'date' => 'Datum',
        'time' => 'Čas',
        'venue' => 'Hala',
        'status' => 'Stav',
        'is_rescheduled' => 'Dohrávka',
        'home_score' => 'Skóre domácích',
        'away_score' => 'Skóre hostů',
    ],

    'values' => [
        'tbd' => 'TBD',
        'empty' => '–',
        'yes' => 'ano',
        'no' => 'ne',
    ],

    'field_change' => ':field: :old_value → :new_value',
    'fixture_added' => 'Nový zápas v rozpisu',

];
