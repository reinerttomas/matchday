<?php

declare(strict_types=1);

return [

    'created' => 'Tým :team_season je přidaný do sezony :season. Jeho rozpis se právě stahuje.',

    'auto_import' => [
        'enabled' => 'Automatický import týmu :team_season je zapnutý.',
        'disabled' => 'Automatický import týmu :team_season je vypnutý. Kalendář dál ukazuje poslední stažený rozpis.',
    ],

    'validation' => [
        'federation_team_page_url_required' => 'Zadejte adresu stránky týmu na ceskyflorbal.cz.',
        'federation_team_page_url_format' => 'Adresa musí vést na stránku týmu na ceskyflorbal.cz, např. https://www.ceskyflorbal.cz/team/detail/overview/45019.',
        'federation_team_page_url_taken' => 'Tato stránka už patří týmu :team_season.',
        'team_unknown' => 'Vybraný tým neexistuje.',
        'team_in_season' => 'Tento tým už v sezoně :season je.',
    ],

];
