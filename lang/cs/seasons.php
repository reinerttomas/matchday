<?php

declare(strict_types=1);

return [

    'created' => 'Sezona :season je vytvořená.',
    'marked_as_current' => 'Sezona :season je aktuální. Kalendáře, veřejné stránky a automatické importy teď používají její týmy.',

    'validation' => [
        'name_required' => 'Zadejte název sezony.',
        'name_format' => 'Název sezony musí být dva po sobě jdoucí roky, např. 2027/28.',
        // :input is filled in by the validator with the name the administrator entered.
        'name_taken' => 'Sezona :input už existuje.',
    ],

];
