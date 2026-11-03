<?php

declare(strict_types=1);

return [

    'categories' => [
        'muzi' => 'muži',
        'zeny' => 'ženy',
        'juniori' => 'junioři',
        'juniorky' => 'juniorky',
        'dorostenci' => 'dorostenci',
        'dorostenky' => 'dorostenky',
        'starsi-zaci' => 'starší žáci',
        'starsi-zakyne' => 'starší žákyně',
        'mladsi-zaci' => 'mladší žáci',
        'mladsi-zakyne' => 'mladší žákyně',
        'elevove' => 'elévové',
        'pripravka' => 'přípravka',
    ],

    'renamed' => 'Tým je přejmenovaný na :team. Adresa kalendáře zůstává stejná.',

    'validation' => [
        'name_required' => 'Zadejte název týmu.',
        'name_format' => 'Název týmu musí být text.',
        // :max is filled in by the validator.
        'name_too_long' => 'Název týmu může mít nejvýše :max znaků.',
        'name_without_slug' => 'Název týmu musí obsahovat aspoň jedno písmeno nebo číslici.',
        'name_taken' => 'Jiný tým už má stejnou adresu kalendáře (:slug). Zvolte jiný název.',
        'category_invalid' => 'Vyberte kategorii ze seznamu.',
    ],

];
