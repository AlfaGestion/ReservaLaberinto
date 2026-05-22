(() => {
const tabButton = document.getElementById('nav-rejected-bookings-tab')
const reloadButton = document.getElementById('reloadRejectedBookings')
const tbody = document.getElementById('rejectedBookingsBody')

if (!tbody) {
    return
}

function escapeHtml(value) {
    return `${value ?? ''}`
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;')
}

function formatDate(value) {
    if (!value) return ''
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        const [y, m, d] = value.split('-')
        return `${d}/${m}/${y}`
    }
    return value
}

function formatDateTime(value) {
    if (!value) return ''
    return `${value}`.slice(0, 16).replace('T', ' ')
}

function formatMoney(value) {
    const n = Number(value || 0)
    return `$${new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n)}`
}

async function loadRejectedBookings() {
    tbody.innerHTML = '<tr><td colspan="13" class="text-center text-muted">Cargando...</td></tr>'

    try {
        const response = await fetch(`${baseUrl}getRejectedBookings`)
        const result = await response.json()

        if (!response.ok || result.error) {
            throw new Error(result.message || 'No pudimos cargar las reservas rechazadas')
        }

        const rows = Array.isArray(result.data) ? result.data : []
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="13" class="text-center text-muted">No hay casos para mostrar.</td></tr>'
            return
        }

        tbody.innerHTML = rows.map((item) => `
            <tr>
                <td>${escapeHtml(formatDate(item.booking_date))}</td>
                <td>${escapeHtml((item.booking_time_from || '') + ' a ' + (item.booking_time_until || ''))}</td>
                <td>${escapeHtml(item.name)}</td>
                <td>${escapeHtml(item.phone)}</td>
                <td>${escapeHtml(item.email)}</td>
                <td>${escapeHtml(item.visitors)}</td>
                <td>${escapeHtml(formatMoney(item.total))}</td>
                <td><span class="badge text-bg-secondary">${escapeHtml(item.payment_status)}</span></td>
                <td>${escapeHtml(item.payment_reason || '')}</td>
                <td>${escapeHtml(formatDateTime(item.created_at))}</td>
                <td>${item.notified_at ? '<span class="badge text-bg-success">Si</span>' : '<span class="badge text-bg-warning">No</span>'}</td>
                <td>${escapeHtml(formatDateTime(item.expires_at))}</td>
                <td>
                    <div class="btn-group dropstart">
                        <button type="button" class="btn btn-sm btn-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Acciones</button>
                        <ul class="dropdown-menu">
                            <li><button class="dropdown-item rejected-action" data-action="resend" data-id="${item.id}">Reenviar link de pago</button></li>
                            <li><button class="dropdown-item rejected-action" data-action="approve" data-id="${item.id}">Aprobar pago</button></li>
                            <li><button class="dropdown-item rejected-action" data-action="move" data-id="${item.id}">Pasar a reservas</button></li>
                            <li><button class="dropdown-item rejected-action" data-action="close" data-id="${item.id}">Marcar como cerrada</button></li>
                        </ul>
                    </div>
                </td>
            </tr>
        `).join('')
    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="13" class="text-center text-danger">${escapeHtml(error.message)}</td></tr>`
    }
}

async function executeAction(action, id) {
    const routeMap = {
        resend: 'resendRejectedPaymentLink',
        approve: 'approveRejectedPayment',
        move: 'moveRejectedToBookings',
        close: 'closeRejectedPayment',
    }

    const route = routeMap[action]
    if (!route) {
        return
    }

    const response = await fetch(`${baseUrl}${route}/${id}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({})
    })

    const result = await response.json()
    if (!response.ok || result.error) {
        throw new Error(result.message || 'No pudimos completar la acción')
    }

    if (typeof showAdminNotice === 'function') {
        showAdminNotice(result.message || 'Acción completada')
    }
}

reloadButton?.addEventListener('click', loadRejectedBookings)

tabButton?.addEventListener('shown.bs.tab', loadRejectedBookings)

document.addEventListener('click', async (event) => {
    const button = event.target.closest('.rejected-action')
    if (!button) {
        return
    }

    try {
        button.disabled = true
        await executeAction(button.dataset.action, button.dataset.id)
        await loadRejectedBookings()
    } catch (error) {
        if (typeof showAdminNotice === 'function') {
            showAdminNotice(error.message || 'No pudimos completar la acción', 'error')
        } else {
            alert(error.message || 'No pudimos completar la acción')
        }
    } finally {
        button.disabled = false
    }
})
})()

