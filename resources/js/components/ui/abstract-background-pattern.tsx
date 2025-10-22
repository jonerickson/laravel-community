import { useId } from 'react';

interface AbstractBackgroundPatternProps {
    className?: string;
    opacity?: number;
    corner?: 'bottom-left' | 'bottom-right';
}

export function AbstractBackgroundPattern({ className, opacity = 0.08, corner = 'bottom-right' }: AbstractBackgroundPatternProps) {
    const patternId = useId();
    const maskId = useId();
    const colorGradient1Id = useId();
    const colorGradient2Id = useId();
    const colorGradient3Id = useId();
    const colorGradient4Id = useId();
    const blurFilterId = useId();

    const isLeft = corner === 'bottom-left';

    return (
        <div className={className}>
            <svg
                fill="none"
                viewBox="0 0 1000 800"
                width="1000"
                height="800"
                preserveAspectRatio={isLeft ? 'xMinYMax meet' : 'xMaxYMax meet'}
                style={{
                    display: 'block',
                    position: 'absolute',
                    bottom: 0,
                    ...(isLeft ? { left: 0 } : { right: 0 }),
                }}
            >
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
                            <circle cx="6" cy="34" r="1.5" fill="#ec4899" opacity={opacity} />
                            <circle cx="34" cy="6" r="1.5" fill="#06b6d4" opacity={opacity} />
                            <line x1="0" y1="20" x2="40" y2="20" stroke="#a78bfa" strokeWidth="0.3" opacity={opacity * 0.5} />
                            <line x1="20" y1="0" x2="20" y2="40" stroke="#a78bfa" strokeWidth="0.3" opacity={opacity * 0.5} />
                            <polygon points="2,2 6,2 4,6" fill="#10b981" opacity={opacity * 0.6} />
                            <polygon points="34,34 38,34 36,38" fill="#f59e0b" opacity={opacity * 0.6} />
                        </g>
                    </pattern>

                    <mask id={maskId}>
                        <rect width="100%" height="100%" fill="white" />
                        <rect width="100%" height="100%" fill="url(#edgeGrad)" style={{ mixBlendMode: 'multiply' }} />
                        <rect width="100%" height="100%" fill="url(#cornerGrad)" style={{ mixBlendMode: 'multiply' }} />
                        {isLeft && <rect width="100%" height="100%" fill="url(#clearGrad)" style={{ mixBlendMode: 'multiply' }} />}
                    </mask>

                    <linearGradient id="edgeGrad" x1={isLeft ? '100%' : '0%'} y1="0%" x2={isLeft ? '0%' : '100%'} y2="0%">
                        <stop offset="0%" stopColor="black" />
                        <stop offset="20%" stopColor="white" />
                        <stop offset="100%" stopColor="white" />
                    </linearGradient>

                    <radialGradient id="cornerGrad" cx={isLeft ? '0%' : '100%'} cy="100%" r="100%">
                        <stop offset="0%" stopColor="black" />
                        <stop offset="40%" stopColor="grey" />
                        <stop offset="70%" stopColor="white" />
                    </radialGradient>

                    <radialGradient id="clearGrad" cx="85%" cy="20%" r="40%">
                        <stop offset="0%" stopColor="black" />
                        <stop offset="40%" stopColor="grey" />
                        <stop offset="70%" stopColor="white" />
                    </radialGradient>

                    <radialGradient id={colorGradient1Id} cx={isLeft ? '85%' : '15%'} cy="20%" r="40%">
                        <stop offset="0%" stopColor="#6366f1" stopOpacity="0.35" />
                        <stop offset="40%" stopColor="#8b5cf6" stopOpacity="0.18" />
                        <stop offset="100%" stopColor="#8b5cf6" stopOpacity="0" />
                    </radialGradient>

                    <radialGradient id={colorGradient2Id} cx={isLeft ? '15%' : '85%'} cy="25%" r="42%">
                        <stop offset="0%" stopColor="#ec4899" stopOpacity="0.32" />
                        <stop offset="40%" stopColor="#f43f5e" stopOpacity="0.16" />
                        <stop offset="100%" stopColor="#f43f5e" stopOpacity="0" />
                    </radialGradient>

                    <radialGradient id={colorGradient3Id} cx={isLeft ? '25%' : '75%'} cy="75%" r="45%">
                        <stop offset="0%" stopColor="#0ea5e9" stopOpacity="0.33" />
                        <stop offset="40%" stopColor="#06b6d4" stopOpacity="0.17" />
                        <stop offset="100%" stopColor="#06b6d4" stopOpacity="0" />
                    </radialGradient>

                    <radialGradient id={colorGradient4Id} cx={isLeft ? '80%' : '20%'} cy="80%" r="38%">
                        <stop offset="0%" stopColor="#10b981" stopOpacity="0.3" />
                        <stop offset="40%" stopColor="#14b8a6" stopOpacity="0.15" />
                        <stop offset="100%" stopColor="#14b8a6" stopOpacity="0" />
                    </radialGradient>

                    <filter id={blurFilterId}>
                        <feGaussianBlur in="SourceGraphic" stdDeviation="80" />
                    </filter>
                </defs>

                <g mask={`url(#${maskId})`}>
                    <rect width="100%" height="100%" fill={`url(#${patternId})`} />
                    <g filter={`url(#${blurFilterId})`}>
                        {isLeft ? (
                            <>
                                <rect width="100%" height="100%" fill="white" opacity="0.3" />
                                <rect width="100%" height="100%" fill={`url(#${colorGradient2Id})`} style={{ mixBlendMode: 'overlay' }} />
                                <rect width="100%" height="100%" fill={`url(#${colorGradient3Id})`} style={{ mixBlendMode: 'overlay' }} />
                                <rect width="100%" height="100%" fill={`url(#${colorGradient4Id})`} style={{ mixBlendMode: 'overlay' }} />
                            </>
                        ) : (
                            <>
                                <rect width="100%" height="100%" fill={`url(#${colorGradient1Id})`} style={{ mixBlendMode: 'overlay' }} />
                                <rect width="100%" height="100%" fill={`url(#${colorGradient2Id})`} style={{ mixBlendMode: 'overlay' }} />
                                <rect width="100%" height="100%" fill={`url(#${colorGradient3Id})`} style={{ mixBlendMode: 'overlay' }} />
                                <rect width="100%" height="100%" fill={`url(#${colorGradient4Id})`} style={{ mixBlendMode: 'overlay' }} />
                            </>
                        )}
                    </g>
                </g>
            </svg>
        </div>
    );
}
