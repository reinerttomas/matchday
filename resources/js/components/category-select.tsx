import type { ComponentProps } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { TeamCategory } from '@/types';

/**
 * The required choice of a team's category, sent as the "category" field of the form around it.
 */
export function CategorySelect({
    id,
    categories,
    isInvalid,
    placeholder,
    'data-test': dataTest,
    ...props
}: {
    id: string;
    categories: TeamCategory[];
    isInvalid: boolean;
    placeholder?: string;
    'data-test'?: string;
} & Pick<
    ComponentProps<typeof Select>,
    'value' | 'defaultValue' | 'onValueChange'
>) {
    return (
        <Select name="category" required {...props}>
            <SelectTrigger
                id={id}
                className="w-full"
                aria-invalid={isInvalid}
                data-test={dataTest}
            >
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                {categories.map((category) => (
                    <SelectItem key={category.value} value={category.value}>
                        {category.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
