function pad(n: number) {
    return String(n).padStart(2, '0');
}

// 12.–14.11.2026 wenn im selben Monat, sonst 30.11.2026 – 02.12.2026
export function formatRange(startText: string, endText: string) {
    const start = new Date(startText);
    const end = new Date(endText);
    const sameMonth = start.getMonth() === end.getMonth() && start.getFullYear() === end.getFullYear();

    if (sameMonth) {
        const month = pad(start.getMonth() + 1) + '.' + start.getFullYear();
        if (start.getDate() === end.getDate()) {
            return pad(start.getDate()) + '.' + month;
        }
        return pad(start.getDate()) + '.–' + pad(end.getDate()) + '.' + month;
    }
    return start.toLocaleDateString('de-AT') + ' – ' + end.toLocaleDateString('de-AT');
}

export function formatTime(text: string) {
    return new Date(text).toLocaleTimeString('de-AT', { hour: '2-digit', minute: '2-digit' });
}

// 18.11.
export function formatDayMonth(text: string) {
    const date = new Date(text);
    return pad(date.getDate()) + '.' + pad(date.getMonth() + 1) + '.';
}

// 18.11. 09:00
export function formatDayMonthTime(text: string) {
    return formatDayMonth(text) + ' ' + formatTime(text);
}
