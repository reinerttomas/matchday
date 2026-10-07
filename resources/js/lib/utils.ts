import type { InertiaLinkProps } from '@inertiajs/react';

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

/**
 * Turns a name into a URL slug like Laravel's `Str::slug()`, so a preview matches the slug the server derives. Matches it for Czech names ("Kutná Hora" → "kutna-hora"); letters `Str::ascii()` transliterates, such as "ß" or "ł", are dropped here.
 */
export function slugify(name: string): string {
    return name
        .normalize('NFD')
        .replace(/\p{M}+/gu, '')
        .replace(/[–—]/g, '-')
        .replace(/_+/g, '-')
        .replaceAll('@', '-at-')
        .toLowerCase()
        .replace(/[^a-z0-9\s-]+/g, '')
        .replace(/[\s-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}
