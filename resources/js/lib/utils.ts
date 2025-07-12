import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function currency(value: string) {
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
