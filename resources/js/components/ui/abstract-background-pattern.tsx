import { useId } from 'react';

interface AbstractBackgroundPatternProps {
    className?: string;
    opacity?: number;
    showColors?: boolean;
}

export function AbstractBackgroundPattern({ className, opacity = 0.08, showColors = true }: AbstractBackgroundPatternProps) {
    const patternId = useId();
    const maskId = useId();
    const colorGradient1Id = useId();
    const colorGradient2Id = useId();
    const colorGradient3Id = useId();
    const colorGradient4Id = useId();
    const colorGradient5Id = useId();
    const blurFilterId = useId();

    return (
        <div className={className}>
            <svg
                fill="none"
                width="100%"
                height="100%"
                style={{
                    display: 'block',
                    position: 'absolute',
                    bottom: 0,
                    width: '100%',
                    height: '100%',
                }}
            >
                <defs>
                    <pattern id={patternId} x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                        <g transform="scale(1.5)">
                            <polygon
                                points="20,5 30,15 30,25 20,35 10,25 10,15"
                                fill="none"
                                stroke="#a78bfa"
                                strokeWidth="0.8"
                                opacity={opacity * 0.8}
                            />
                            <circle cx="6" cy="34" r="1.5" fill="#c4b5fd" opacity={opacity * 0.7} />
                            <circle cx="34" cy="6" r="1.5" fill="#c4b5fd" opacity={opacity * 0.7} />
                            <line x1="0" y1="20" x2="40" y2="20" stroke="#c4b5fd" strokeWidth="0.3" opacity={opacity * 0.3} />
                            <line x1="20" y1="0" x2="20" y2="40" stroke="#c4b5fd" strokeWidth="0.3" opacity={opacity * 0.3} />
                            <polygon points="2,2 6,2 4,6" fill="#c4b5fd" opacity={opacity * 0.4} />
                            <polygon points="34,34 38,34 36,38" fill="#c4b5fd" opacity={opacity * 0.4} />
                        </g>
                    </pattern>

                    <mask id={maskId}>
                        <rect width="100%" height="100%" fill="white" />
                        <rect width="100%" height="100%" fill="url(#edgeGrad)" style={{ mixBlendMode: 'multiply' }} />
                        <rect width="100%" height="100%" fill="url(#cornerGrad)" style={{ mixBlendMode: 'multiply' }} />
                    </mask>

                    <linearGradient id="edgeGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stopColor="black" />
                        <stop offset="20%" stopColor="white" />
                        <stop offset="100%" stopColor="white" />
                    </linearGradient>

                    <radialGradient id="cornerGrad" cx="100%" cy="100%" r="100%">
                        <stop offset="0%" stopColor="black" />
                        <stop offset="40%" stopColor="grey" />
                        <stop offset="70%" stopColor="white" />
                    </radialGradient>

                    <radialGradient id="clearGrad" cx="85%" cy="20%" r="40%">
                        <stop offset="0%" stopColor="black" />
                        <stop offset="40%" stopColor="grey" />
                        <stop offset="70%" stopColor="white" />
                    </radialGradient>

                    <radialGradient id={colorGradient1Id} cx="15%" cy="20%" r="40%">
                        <stop offset="0%" stopColor="#818cf8" stopOpacity="0.35" />
                        <stop offset="40%" stopColor="#a78bfa" stopOpacity="0.18" />
                        <stop offset="100%" stopColor="#a78bfa" stopOpacity="0" />
                    </radialGradient>

                    <radialGradient id={colorGradient2Id} cx="85%" cy="25%" r="42%">
                        <stop offset="0%" stopColor="#f472b6" stopOpacity="0.32" />
                        <stop offset="40%" stopColor="#fb7185" stopOpacity="0.16" />
                        <stop offset="100%" stopColor="#fb7185" stopOpacity="0" />
                    </radialGradient>

                    <radialGradient id={colorGradient3Id} cx="75%" cy="75%" r="45%">
                        <stop offset="0%" stopColor="#38bdf8" stopOpacity="0.33" />
                        <stop offset="40%" stopColor="#22d3ee" stopOpacity="0.17" />
                        <stop offset="100%" stopColor="#22d3ee" stopOpacity="0" />
                    </radialGradient>

                    <radialGradient id={colorGradient4Id} cx="20%" cy="80%" r="38%">
                        <stop offset="0%" stopColor="#34d399" stopOpacity="0.3" />
                        <stop offset="40%" stopColor="#2dd4bf" stopOpacity="0.15" />
                        <stop offset="100%" stopColor="#2dd4bf" stopOpacity="0" />
                    </radialGradient>

                    <radialGradient id={colorGradient5Id} cx="10%" cy="90%" r="35%">
                        <stop offset="0%" stopColor="#fcd34d" stopOpacity="0.25" />
                        <stop offset="40%" stopColor="#fbbf24" stopOpacity="0.12" />
                        <stop offset="100%" stopColor="#fbbf24" stopOpacity="0" />
                    </radialGradient>

                    <filter id={blurFilterId}>
                        <feGaussianBlur in="SourceGraphic" stdDeviation="80" />
                    </filter>
                </defs>

                <g mask={`url(#${maskId})`}>
                    <rect width="100%" height="100%" fill={`url(#${patternId})`} />
                    {showColors && (
                        <g filter={`url(#${blurFilterId})`}>
                            <>
                                <rect width="100%" height="100%" fill={`url(#${colorGradient1Id})`} style={{ mixBlendMode: 'overlay' }} />
                                <rect width="100%" height="100%" fill={`url(#${colorGradient2Id})`} style={{ mixBlendMode: 'overlay' }} />
                                <rect width="100%" height="100%" fill={`url(#${colorGradient3Id})`} style={{ mixBlendMode: 'overlay' }} />
                                <rect width="100%" height="100%" fill={`url(#${colorGradient4Id})`} style={{ mixBlendMode: 'overlay' }} />
                                <rect width="100%" height="100%" fill={`url(#${colorGradient5Id})`} style={{ mixBlendMode: 'overlay' }} />
                            </>
                        </g>
                    )}
                </g>
            </svg>
        </div>
    );
}
