import { cn } from '@/lib/utils';
import { truncate } from '@/utils/truncate';

interface StyledUserNameProps {
    user: App.Data.UserData;
    className?: string;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    showIcon?: boolean;
}

export function StyledUserName({ user, className, size = 'sm', showIcon = true }: StyledUserNameProps) {
    if (!user.displayStyle) {
        return <span className={className}>{user.name}</span>;
    }

    const { color, style, icon } = user.displayStyle;

    const getStyle = () => {
        switch (style) {
            case 'solid':
                return {
                    color: color,
                };
            case 'gradient':
                return {
                    background: `linear-gradient(90deg, ${color}, ${adjustColorBrightness(color, 40)})`,
                    WebkitBackgroundClip: 'text',
                    WebkitTextFillColor: 'transparent',
                    backgroundClip: 'text',
                };
            case 'holographic':
                return {
                    background: `linear-gradient(90deg,
                        ${color},
                        ${adjustColorHue(color, 60)},
                        ${adjustColorHue(color, 120)},
                        ${adjustColorHue(color, 180)})`,
                    backgroundSize: '200% 100%',
                    WebkitBackgroundClip: 'text',
                    WebkitTextFillColor: 'transparent',
                    backgroundClip: 'text',
                    animation: 'holographic-shift 3s ease-in-out infinite',
                };
            default:
                return {
                    color: color,
                };
        }
    };

    return (
        <span className={cn('inline-flex items-center gap-2', className)}>
            <span style={getStyle()} className="leading-none font-medium text-nowrap">
                {truncate(user.name, 32)}
            </span>
            {icon && showIcon && (
                <img
                    className={cn('w-auto object-contain', {
                        'h-[0.5rem]': size === 'xs',
                        'h-[1rem]': size === 'sm',
                        'h-[1.5rem]': size === 'md',
                        'h-[2.0rem]': size === 'lg',
                        'h-[2.5rem]': size === 'xl',
                    })}
                    src={icon}
                    alt={user.name}
                />
            )}
        </span>
    );
}

function adjustColorBrightness(color: string, percent: number): string {
    const num = parseInt(color.replace('#', ''), 16);
    const amt = Math.round(2.55 * percent);
    const R = (num >> 16) + amt;
    const G = ((num >> 8) & 0x00ff) + amt;
    const B = (num & 0x0000ff) + amt;
    return (
        '#' +
        (0x1000000 + (R < 255 ? (R < 1 ? 0 : R) : 255) * 0x10000 + (G < 255 ? (G < 1 ? 0 : G) : 255) * 0x100 + (B < 255 ? (B < 1 ? 0 : B) : 255))
            .toString(16)
            .slice(1)
    );
}

function adjustColorHue(color: string, degrees: number): string {
    const num = parseInt(color.replace('#', ''), 16);
    const r = (num >> 16) / 255;
    const g = ((num >> 8) & 0x00ff) / 255;
    const b = (num & 0x0000ff) / 255;

    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    let h = 0;
    let s = 0;
    const l = (max + min) / 2;

    if (max !== min) {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);

        switch (max) {
            case r:
                h = ((g - b) / d + (g < b ? 6 : 0)) / 6;
                break;
            case g:
                h = ((b - r) / d + 2) / 6;
                break;
            case b:
                h = ((r - g) / d + 4) / 6;
                break;
        }
    }

    h = ((h * 360 + degrees) % 360) / 360;

    const hue2rgb = (p: number, q: number, t: number) => {
        if (t < 0) t += 1;
        if (t > 1) t -= 1;
        if (t < 1 / 6) return p + (q - p) * 6 * t;
        if (t < 1 / 2) return q;
        if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
        return p;
    };

    let newR: number, newG: number, newB: number;

    if (s === 0) {
        newR = newG = newB = l;
    } else {
        const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
        const p = 2 * l - q;
        newR = hue2rgb(p, q, h + 1 / 3);
        newG = hue2rgb(p, q, h);
        newB = hue2rgb(p, q, h - 1 / 3);
    }

    return (
        '#' +
        Math.round(newR * 255)
            .toString(16)
            .padStart(2, '0') +
        Math.round(newG * 255)
            .toString(16)
            .padStart(2, '0') +
        Math.round(newB * 255)
            .toString(16)
            .padStart(2, '0')
    );
}
