export function getPercentage(value: number, total: number): number {
    return total > 0 ? Math.round((value / total) * 100) : 0;
}
