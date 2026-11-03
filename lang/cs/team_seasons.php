<?php

declare(strict_types=1);

return [

    'created' => 'Tým :team_season je přidaný do sezony :season. Jeho rozpis se právě stahuje.',

    'auto_import' => [
        'enabled' => 'Automatický import týmu :team_season je zapnutý.',
        'disabled' => 'Automatický import týmu :team_season je vypnutý. Kalendář dál ukazuje poslední stažený rozpis.',
    ],

    'lookup' => [
        'unavailable' => 'Český florbal teď neodpovídá. Název ani soutěž se nedoplní, vyplňte je ručně.',
    ],

    'validation' => [
        'federation_team_page_url_required' => 'Zadejte adresu stránky týmu na ceskyflorbal.cz.',
        'federation_team_page_url_format' => 'Adresa musí vést na stránku týmu na ceskyflorbal.cz, např. https://www.ceskyflorbal.cz/team/detail/overview/45019.',
        'federation_team_page_url_taken' => 'Tato stránka už patří týmu :team_season.',
        'federation_team_page_url_other_season' => 'Stránka patří sezoně :page_season, tým ale přidáváte do sezony :season.',
        'team_unknown' => 'Vybraný tým neexistuje.',
        'team_in_season' => 'Tento tým už v sezoně :season je.',
    ],

];
