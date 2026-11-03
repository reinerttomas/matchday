<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The age and gender group a team plays in; it belongs to the team and holds across seasons, while the competition can change.
 *
 * The stored values are the slugs of the Czech labels, as a new team's slug is made of its name and its category's label.
 *
 * @phpstan-type CategoryProps array{value: string, label: string}
 */
enum Category: string
{
    case Men = 'muzi';
    case Women = 'zeny';
    case Juniors = 'juniori';
    case JuniorWomen = 'juniorky';
    case Youths = 'dorostenci';
    case YouthWomen = 'dorostenky';
    case OlderBoys = 'starsi-zaci';
    case OlderGirls = 'starsi-zakyne';
    case YoungerBoys = 'mladsi-zaci';
    case YoungerGirls = 'mladsi-zakyne';
    case Elves = 'elevove';
    case Preparatory = 'pripravka';

    /**
     * Name the category the way the badge and the calendar name show it, such as "ženy".
     */
    public function label(): string
    {
        return __("teams.categories.{$this->value}");
    }

    /**
     * Describe the category for a page, which sends the value back and shows the label in a select or a badge.
     *
     * @return CategoryProps
     */
    public function props(): array
    {
        return ['value' => $this->value, 'label' => $this->label()];
    }
}
