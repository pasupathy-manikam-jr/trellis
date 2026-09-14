import { SVGAttributes } from 'react';

/** A garden trellis: a diamond lattice, the structure a course gives a learner. */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.75}
            strokeLinecap="round"
            strokeLinejoin="round"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path d="M12 2 L22 12 L12 22 L2 12 Z" />
            <path d="M12 2 V22" />
            <path d="M2 12 H22" />
        </svg>
    );
}
