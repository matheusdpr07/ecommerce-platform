export function formatCentsToBrl(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',');
}

export function parseBrlToCents(value: string): number {
    const normalized = value.replace(/\./g, '').replace(',', '.').trim();
    const parsed = Number.parseFloat(normalized);

    if (Number.isNaN(parsed) || parsed < 0) {
        return 0;
    }

    return Math.round(parsed * 100);
}

export function formatMoneyFromCents(cents: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(cents / 100);
}
