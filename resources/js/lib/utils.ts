import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function currency(value: string | number | null | undefined, showFree: boolean = true) {
    if (value === undefined || value === null) return '0.00';

    if (typeof value !== 'string') value = String(value);

    if (value === '0' && showFree) return 'Free';

    const amount = parseFloat(value);

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
}

export function date(value: string, time: boolean = false) {
    const trimmedDate = value.replace(/\.\d+Z$/, 'Z');
    const date = new Date(trimmedDate);

    if (isNaN(date.getTime())) {
        return value;
    }

    const dateOptions: Intl.DateTimeFormatOptions = time
        ? {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              hour: 'numeric',
              minute: '2-digit',
              timeZoneName: 'short',
          }
        : {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
          };

    return new Intl.DateTimeFormat('en-US', dateOptions).format(date);
}

export function ucFirst(str: string): string {
    if (!str) return '';
    return str[0].toUpperCase() + str.slice(1);
}

export function pluralize(word: string, count: number, pluralForm?: string): string {
    if (count === 1) {
        return word;
    }

    if (pluralForm) {
        return pluralForm;
    }

    if (word.endsWith('y') && !['a', 'e', 'i', 'o', 'u'].includes(word[word.length - 2])) {
        return word.slice(0, -1) + 'ies';
    }

    if (word.endsWith('s') || word.endsWith('sh') || word.endsWith('ch') || word.endsWith('x') || word.endsWith('z')) {
        return word + 'es';
    }

    if (word.endsWith('f')) {
        return word.slice(0, -1) + 'ves';
    }

    if (word.endsWith('fe')) {
        return word.slice(0, -2) + 'ves';
    }

    if (word.endsWith('o') && !['a', 'e', 'i', 'o', 'u'].includes(word[word.length - 2])) {
        return word + 'es';
    }

    return word + 's';
}

export function formatNumber(value: number | string | null | undefined): string {
    if (value === null || value === undefined) {
        return '0';
    }

    const num = typeof value === 'string' ? parseFloat(value) : value;

    if (isNaN(num)) {
        return '0';
    }

    return new Intl.NumberFormat('en-US').format(num);
}

export function abbreviateNumber(value: number | string | null | undefined): string {
    if (value === null || value === undefined) {
        return '0';
    }

    const num = typeof value === 'string' ? parseFloat(value) : value;

    if (isNaN(num)) {
        return '0';
    }

    if (num >= 1_000_000) {
        return `${(num / 1_000_000).toFixed(1).replace(/\.0$/, '')}M`;
    }

    if (num >= 1_000) {
        return `${(num / 1_000).toFixed(1).replace(/\.0$/, '')}K`;
    }

    return num.toString();
}
