function normalizeBaseUrl(value) {
    return value.endsWith('/') ? value : `${value}/`
}

function resolveBaseUrl() {
    const configuredBaseUrl = `${window.appBaseUrl || ''}`.trim()

    if (configuredBaseUrl === '') {
        return normalizeBaseUrl(window.location.origin)
    }

    try {
        const configuredUrl = new URL(configuredBaseUrl, window.location.origin)
        const configuredPath = normalizeBaseUrl(configuredUrl.pathname || '/')

        if (configuredUrl.origin !== window.location.origin) {
            return `${window.location.origin}${configuredPath.startsWith('/') ? configuredPath : `/${configuredPath}`}`
        }

        return `${configuredUrl.origin}${configuredPath}`
    } catch (error) {
        const fallbackPath = normalizeBaseUrl(configuredBaseUrl.startsWith('/') ? configuredBaseUrl : `/${configuredBaseUrl}`)
        return `${window.location.origin}${fallbackPath}`
    }
}

const baseUrl = resolveBaseUrl()
