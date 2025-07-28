import { useId } from 'react';

interface AbstractBackgroundPatternProps {
    className?: string;
    opacity?: number;
    corner?: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';
}

export function AbstractBackgroundPattern({
                                              className,
                                              opacity = 0.08,
                                              corner = 'top-right'
                                          }: AbstractBackgroundPatternProps) {
    const patternId = useId();
    const gradientId = useId();

    const getCornerTransform = () => {
        switch (corner) {
            case 'top-left':
                return 'rotate(-15)';
            case 'top-right':
                return 'rotate(15)';
            case 'bottom-left':
                return 'rotate(15)';
            case 'bottom-right':
                return 'rotate(-15)';
            default:
                return 'rotate(15)';
        }
    };

    return (
        <div className={className}>
            <svg
                fill="none"
                viewBox="0 0 1000 800"
                width="1000"
                height="800"
                style={{ transform: getCornerTransform(), transformOrigin: 'center' }}
            >
                <defs>
                    <pattern
                        id={patternId}
                        x="0"
                        y="0"
                        width="40"
                        height="40"
                        patternUnits="userSpaceOnUse"
                    >
                        <g transform="scale(1.5)">
                            <polygon
                                points="20,5 30,15 30,25 20,35 10,25 10,15"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="0.8"
                                opacity={opacity}
                            />

                            <circle
                                cx="6"
                                cy="34"
                                r="1.5"
                                fill="currentColor"
                                opacity={opacity * 0.8}
                            />
                            <circle
                                cx="34"
                                cy="6"
                                r="1.5"
                                fill="currentColor"
                                opacity={opacity * 0.8}
                            />

                            <line
                                x1="0"
                                y1="20"
                                x2="40"
                                y2="20"
                                stroke="currentColor"
                                strokeWidth="0.3"
                                opacity={opacity * 0.4}
                            />
                            <line
                                x1="20"
                                y1="0"
                                x2="20"
                                y2="40"
                                stroke="currentColor"
                                strokeWidth="0.3"
                                opacity={opacity * 0.4}
                            />

                            <polygon
                                points="2,2 6,2 4,6"
                                fill="currentColor"
                                opacity={opacity * 0.5}
                            />
                            <polygon
                                points="34,34 38,34 36,38"
                                fill="currentColor"
                                opacity={opacity * 0.5}
                            />
                        </g>
                    </pattern>

                    <radialGradient id={gradientId} cx="100%" cy="100%" r="140%">
                        <stop offset="0%" stopColor="white" stopOpacity="1" />
                        <stop offset="50%" stopColor="white" stopOpacity="0.7" />
                        <stop offset="100%" stopColor="white" stopOpacity="0" />
                    </radialGradient>

                    <mask id="fadeMask">
                        <rect width="100%" height="100%" fill={`url(#${gradientId})`} />
                    </mask>
                </defs>

                <g mask="url(#fadeMask)">
                    <rect width="100%" height="100%" fill={`url(#${patternId})`} />
                </g>
            </svg>
        </div>
    );
}
