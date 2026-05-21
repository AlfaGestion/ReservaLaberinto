function normalizeBaseUrl(value) {
    return value.endsWith('/') ? value : `${value}/`
}

function inferCurrentBasePath() {
    const routePrefixes = new Set([
        'abmAdmin',
        'auth',
        'cancelarReserva',
        'confirmarReserva',
        'configMpView',
        'customerNotices',
        'customers',
        'MisReservas',
        'misreservas',
        'pagoAprobado',
        'pagoRechazado',
        'payment',
        'Registrarme',
        'upload',
        'uploadLogo',
    ])

    const pathParts = window.location.pathname.split('/').filter(Boolean)
    const routeIndex = pathParts.findIndex(part => routePrefixes.has(part))

    if (routeIndex === -1) {
        return normalizeBaseUrl(window.location.pathname || '/')
    }

    return normalizeBaseUrl(`/${pathParts.slice(0, routeIndex).join('/')}`)
}

function resolveBaseUrl() {
    const configuredBaseUrl = `${window.appBaseUrl || ''}`.trim()

    if (configuredBaseUrl === '') {
        return `${window.location.origin}${inferCurrentBasePath()}`
    }

    try {
        const configuredUrl = new URL(configuredBaseUrl, window.location.origin)
        const configuredPath = normalizeBaseUrl(configuredUrl.pathname || '/')

        if (configuredUrl.origin !== window.location.origin) {
            const currentBasePath = inferCurrentBasePath()
            const basePath = configuredPath === '/' ? currentBasePath : configuredPath

            return `${window.location.origin}${basePath.startsWith('/') ? basePath : `/${basePath}`}`
        }

        return `${configuredUrl.origin}${configuredPath}`
    } catch (error) {
        const fallbackPath = normalizeBaseUrl(configuredBaseUrl.startsWith('/') ? configuredBaseUrl : `/${configuredBaseUrl}`)
        return `${window.location.origin}${fallbackPath}`
    }
}

const baseUrl = resolveBaseUrl()
