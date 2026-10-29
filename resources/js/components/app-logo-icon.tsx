import type { SVGAttributes } from 'react';

/**
 * TaskFlow mark: three Kanban columns of uneven height.
 * Inherits its colour from `fill`, so callers style it with `fill-current`.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <rect x="2" y="3" width="5.5" height="18" rx="1.75" />
            <rect x="9.25" y="3" width="5.5" height="11" rx="1.75" />
            <rect x="16.5" y="3" width="5.5" height="14.5" rx="1.75" />
        </svg>
    );
}
