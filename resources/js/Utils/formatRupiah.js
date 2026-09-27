/**
 * Format numeric value to Indonesian Rupiah representation
 * Example: 15750000 -> Rp 15.750.000,00
 */
export function formatRupiah(amount) {
    if (amount === null || amount === undefined || isNaN(Number(amount))) {
        return 'Rp 0,00';
    }

    const num = Number(amount);
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
}
