<?php

declare(strict_types=1);

namespace App\Services\Ceskyflorbal;

use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\Str;
use UnexpectedValueException;

final readonly class MatchDetailParser
{
    /**
     * Parse the venue from a fixture's match detail page ("Informace" tab) on ceskyflorbal.cz.
     *
     * @throws UnexpectedValueException when the page shows no venue
     */
    public function parse(string $html): MatchDetailPageData
    {
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $venueLink = $document->querySelector('.MatchCenter-placeView');

        if ($venueLink === null || preg_match('/^\/arena\/detail\/default\/(\d+)$/', (string) $venueLink->getAttribute('href'), $matches) !== 1) {
            throw new UnexpectedValueException('Match detail page has no venue link.');
        }

        $venueName = Str::squish($venueLink->querySelector('h3')->textContent ?? '');

        if ($venueName === '') {
            throw new UnexpectedValueException('Match detail page has no venue name.');
        }

        return new MatchDetailPageData(
            venueExternalId: (int) $matches[1],
            venueName: $venueName,
            venueAddress: $this->address($document),
        );
    }

    /**
     * Read the "Adresa:" row of the venue table, whose lines are separated by <br>, as one line joined by commas.
     */
    private function address(HTMLDocument $document): ?string
    {
        foreach ($document->querySelectorAll('.MatchCenter-placeTable tr') as $row) {
            if (Str::squish($row->querySelector('td')->textContent ?? '') === 'Adresa:') {
                $cell = $row->querySelector('td:nth-child(2)');

                return $cell === null ? null : $this->linesJoinedByCommas($cell);
            }
        }

        return null;
    }

    /**
     * Join the lines of an element separated by <br> with commas.
     */
    private function linesJoinedByCommas(Element $element): ?string
    {
        $lines = [''];

        foreach ($element->childNodes as $node) {
            if ($node instanceof Element && $node->localName === 'br') {
                $lines[] = '';
            } else {
                $lines[array_key_last($lines)] .= $node->textContent;
            }
        }

        $lines = array_filter(array_map(Str::squish(...), $lines), fn (string $line): bool => $line !== '');

        return $lines === [] ? null : implode(', ', $lines);
    }
}
