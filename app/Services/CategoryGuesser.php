<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Category;
use Illuminate\Support\Str;

/**
 * Guesses a team's category from the name of the competition it plays, such as "1. liga žen" → ženy.
 *
 * The federation names the category only in the competition's wording, in an inflected Czech form, so the guess looks for those words.
 * Senior men's competitions usually carry no category word at all ("Národní liga", "Superliga"), which is why no match means men.
 */
final readonly class CategoryGuesser
{
    /**
     * The words that name each category in a competition name, without diacritics and lowercase, as the forms competition names use.
     *
     * @var array<value-of<Category>, list<string>>
     */
    private const array WORDS = [
        'zeny' => ['zen', 'zeny'],
        'juniori' => ['junioru', 'juniori'],
        'juniorky' => ['juniorek', 'juniorky'],
        'dorostenci' => ['dorostencu', 'dorostenci'],
        'dorostenky' => ['dorostenek', 'dorostenky'],
        'starsi-zaci' => ['starsich zaku', 'starsi zaci'],
        'starsi-zakyne' => ['starsich zakyn', 'starsi zakyne'],
        'mladsi-zaci' => ['mladsich zaku', 'mladsi zaci'],
        'mladsi-zakyne' => ['mladsich zakyn', 'mladsi zakyne'],
        'elevove' => ['elevu', 'elevove'],
        'pripravka' => ['pripravek', 'pripravky', 'pripravka'],
    ];

    /**
     * Guess the category of a team playing the given competition, men when the name carries no category word.
     */
    public function guess(string $competitionName): Category
    {
        $words = Str::lower(Str::ascii($competitionName));

        foreach (self::WORDS as $category => $categoryWords) {
            foreach ($categoryWords as $categoryWord) {
                if (preg_match('/\b'.preg_quote($categoryWord, '/').'\b/', $words) === 1) {
                    return Category::from($category);
                }
            }
        }

        return Category::Men;
    }
}
