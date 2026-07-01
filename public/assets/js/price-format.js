window.formatPriceAR = function formatPriceAR(value, fallback = 'No calculado') {
    if (value === null || value === undefined || value === '') {
        return fallback
    }

    let numericValue = Number(value)

    if (!Number.isFinite(numericValue)) {
        const normalized = `${value}`.replace(/[$\s]/g, '').replace(/\./g, '').replace(/,/g, '.')
        numericValue = Number(normalized)
    }

    if (!Number.isFinite(numericValue)) {
        return fallback
    }

    return `$${Math.round(numericValue).toLocaleString('es-AR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    })}`
}
