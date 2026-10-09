<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What a player did on the public team page while subscribing to the team's calendar.
 */
enum TeamPageAction: string
{
    case PageView = 'page_view';
    case Google = 'google';
    case Webcal = 'webcal';
    case Outlook = 'outlook';
    case CopyAddress = 'copy_address';
    case HelpOpen = 'help_open';
    case OtherOptionsOpen = 'other_options_open';
    case EscapeIntent = 'escape_intent';
    case CopyPageLink = 'copy_page_link';
}
