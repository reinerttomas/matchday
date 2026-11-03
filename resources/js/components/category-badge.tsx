import type { ComponentProps } from 'react';
import { Badge } from '@/components/ui/badge';
import type { TeamCategory } from '@/types';

/**
 * The team's category beside its name, which tells same-named teams apart.
 */
export function CategoryBadge({
    category,
    ...props
}: { category: TeamCategory } & Omit<
    ComponentProps<typeof Badge>,
    'children' | 'variant'
>) {
    return (
        <Badge variant="outline" {...props}>
            {category.label}
        </Badge>
    );
}
