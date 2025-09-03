/**
 * Truncates text to a specified character limit and adds ellipsis
 * @param text - The text to truncate
 * @param limit - The character limit (default: 100)
 * @param ellipsis - The ellipsis string to append (default: '...')
 * @returns The truncated text with ellipsis if needed
 */
export function truncate(text: string | null | undefined, limit: number = 100, ellipsis: string = '...'): string {
    if (!text) return '';

    if (text.length <= limit) {
        return text;
    }

    return text.substring(0, limit).trim() + ellipsis;
}

/**
 * Truncates text to a specified word limit and adds ellipsis
 * @param text - The text to truncate
 * @param wordLimit - The word limit (default: 20)
 * @param ellipsis - The ellipsis string to append (default: '...')
 * @returns The truncated text with ellipsis if needed
 */
export function truncateWords(text: string | null | undefined, wordLimit: number = 20, ellipsis: string = '...'): string {
    if (!text) return '';

    const words = text.split(' ');

    if (words.length <= wordLimit) {
        return text;
    }

    return words.slice(0, wordLimit).join(' ').trim() + ellipsis;
}

/**
 * Strip HTML characters from a string.
 *
 * @param text
 */
export function stripCharacters(text: string) {
    if (!text) return '';

    return text.replace(/<\/?[^>]+(>|$)/g, '');
}
