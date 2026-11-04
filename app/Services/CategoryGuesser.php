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
     * Guess the category of a team playing the given competition, men when the name carries no category word.
     */
    public function guess(string $competitionName): Category
    {
        $words = Str::lower(Str::ascii($competitionName));

        foreach (Category::cases() as $category) {
            foreach ($this->words($category) as $categoryWord) {
                if (Str::isMatch('/\b'.preg_quote($categoryWord, '/').'\b/', $words)) {
                    return $category;
                }
            }
        }

        return Category::Men;
    }

    /**
     * The words that name the category in a competition name, without diacritics and lowercase, as the forms competition names use.
     *
     * @return list<string>
     */
    private function words(Category $category): array
    {
        return match ($category) {
            Category::Men => [],
            Category::Women => ['zen', 'zeny'],
            Category::Juniors => ['junioru', 'juniori'],
            Category::JuniorWomen => ['juniorek', 'juniorky'],
            Category::Youths => ['dorostencu', 'dorostenci'],
            Category::YouthWomen => ['dorostenek', 'dorostenky'],
            Category::OlderBoys => ['starsich zaku', 'starsi zaci'],
            Category::OlderGirls => ['starsich zakyn', 'starsi zakyne'],
            Category::YoungerBoys => ['mladsich zaku', 'mladsi zaci'],
            Category::YoungerGirls => ['mladsich zakyn', 'mladsi zakyne'],
            Category::Elves => ['elevu', 'elevove'],
            Category::Preparatory => ['pripravek', 'pripravky', 'pripravka'],
        };
    }
}
