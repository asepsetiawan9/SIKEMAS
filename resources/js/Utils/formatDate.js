/**
 * Format date string or object to Indonesian display format
 * Example: 2026-09-27 -> 27 September 2026
 */
export function formatDate(dateInput) {
    if (!dateInput) return '-';

    const date = new Date(dateInput);
    if (isNaN(date.getTime())) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
}
