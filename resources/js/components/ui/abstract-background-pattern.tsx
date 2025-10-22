import { useId } from 'react';

interface AbstractBackgroundPatternProps {
    className?: string;
    opacity?: number;
    corner?: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';
}

export function AbstractBackgroundPattern({ className, opacity = 0.08, corner = 'top-right' }: AbstractBackgroundPatternProps) {
    const patternId = useId();
    const gradientId = useId();
    const colorGradient1Id = useId();
    const colorGradient2Id = useId();
    const colorGradient3Id = useId();
    const colorGradient4Id = useId();
    const blurFilterId = useId();

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
            <svg fill="none" viewBox="0 0 1000 800" width="1000" height="800" style={{ transform: getCornerTransform(), transformOrigin: 'center' }}>
                <defs>
                    <pattern id={patternId} x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                        <g transform="scale(1.5)">
                            <polygon
                                points="20,5 30,15 30,25 20,35 10,25 10,15"
                                fill="none"
                                stroke="#8b5cf6"
                                strokeWidth="0.8"
                                opacity={opacity * 1.2}
                            />

                            <circle cx="6" cy="34" r="1.5" fill="#ec4899" opacity={opacity * 1.0} />
                            <circle cx="34" cy="6" r="1.5" fill="#06b6d4" opacity={opacity * 1.0} />

                            <line x1="0" y1="20" x2="40" y2="20" stroke="#a78bfa" strokeWidth="0.3" opacity={opacity * 0.5} />
                            <line x1="20" y1="0" x2="20" y2="40" stroke="#a78bfa" strokeWidth="0.3" opacity={opacity * 0.5} />

                            <polygon points="2,2 6,2 4,6" fill="#10b981" opacity={opacity * 0.6} />
                            <polygon points="34,34 38,34 36,38" fill="#f59e0b" opacity={opacity * 0.6} />
                        </g>
                    </pattern>

                    <radialGradient id={gradientId} cx="100%" cy="100%" r="140%">
                        <stop offset="0%" stopColor="white" stopOpacity="1" />
                        <stop offset="50%" stopColor="white" stopOpacity="0.7" />
                        <stop offset="100%" stopColor="white" stopOpacity="0" />
                    </radialGradient>

                    <radialGradient id={colorGradient1Id} cx="15%" cy="20%" r="40%">
                        <stop offset="0%" stopColor="#6366f1" stopOpacity="0.35" />
                        <stop offset="40%" stopColor="#8b5cf6" stopOpacity="0.18" />
                        <stop offset="100%" stopColor="#8b5cf6" stopOpacity="0" />
                    </radialGradient>

                    <radialGradient id={colorGradient2Id} cx="85%" cy="25%" r="42%">
                        <stop offset="0%" stopColor="#ec4899" stopOpacity="0.32" />
                        <stop offset="40%" stopColor="#f43f5e" stopOpacity="0.16" />
                        <stop offset="100%" stopColor="#f43f5e" stopOpacity="0" />
                    </radialGradient>

                    <radialGradient id={colorGradient3Id} cx="75%" cy="75%" r="45%">
                        <stop offset="0%" stopColor="#0ea5e9" stopOpacity="0.33" />
                        <stop offset="40%" stopColor="#06b6d4" stopOpacity="0.17" />
                        <stop offset="100%" stopColor="#06b6d4" stopOpacity="0" />
                    </radialGradient>

                    <radialGradient id={colorGradient4Id} cx="20%" cy="80%" r="38%">
                        <stop offset="0%" stopColor="#10b981" stopOpacity="0.30" />
                        <stop offset="40%" stopColor="#14b8a6" stopOpacity="0.15" />
                        <stop offset="100%" stopColor="#14b8a6" stopOpacity="0" />
                    </radialGradient>

                    <filter id={blurFilterId}>
                        <feGaussianBlur in="SourceGraphic" stdDeviation="80" />
                    </filter>

                    <mask id="fadeMask">
                        <rect width="100%" height="100%" fill={`url(#${gradientId})`} />
                    </mask>
                </defs>

                <g mask="url(#fadeMask)">
                    <rect width="100%" height="100%" fill={`url(#${patternId})`} />

                    <g filter={`url(#${blurFilterId})`} style={{ mixBlendMode: 'overlay' }}>
                        <rect width="100%" height="100%" fill={`url(#${colorGradient1Id})`} />
                        <rect width="100%" height="100%" fill={`url(#${colorGradient2Id})`} />
                        <rect width="100%" height="100%" fill={`url(#${colorGradient3Id})`} />
                        <rect width="100%" height="100%" fill={`url(#${colorGradient4Id})`} />
                    </g>
                </g>
            </svg>
        </div>
    );
}
