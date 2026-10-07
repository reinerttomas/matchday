<?php

declare(strict_types=1);

return [

    'validation' => [
        'slug_required' => 'Zadejte slug.',
        'slug_format' => 'Slug smí obsahovat jen malá písmena bez diakritiky a číslice, oddělené pomlčkou, např. kutna-hora-b.',
        // :max and :input are filled in by the validator.
        'slug_too_long' => 'Slug může mít nejvýše :max znaků.',
        'slug_taken' => 'Slug :input už používá jiný tým.',
    ],

];
