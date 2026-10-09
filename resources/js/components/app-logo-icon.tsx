import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="17 20 66 60"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path d="M17 20 L50 41 L83 20 L83 80 L66 70 L66 50 L50 61 L34 50 L34 70 L17 80 Z" />
        </svg>
    );
}
