const searchBookingButton = document.getElementById('searchBooking')
const inputDesdeBooking = document.getElementById('fechaDesdeBooking')
const inputHastaBooking = document.getElementById('fechaHastaBooking')
const completarPagoModalButton = new bootstrap.Modal('#completarPagoModal')
const contentPaymentResults = document.getElementById('paymentResult')
const spinnerCompletarPagos = new bootstrap.Modal('#spinnerCompletarPago')
const cambiarEstadoMPModal = new bootstrap.Modal('#modalCambiarEstado')
const sendInvoiceEmailModalElement = document.getElementById('sendInvoiceEmailModal')
const sendInvoiceEmailModal = sendInvoiceEmailModalElement ? new bootstrap.Modal(sendInvoiceEmailModalElement) : null
const totalReservasHoy = document.getElementById('totalReservasHoy')
const bookingsTabButton = document.getElementById('nav-bookings-tab')
const botonCompletarPago = document.getElementById('botonCompletarPago')
const selectDateBooking = document.getElementById('selectDateBooking')
const invoiceEmailBookingIdInput = document.getElementById('invoiceEmailBookingId')
const invoiceEmailToInput = document.getElementById('invoiceEmailTo')
const invoiceEmailSubjectInput = document.getElementById('invoiceEmailSubjectModal')
const invoiceEmailMessageInput = document.getElementById('invoiceEmailMessageModal')
const invoiceEmailAttachPdfInput = document.getElementById('invoiceEmailAttachPdf')
const invoiceEmailPdfFileInput = document.getElementById('invoiceEmailPdfFile')
const specialRequestsPanel = document.getElementById('specialRequestsPanel')
const specialRequestsList = document.getElementById('specialRequestsList')
const specialRequestsUnreadBadge = document.getElementById('specialRequestsUnreadBadge')
const refreshSpecialRequestsButton = document.getElementById('refreshSpecialRequests')
const specialRequestsTabButton = document.getElementById('nav-special-requests-tab')
const specialRequestViewModalElement = document.getElementById('specialRequestViewModal')
const specialRequestViewModal = specialRequestViewModalElement ? new bootstrap.Modal(specialRequestViewModalElement) : null
const specialRequestViewContent = document.getElementById('specialRequestViewContent')
const specialRequestReplyModalElement = document.getElementById('specialRequestReplyModal')
const specialRequestReplyModal = specialRequestReplyModalElement ? new bootstrap.Modal(specialRequestReplyModalElement) : null
const specialRequestReplyIdInput = document.getElementById('specialRequestReplyId')
const specialRequestReplyToInput = document.getElementById('specialRequestReplyTo')
const specialRequestReplySubjectInput = document.getElementById('specialRequestReplySubject')
const specialRequestReplyMessageInput = document.getElementById('specialRequestReplyMessage')
const confirmReplySpecialRequestButton = document.getElementById('confirmReplySpecialRequest')

let bookingData = {}
let bookingId = ''
let currentBookingListMode = 'active'
let knownActiveBookingIds = new Set()
let unreadBookingIds = new Set()
let knownSpecialRequestIds = new Set()
let specialRequestAudioContext = null
let browserNotificationPermissionRequested = false
let originalDocumentTitle = document.title
let originalBookingsTabLabel = bookingsTabButton?.innerHTML || '<i class="fa-regular fa-calendar-days"></i> Reservas'

function formatLocalDate(date) {
    const year = date.getFullYear()
    const month = `${date.getMonth() + 1}`.padStart(2, '0')
    const day = `${date.getDate()}`.padStart(2, '0')
    return `${year}-${month}-${day}`
}

function getInvoiceEmailDefaults() {
    return {
        subject: selectDateBooking?.dataset.invoiceEmailSubject || 'Factura de reserva - Laberinto: {nombre}',
        message: selectDateBooking?.dataset.invoiceEmailMessage || 'Hola {nombre},\n\nTe enviamos adjunto el comprobante de tu reserva.\n\nFecha: {fecha}\nHorario: {horario}\nCodigo: {codigo}\nPagado: {pagado}\n\nGracias.'
    }
}

function normalizeMultilineTemplate(template, fallback = '') {
    const rawTemplate = typeof template === 'string' && template.trim() !== '' ? template : fallback
    return `${rawTemplate}`
        .replaceAll('\r\n', '\n')
        .replaceAll('\\r\\n', '\n')
        .replaceAll('\\n', '\n')
        .trim()
}

function getSpecialReplyDefaults() {
    return {
        subject: specialRequestsPanel?.dataset.specialReplySubject || 'Respuesta a tu solicitud de reserva - {fecha}',
        message: normalizeMultilineTemplate(
            specialRequestsPanel?.dataset.specialReplyMessage,
            'Hola {nombre},\n\nVimos tu solicitud para reservar el {fecha} a las {horario}.\n\nTe respondemos por este medio para confirmar disponibilidad y pasos a seguir.\n\nGracias.'
        )
    }
}

function formatBookingDateDisplay(rawDate) {
    if (!rawDate) {
        return ''
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(rawDate)) {
        const [year, month, day] = rawDate.split('-')
        return `${day}/${month}/${year}`
    }

    return rawDate
}

function applyInvoicePlaceholders(template, booking, customerEmail = '') {
    const replacements = {
        '{nombre}': booking?.name || 'Cliente',
        '{fecha}': formatBookingDateDisplay(booking?.date || ''),
        '{horario}': `${booking?.time_from || ''} a ${booking?.time_until || ''}`.trim(),
        '{codigo}': booking?.code || '',
        '{pagado}': `$${booking?.payment || 0}`,
        '{email}': customerEmail || '',
        '{telefono}': booking?.phone || ''
    }

    return Object.entries(replacements).reduce((result, [key, value]) => result.replaceAll(key, value), template || '')
}

async function getCustomerByPhone(phone) {
    if (!phone) {
        return null
    }

    try {
        const response = await fetch(`${baseUrl}getCustomer/${encodeURIComponent(phone)}`)
        const responseData = await response.json()
        return response.ok ? responseData?.data || null : null
    } catch (error) {
        console.error('Error:', error)
        return null
    }
}

async function openInvoiceEmailComposer(bookingId, triggerButton = null) {
    if (!sendInvoiceEmailModal || !invoiceEmailBookingIdInput || !invoiceEmailToInput || !invoiceEmailSubjectInput || !invoiceEmailMessageInput) {
        return false
    }

    if (triggerButton) {
        triggerButton.disabled = true
        triggerButton.dataset.originalText = triggerButton.innerText
        triggerButton.innerText = 'Preparando...'
    }

    try {
        const booking = await getBooking(bookingId)
        const customer = await getCustomerByPhone(booking?.phone || '')
        const customerEmail = customer?.email || ''
        const defaults = getInvoiceEmailDefaults()

        invoiceEmailBookingIdInput.value = bookingId
        invoiceEmailToInput.value = customerEmail
        invoiceEmailSubjectInput.value = applyInvoicePlaceholders(defaults.subject, booking, customerEmail)
        invoiceEmailMessageInput.value = applyInvoicePlaceholders(defaults.message, booking, customerEmail)
        invoiceEmailAttachPdfInput.checked = true
        if (invoiceEmailPdfFileInput) {
            invoiceEmailPdfFileInput.value = ''
        }

        sendInvoiceEmailModal.show()
        return true
    } catch (error) {
        console.error('Error:', error)
        if (typeof showAdminNotice === 'function') {
            showAdminNotice('No se pudo preparar el email de la factura', 'error')
        } else {
            alert('No se pudo preparar el email de la factura')
        }
        return false
    } finally {
        if (triggerButton) {
            triggerButton.disabled = false
            triggerButton.innerText = triggerButton.dataset.originalText || 'Enviar factura'
            delete triggerButton.dataset.originalText
        }
    }
}

function applySpecialRequestPlaceholders(template, request) {
    const replacements = {
        '{nombre}': request?.customer_name || 'Cliente',
        '{fecha}': request?.requested_date_display || 'Fecha a confirmar',
        '{horario}': request?.time_display || 'Horario a confirmar',
        '{telefono}': request?.customer_phone || '',
        '{email}': request?.customer_email || '',
        '{visitantes}': `${request?.visitors || 0}`,
        '{minimo}': `${request?.minimum_visitors || 0}`
    }

    return Object.entries(replacements).reduce((result, [key, value]) => result.replaceAll(key, value), template || '')
}

function escapeHtml(value) {
    return `${value ?? ''}`
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;')
}

function formatSpecialRequestAmount(amount) {
    const numericAmount = Number(amount)

    if (!Number.isFinite(numericAmount)) {
        return 'No calculado'
    }

    return `$${new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(numericAmount)}`
}

function getSpecialRequestStatusMeta(status = '') {
    const statusMap = {
        new: {
            badgeClass: 'text-bg-danger',
            cardClass: 'border-danger-subtle bg-danger-subtle',
        },
        viewed: {
            badgeClass: 'text-bg-secondary',
            cardClass: 'bg-light',
        },
        replied: {
            badgeClass: 'text-bg-success',
            cardClass: 'bg-light',
        },
        confirmed: {
            badgeClass: 'text-bg-primary',
            cardClass: 'border-primary-subtle bg-primary-subtle',
        },
        cancelled: {
            badgeClass: 'text-bg-warning',
            cardClass: 'border-warning-subtle bg-warning-subtle',
        },
    }

    return statusMap[status] || statusMap.viewed
}
function unlockSpecialRequestAudio() {
    if (specialRequestAudioContext || typeof window.AudioContext !== 'function') {
        return
    }

    try {
        specialRequestAudioContext = new window.AudioContext()
    } catch (error) {
        specialRequestAudioContext = null
    }
}

async function ensureBrowserNotificationPermission() {
    if (!('Notification' in window) || browserNotificationPermissionRequested || Notification.permission !== 'default') {
        return
    }

    browserNotificationPermissionRequested = true

    try {
        await Notification.requestPermission()
    } catch (error) {
        console.error('No se pudo solicitar permiso de notificaciones:', error)
    }
}

function playSpecialRequestSound() {
    if (!specialRequestAudioContext) {
        return
    }

    try {
        if (specialRequestAudioContext.state === 'suspended') {
            specialRequestAudioContext.resume()
        }

        const now = specialRequestAudioContext.currentTime
        ;[0, 0.18].forEach((offset) => {
            const oscillator = specialRequestAudioContext.createOscillator()
            const gain = specialRequestAudioContext.createGain()

            oscillator.type = 'sine'
            oscillator.frequency.setValueAtTime(offset === 0 ? 880 : 660, now + offset)
            gain.gain.setValueAtTime(0.0001, now + offset)
            gain.gain.exponentialRampToValueAtTime(0.14, now + offset + 0.02)
            gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + 0.22)

            oscillator.connect(gain)
            gain.connect(specialRequestAudioContext.destination)
            oscillator.start(now + offset)
            oscillator.stop(now + offset + 0.24)
        })
    } catch (error) {
        console.error('Error al reproducir el aviso de solicitud especial:', error)
    }
}

function showBrowserSpecialRequestNotification(count = 1) {
    if (!('Notification' in window) || Notification.permission !== 'granted') {
        return
    }

    try {
        const notification = new Notification('Nueva solicitud de reserva', {
            body: count > 1
                ? `Ingresaron ${count} nuevas solicitudes especiales.`
                : 'Ingres\u00f3 una nueva solicitud especial para revisar.',
            tag: 'special-booking-request',
            renotify: true,
        })

        setTimeout(() => notification.close(), 8000)
    } catch (error) {
        console.error('No se pudo mostrar la notificacion del navegador:', error)
    }
}

function isBookingsTabActive() {
    return bookingsTabButton?.classList.contains('active') === true
}

function updateBookingsTabState(unreadCount = unreadBookingIds.size) {
    if (!bookingsTabButton) {
        return
    }

    bookingsTabButton.innerHTML = unreadCount > 0
        ? `<i class="fa-regular fa-calendar-days"></i> Reservas (${unreadCount})`
        : originalBookingsTabLabel
}

function markBookingNotificationsAsSeen() {
    unreadBookingIds.clear()
    updateBookingsTabState(0)
}

function playBookingSound() {
    if (!specialRequestAudioContext) {
        return
    }

    try {
        if (specialRequestAudioContext.state === 'suspended') {
            specialRequestAudioContext.resume()
        }

        const now = specialRequestAudioContext.currentTime
        const frequencies = [740, 880, 988]

        ;[0, 0.12, 0.24].forEach((offset, index) => {
            const oscillator = specialRequestAudioContext.createOscillator()
            const gain = specialRequestAudioContext.createGain()

            oscillator.type = 'triangle'
            oscillator.frequency.setValueAtTime(frequencies[index], now + offset)
            gain.gain.setValueAtTime(0.0001, now + offset)
            gain.gain.exponentialRampToValueAtTime(0.12, now + offset + 0.02)
            gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + 0.18)

            oscillator.connect(gain)
            gain.connect(specialRequestAudioContext.destination)
            oscillator.start(now + offset)
            oscillator.stop(now + offset + 0.2)
        })
    } catch (error) {
        console.error('Error al reproducir el aviso de nueva reserva:', error)
    }
}

function showBrowserBookingNotification(count = 1) {
    if (!('Notification' in window) || Notification.permission !== 'granted') {
        return
    }

    try {
        const notification = new Notification('Nueva reserva', {
            body: count > 1
                ? `Entraron ${count} nuevas reservas para revisar.`
                : 'Entro una nueva reserva para revisar.',
            tag: 'new-booking',
            renotify: true,
        })

        setTimeout(() => notification.close(), 8000)
    } catch (error) {
        console.error('No se pudo mostrar la notificacion de nueva reserva:', error)
    }
}

function processIncomingBookings(items = [], notifyOnNew = false, markAsSeen = false) {
    const currentIds = new Set((Array.isArray(items) ? items : []).map(item => `${item.id}`))
    const newIds = [...currentIds].filter(id => !knownActiveBookingIds.has(id))
    knownActiveBookingIds = currentIds

    if (!notifyOnNew || newIds.length === 0) {
        if (markAsSeen) {
            markBookingNotificationsAsSeen()
        }
        return
    }

    newIds.forEach(id => unreadBookingIds.add(id))

    if (markAsSeen) {
        markBookingNotificationsAsSeen()
    } else {
        updateBookingsTabState(unreadBookingIds.size)
    }

    playBookingSound()
    showBrowserBookingNotification(newIds.length)

    if (typeof showAdminNotice === 'function') {
        showAdminNotice(
            newIds.length > 1
                ? `Entraron ${newIds.length} nuevas reservas para revisar`
                : 'Entro una nueva reserva para revisar',
            'info',
            'Nueva reserva'
        )
    }
}

async function refreshBookingNotifications(playSoundOnNew = false) {
    if (!bookingData?.fechaDesde || !bookingData?.fechaHasta) {
        return
    }

    try {
        await getActiveBookings(bookingData, {
            updateTable: currentBookingListMode === 'active',
            updateSummary: currentBookingListMode === 'active',
            notifyOnNew: playSoundOnNew,
            resetKnown: false,
            markAsSeen: currentBookingListMode === 'active' && isBookingsTabActive(),
            showPendingMpAlert: false,
        })
    } catch (error) {
        console.error('Error al actualizar reservas en segundo plano:', error)
    }
}

function renderSpecialRequests(items = []) {
    if (!specialRequestsList) {
        return
    }

    if (!Array.isArray(items) || items.length === 0) {
        specialRequestsList.innerHTML = '<div class="text-muted">No hay solicitudes especiales para mostrar.</div>'
        return
    }

    specialRequestsList.innerHTML = items.map((item) => {
        const statusMeta = getSpecialRequestStatusMeta(item.status)
        const isClosedRequest = ['cancelled', 'confirmed'].includes(item.status)
        const canReply = Boolean(item.customer_email) && !isClosedRequest
        const canCancel = !isClosedRequest
        const replyLabel = item.status === 'cancelled'
            ? 'Cancelada'
            : (item.status === 'confirmed'
                ? 'Confirmada'
                : (item.customer_email ? 'Responder' : 'Sin email'))

        return `
            <div class="border rounded-4 p-3 mb-3 ${statusMeta.cardClass}">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                            <strong>Solicitud #${item.id}</strong>
                            <span class="badge rounded-pill ${statusMeta.badgeClass}">${escapeHtml(item.status_label)}</span>
                            <span class="text-muted small">${escapeHtml(item.created_at_display)}</span>
                        </div>
                        <div class="small text-muted mb-1">Fecha solicitada: <strong class="text-dark">${escapeHtml(item.requested_date_display)}</strong>${item.time_display ? ` &middot; Horario: <strong class="text-dark">${escapeHtml(item.time_display)}</strong>` : ''}</div>
                        <div class="small text-muted mb-1">Cliente: <strong class="text-dark">${escapeHtml(item.customer_name)}</strong> &middot; ${escapeHtml(item.customer_phone)}${item.customer_email ? ` &middot; ${escapeHtml(item.customer_email)}` : ''}</div>
                        <div class="small text-muted">Visitantes: <strong class="text-dark">${escapeHtml(item.visitors)}</strong> &middot; M&iacute;nimo requerido: <strong class="text-dark">${escapeHtml(item.minimum_visitors)}</strong> &middot; Importe: <strong class="text-dark">${escapeHtml(formatSpecialRequestAmount(item.total_amount))}</strong></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-self-start">
                        <button type="button" class="btn btn-outline-dark btn-sm special-request-view-trigger" data-id="${item.id}">Ver mensaje</button>
                        <button type="button" class="btn btn-primary btn-sm special-request-reply-trigger" data-id="${item.id}" ${canReply ? '' : 'disabled'}>${replyLabel}</button>
                        <button type="button" class="btn btn-outline-warning btn-sm special-request-cancel-trigger" data-id="${item.id}" ${canCancel ? '' : 'disabled'}>Cancelar</button>
                        <button type="button" class="btn btn-outline-danger btn-sm special-request-delete-trigger" data-id="${item.id}">Eliminar</button>
                    </div>
                </div>
            </div>
        `
    }).join('')
}

function updateSpecialRequestsBadge(unreadCount = 0) {
    if (!specialRequestsUnreadBadge) {
        return
    }

    if (unreadCount > 0) {
        specialRequestsUnreadBadge.classList.remove('d-none')
        specialRequestsUnreadBadge.textContent = `${unreadCount} nueva${unreadCount === 1 ? '' : 's'}`
        return
    }

    specialRequestsUnreadBadge.classList.add('d-none')
    specialRequestsUnreadBadge.textContent = '0 nuevas'
}

function updateSpecialRequestsTabState(unreadCount = 0) {
    if (specialRequestsTabButton) {
        specialRequestsTabButton.innerHTML = unreadCount > 0
            ? `<i class="fa-regular fa-bell"></i> Solicitudes reservas (${unreadCount})`
            : '<i class="fa-regular fa-bell"></i> Solicitudes reservas'
    }

    document.title = unreadCount > 0
        ? `(${unreadCount}) Solicitudes nuevas - ${originalDocumentTitle}`
        : originalDocumentTitle
}

async function fetchSpecialBookingRequests(playSoundOnNew = false) {
    if (!specialRequestsList) {
        return
    }

    try {
        const response = await fetch(`${baseUrl}getSpecialBookingRequests`)
        const responseData = await response.json()

        if (!response.ok || responseData.error) {
            specialRequestsList.innerHTML = '<div class="text-danger">No se pudieron cargar las solicitudes especiales.</div>'
            return
        }

        const items = Array.isArray(responseData.data) ? responseData.data : []
        const currentNewIds = new Set(items.filter(item => item.status === 'new').map(item => `${item.id}`))
        const newIds = [...currentNewIds].filter(id => !knownSpecialRequestIds.has(id))
        const hasBrandNewRequest = playSoundOnNew && newIds.length > 0

        knownSpecialRequestIds = currentNewIds
        const unreadCount = responseData?.meta?.unreadCount || currentNewIds.size
        updateSpecialRequestsBadge(unreadCount)
        updateSpecialRequestsTabState(unreadCount)
        renderSpecialRequests(items)

        if (hasBrandNewRequest) {
            playSpecialRequestSound()
            showBrowserSpecialRequestNotification(newIds.length)
            if (typeof showAdminNotice === 'function') {
                showAdminNotice('Entr\u00f3 una nueva solicitud especial para revisar', 'info', 'Nueva solicitud')
            }
        }
    } catch (error) {
        console.error('Error:', error)
        specialRequestsList.innerHTML = '<div class="text-danger">No se pudieron cargar las solicitudes especiales.</div>'
    }
}

async function openSpecialRequestView(requestId) {
    if (!specialRequestViewModal || !specialRequestViewContent) {
        return
    }

    specialRequestViewContent.innerHTML = '<div class="text-muted">Cargando solicitud...</div>'

    try {
        const response = await fetch(`${baseUrl}viewSpecialBookingRequest/${requestId}`)
        const responseData = await response.json()

        if (!response.ok || responseData.error) {
            throw new Error(responseData.message || 'No se pudo cargar la solicitud')
        }

        const item = responseData.data
        const statusMeta = getSpecialRequestStatusMeta(item.status)
        specialRequestViewContent.innerHTML = `
            <div class="mb-3">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                    <strong>Solicitud #${item.id}</strong>
                    <span class="badge rounded-pill ${statusMeta.badgeClass}">${escapeHtml(item.status_label)}</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-6"><strong>Fecha solicitada:</strong><br>${escapeHtml(item.requested_date_display)}</div>
                    <div class="col-md-6"><strong>Horario solicitado:</strong><br>${escapeHtml(item.time_display || 'No informado')}</div>
                    <div class="col-md-6"><strong>Recibida:</strong><br>${escapeHtml(item.created_at_display)}</div>
                    <div class="col-md-6"><strong>Nombre:</strong><br>${escapeHtml(item.customer_name)}</div>
                    <div class="col-md-6"><strong>Apellido:</strong><br>${escapeHtml(item.customer_last_name || 'No informado')}</div>
                    <div class="col-md-6"><strong>Tel&eacute;fono:</strong><br>${escapeHtml(item.customer_phone)}</div>
                    <div class="col-md-6"><strong>Email:</strong><br>${escapeHtml(item.customer_email || 'No informado')}</div>
                    <div class="col-md-6"><strong>Visitantes:</strong><br>${escapeHtml(item.visitors)} (m&iacute;nimo ${escapeHtml(item.minimum_visitors)})</div>
                    <div class="col-md-6"><strong>Importe:</strong><br>${escapeHtml(formatSpecialRequestAmount(item.total_amount))}</div>
                    <div class="col-md-6"><strong>DNI:</strong><br>${escapeHtml(item.customer_dni || 'No informado')}</div>
                    <div class="col-md-6"><strong>Ciudad:</strong><br>${escapeHtml(item.customer_city || 'No informada')}</div>
                    <div class="col-md-6"><strong>Tipo / Instituci&oacute;n:</strong><br>${escapeHtml(item.customer_type_institution || 'No informado')}</div>
                </div>
            </div>
            <div class="border rounded-4 p-3 bg-light">
                <strong class="d-block mb-2">Mensaje</strong>
                <div style="white-space: pre-line;">${escapeHtml(item.request_message || 'Sin mensaje')}</div>
            </div>
        `

        specialRequestViewModal.show()
        fetchSpecialBookingRequests()
    } catch (error) {
        console.error('Error:', error)
        if (typeof showAdminNotice === 'function') {
            showAdminNotice('No se pudo abrir la solicitud', 'error')
        } else {
            alert('No se pudo abrir la solicitud')
        }
    }
}

async function openSpecialRequestReply(requestId) {
    if (!specialRequestReplyModal || !specialRequestReplyIdInput || !specialRequestReplyToInput || !specialRequestReplySubjectInput || !specialRequestReplyMessageInput) {
        return
    }

    try {
        const response = await fetch(`${baseUrl}viewSpecialBookingRequest/${requestId}`)
        const responseData = await response.json()

        if (!response.ok || responseData.error) {
            throw new Error(responseData.message || 'No se pudo cargar la solicitud')
        }

        const item = responseData.data
        const defaults = getSpecialReplyDefaults()

        specialRequestReplyIdInput.value = item.id
        specialRequestReplyToInput.value = item.customer_email || ''
        specialRequestReplySubjectInput.value = applySpecialRequestPlaceholders(defaults.subject, item)
        specialRequestReplyMessageInput.value = item.reply_message || applySpecialRequestPlaceholders(defaults.message, item)

        specialRequestReplyModal.show()
        fetchSpecialBookingRequests()
    } catch (error) {
        console.error('Error:', error)
        if (typeof showAdminNotice === 'function') {
            showAdminNotice('No se pudo preparar la respuesta', 'error')
        } else {
            alert('No se pudo preparar la respuesta')
        }
    }
}

async function cancelSpecialRequestFromAdmin(requestId) {
    if (!requestId) {
        return
    }

    if (!window.confirm('Cancelar esta solicitud especial?')) {
        return
    }

    try {
        const response = await fetch(`${baseUrl}cancelSpecialBookingRequest/${requestId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        const responseData = await response.json()

        if (!response.ok || responseData.error) {
            throw new Error(responseData.message || 'No se pudo cancelar la solicitud')
        }

        specialRequestReplyModal?.hide()
        fetchSpecialBookingRequests()

        if (typeof showAdminNotice === 'function') {
            showAdminNotice(responseData.message || 'Solicitud cancelada correctamente')
        } else {
            alert(responseData.message || 'Solicitud cancelada correctamente')
        }
    } catch (error) {
        console.error('Error:', error)
        if (typeof showAdminNotice === 'function') {
            showAdminNotice(error.message || 'No se pudo cancelar la solicitud', 'error')
        } else {
            alert(error.message || 'No se pudo cancelar la solicitud')
        }
    }
}

async function deleteSpecialRequestFromAdmin(requestId) {
    if (!requestId) {
        return
    }

    if (!window.confirm('Eliminar esta solicitud especial? Esta accion no se puede deshacer.')) {
        return
    }

    try {
        const response = await fetch(`${baseUrl}deleteSpecialBookingRequest/${requestId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        const responseData = await response.json()

        if (!response.ok || responseData.error) {
            throw new Error(responseData.message || 'No se pudo eliminar la solicitud')
        }

        specialRequestViewModal?.hide()
        specialRequestReplyModal?.hide()
        fetchSpecialBookingRequests()

        if (typeof showAdminNotice === 'function') {
            showAdminNotice(responseData.message || 'Solicitud eliminada correctamente')
        } else {
            alert(responseData.message || 'Solicitud eliminada correctamente')
        }
    } catch (error) {
        console.error('Error:', error)
        if (typeof showAdminNotice === 'function') {
            showAdminNotice(error.message || 'No se pudo eliminar la solicitud', 'error')
        } else {
            alert(error.message || 'No se pudo eliminar la solicitud')
        }
    }
}

document.addEventListener('DOMContentLoaded', async (e) => {
    const today = formatLocalDate(new Date())
    const weekStart = selectDateBooking?.dataset.weekStart || today
    const latestBookingDate = selectDateBooking?.dataset.latestBookingDate || today

    inputDesdeBooking.value = weekStart
    inputHastaBooking.value = latestBookingDate

    bookingData = {
        fechaDesde: inputDesdeBooking.value,
        fechaHasta: inputHastaBooking.value
    }

    currentBookingListMode = 'active'
    await getActiveBookings(bookingData, {
        resetKnown: true,
        markAsSeen: true,
    })
    fetchSpecialBookingRequests()
    setInterval(() => {
        refreshBookingNotifications(true)
        fetchSpecialBookingRequests(true)
    }, 30000)
})

bookingsTabButton?.addEventListener('shown.bs.tab', () => {
    if (currentBookingListMode === 'active') {
        markBookingNotificationsAsSeen()
    }
})

document.addEventListener('pointerdown', unlockSpecialRequestAudio, { once: true })
document.addEventListener('pointerdown', ensureBrowserNotificationPermission, { once: true })


document.addEventListener('click', async (e) => {
    if (e.target) {
        if (e.target.id == 'searchBooking') {
            bookingData = {
                fechaDesde: inputDesdeBooking.value,
                fechaHasta: inputHastaBooking.value
            }

            currentBookingListMode = 'active'
            await getActiveBookings(bookingData, {
                resetKnown: true,
                markAsSeen: isBookingsTabActive(),
            })
        } else if (e.target.id == 'searchAnnulledBooking') {
            bookingData = {
                fechaDesde: inputDesdeBooking.value,
                fechaHasta: inputHastaBooking.value
            }

            currentBookingListMode = 'annulled'
            await getAnnulledBookings(bookingData)
            await getActiveBookings(bookingData, {
                updateTable: false,
                updateSummary: false,
                resetKnown: true,
                showPendingMpAlert: false,
            })
        } else if (e.target.id == 'modalCompletarPago') {

            const bookingId = e.target.dataset.id
            const botonPagar = document.getElementById('botonCompletarPago')
            const booking = await getBooking(bookingId)
            botonPagar.setAttribute('data-id', bookingId)

            completarPagoModalButton.show()
            inputCompletarPagoReserva.value = booking.diference
            medioPagoSelect.value = ''
            if (botonPagar) {
                botonPagar.disabled = false
                botonPagar.innerText = 'Pagar'
            }
        } else if (e.target.id == 'modalCambiarEstado') {
            cambiarEstadoMPModal.show()
            bookingId = e.target.dataset.id

        } else if (e.target.id == 'confirmarMP') {
            const check = document.getElementById('confirmarMPCheck')

            let dataState = {
                confirm: check.checked,
                bookingId: bookingId
            }

            confirmMP(dataState)

        } else if (e.target.id == 'botonCompletarPago') {

            const idUser = document.getElementById('userId').dataset.id
            const botonPagar = document.getElementById('botonCompletarPago')
            const bookingId = botonPagar.dataset.id
            const booking = await getBooking(bookingId)

            if (medioPagoSelect.value == '' || inputCompletarPagoReserva.value == '') {
                return alert('Debe completar todos los campos')
            }

            // if (inputCompletarPagoReserva.value > booking.diference) {
            //     return alert('El monto a abonar no puede ser mayor al saldo')
            // }

            let data = {
                pago: inputCompletarPagoReserva.value,
                idUser: idUser,
                medioPago: medioPagoSelect.value,
                idCustomer: booking.id_customer,
            }

            botonCompletarPago.disabled = true
            botonCompletarPago.innerText = 'Procesando...'
            
            completePayment(`${baseUrl}completePayment/${bookingId}`, data)

        } else if (e.target.id == 'resendBookingEmail') {
            const resendButton = e.target
            const resendBookingId = resendButton.dataset.id

            resendButton.disabled = true
            resendButton.innerText = 'Reenviando...'

            try {
                const response = await fetch(`${baseUrl}resendBookingEmail/${resendBookingId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })

                const responseData = await response.json()

                if (response.ok && !responseData.error) {
                    if (typeof showAdminNotice === 'function') {
                        showAdminNotice(responseData.message || 'Se intento reenviar el email')
                    } else {
                        alert(responseData.message || 'Se intento reenviar el email')
                    }
                } else {
                    if (typeof showAdminNotice === 'function') {
                        showAdminNotice(responseData.message || 'No se pudo reenviar el email', 'error')
                    } else {
                        alert(responseData.message || 'No se pudo reenviar el email')
                    }
                }
            } catch (error) {
                console.error('Error:', error)
                if (typeof showAdminNotice === 'function') {
                    showAdminNotice('No se pudo reenviar el email', 'error')
                } else {
                    alert('No se pudo reenviar el email')
                }
            } finally {
                resendButton.disabled = false
                resendButton.innerText = 'Reenviar email'
            }
        } else if (e.target.id == 'sendBookingInvoiceEmail') {
            const invoiceButton = e.target
            const invoiceBookingId = invoiceButton.dataset.id
            const openedFromPaymentResult = invoiceButton.closest('#paymentResult') !== null

            if (openedFromPaymentResult) {
                modalResultPayment.hide()
            }

            const openedComposer = await openInvoiceEmailComposer(invoiceBookingId, invoiceButton)

            if (!openedComposer && openedFromPaymentResult) {
                modalResultPayment.show()
            }
        } else if (e.target.id == 'confirmSendBookingInvoiceEmail') {
            const confirmButton = e.target
            const invoiceBookingId = invoiceEmailBookingIdInput?.value || ''

            if (!invoiceBookingId || !invoiceEmailToInput?.value || !invoiceEmailSubjectInput?.value || !invoiceEmailMessageInput?.value) {
                return alert('Debe completar destinatario, asunto y mensaje')
            }

            confirmButton.disabled = true
            confirmButton.innerText = 'Enviando...'

            try {
                const formData = new FormData()
                formData.append('email', invoiceEmailToInput.value.trim())
                formData.append('subject', invoiceEmailSubjectInput.value)
                formData.append('message', invoiceEmailMessageInput.value)
                formData.append('attachInvoice', invoiceEmailAttachPdfInput?.checked === true ? '1' : '0')

                if (invoiceEmailPdfFileInput?.files?.[0]) {
                    formData.append('invoicePdfFile', invoiceEmailPdfFileInput.files[0])
                }

                const response = await fetch(`${baseUrl}sendBookingInvoiceEmail/${invoiceBookingId}`, {
                    method: 'POST',
                    body: formData
                })

                const responseData = await response.json()

                if (response.ok && !responseData.error) {
                    sendInvoiceEmailModal.hide()

                    if (bookingData?.fechaDesde && bookingData?.fechaHasta) {
                        await getActiveBookings(bookingData, {
                            showPendingMpAlert: false,
                            markAsSeen: currentBookingListMode === 'active' && isBookingsTabActive(),
                        })
                    }
                    if (typeof showAdminNotice === 'function') {
                        showAdminNotice(responseData.message || 'Factura enviada correctamente')
                    } else {
                        alert(responseData.message || 'Factura enviada correctamente')
                    }
                } else {
                    if (typeof showAdminNotice === 'function') {
                        showAdminNotice(responseData.message || 'No se pudo enviar la factura', 'error')
                    } else {
                        alert(responseData.message || 'No se pudo enviar la factura')
                    }
                }
            } catch (error) {
                console.error('Error:', error)
                if (typeof showAdminNotice === 'function') {
                    showAdminNotice('No se pudo enviar la factura', 'error')
                } else {
                    alert('No se pudo enviar la factura')
                }
            } finally {
                confirmButton.disabled = false
                confirmButton.innerText = 'Enviar factura'
            }
        } else if (e.target.id == 'refreshSpecialRequests') {
            fetchSpecialBookingRequests()
        } else if (e.target.closest('.special-request-view-trigger')) {
            const viewButton = e.target.closest('.special-request-view-trigger')
            openSpecialRequestView(viewButton.dataset.id)
        } else if (e.target.closest('.special-request-reply-trigger')) {
            const replyButton = e.target.closest('.special-request-reply-trigger')
            openSpecialRequestReply(replyButton.dataset.id)
        } else if (e.target.closest('.special-request-cancel-trigger')) {
            const cancelButton = e.target.closest('.special-request-cancel-trigger')
            cancelSpecialRequestFromAdmin(cancelButton.dataset.id)
        } else if (e.target.closest('.special-request-delete-trigger')) {
            const deleteButton = e.target.closest('.special-request-delete-trigger')
            deleteSpecialRequestFromAdmin(deleteButton.dataset.id)
        } else if (e.target.id == 'confirmReplySpecialRequest') {
            const confirmButton = e.target
            const requestId = specialRequestReplyIdInput?.value || ''

            if (!requestId || !specialRequestReplyToInput?.value || !specialRequestReplySubjectInput?.value || !specialRequestReplyMessageInput?.value) {
                return alert('Debe completar destinatario, asunto y mensaje')
            }

            if (confirmButton) {
                confirmButton.disabled = true
                confirmButton.innerText = 'Enviando email...'
            }

            try {
                const formData = new FormData()
                formData.append('email', specialRequestReplyToInput.value.trim())
                formData.append('subject', specialRequestReplySubjectInput.value)
                formData.append('message', specialRequestReplyMessageInput.value)

                const response = await fetch(`${baseUrl}replySpecialBookingRequest/${requestId}`, {
                    method: 'POST',
                    body: formData
                })

                const responseData = await response.json()

                if (!response.ok || responseData.error) {
                    throw new Error(responseData.message || 'No se pudo enviar la respuesta')
                }

                specialRequestReplyModal.hide()
                fetchSpecialBookingRequests()

                if (typeof showAdminNotice === 'function') {
                    showAdminNotice(responseData.message || 'Respuesta enviada correctamente')
                } else {
                    alert(responseData.message || 'Respuesta enviada correctamente')
                }
            } catch (error) {
                console.error('Error:', error)
                if (typeof showAdminNotice === 'function') {
                    showAdminNotice(error.message || 'No se pudo enviar la respuesta', 'error')
                } else {
                    alert(error.message || 'No se pudo enviar la respuesta')
                }
            } finally {
                if (confirmButton) {
                    confirmButton.disabled = false
                    confirmButton.innerText = 'Enviar email'
                }
            }
        }
    }
})

function resetCompletePaymentButton() {
    if (!botonCompletarPago) {
        return
    }

    botonCompletarPago.disabled = false
    botonCompletarPago.innerText = 'Pagar'
}

async function refreshBookingsAfterPayment() {
    if (currentBookingListMode !== 'active' || !bookingData?.fechaDesde || !bookingData?.fechaHasta) {
        return
    }

    await getActiveBookings(bookingData, {
        showPendingMpAlert: false,
        markAsSeen: isBookingsTabActive(),
    })
}

async function completePayment(url, data) {
    try {
        spinnerCompletarPagos.show()

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })

        let responseData = null
        try {
            responseData = await response.json()
        } catch (error) {
            responseData = null
        }

        if (!response.ok || responseData?.error) {
            throw new Error(responseData?.message || 'No se pudo ingresar el pago.')
        }

        const bookingId = responseData?.data?.bookingId || null
        const paymentCompleted = responseData?.data?.totalPaymentCompleted === true

        completarPagoModalButton.hide()

        let refreshedTable = true
        try {
            await refreshBookingsAfterPayment()
        } catch (refreshError) {
            refreshedTable = false
            console.error('Error actualizando la tabla despues del pago:', refreshError)
        }

        contentPaymentResults.innerHTML = `
            <div class="modal-body modalResultPayment d-flex justify-content-center align-items-center flex-column text-white" style="background-color: #157347;">
                <h4 class="mb-4">Pago confirmado!</h4>
                <i class="fa-regular fa-circle-check fa-2xl mb-4"></i>
                ${paymentCompleted ? '<p class="mb-0 text-white text-center">La reserva quedo saldada. Puede enviar la factura desde aqui.</p>' : '<p class="mb-0 text-white text-center">La reserva se actualizo sin recargar la pagina.</p>'}
                ${!refreshedTable ? '<p class="mt-3 mb-0 text-white text-center">El pago se guardo, pero no se pudo actualizar la tabla automaticamente.</p>' : ''}
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                    ${paymentCompleted && bookingId ? `<button type="button" class="btn btn-light" id="sendBookingInvoiceEmail" data-id="${bookingId}">Enviar factura</button>` : ''}
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>`

        spinnerCompletarPagos.hide()
        modalResultPayment.show()

        if (typeof showAdminNotice === 'function') {
            showAdminNotice(responseData?.message || 'Pago confirmado')
        }
    } catch (error) {
        console.error('Error:', error)
        completarPagoModalButton.hide()
        spinnerCompletarPagos.hide()

        contentPaymentResults.innerHTML = `
            <div class="modal-body modalResultPayment d-flex justify-content-center align-items-center flex-column text-white" style="background-color: #bb2d3b;">
                <h4 class="mb-4">No se pudo guardar el pago</h4>
                <i class="fa-regular fa-circle-xmark fa-2xl mb-4"></i>
                <p class="mb-0 text-white text-center">${escapeHtml(error.message || 'Vuelva a intentar.')}</p>
                <button type="button" class="btn btn-outline-light mt-4" data-bs-dismiss="modal">Cerrar</button>
            </div>`

        modalResultPayment.show()

        if (typeof showAdminNotice === 'function') {
            showAdminNotice(error.message || 'No se pudo ingresar el pago', 'error')
        }
    } finally {
        resetCompletePaymentButton()
    }
}

async function getBooking(id) {
    try {
        const response = await fetch(`${baseUrl}getBooking/${id}`);

        const responseData = await response.json();

        if (responseData.data != '') {

            return responseData.data

        } else {
            alert('Algo sali\u00f3 mal. No se pudo obtener la informaci\u00f3n.');
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}


async function confirmMP(data) {

    try {
        const response = await fetch(`${baseUrl}/confirmMP`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        location.reload(true)

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function getActiveBookings(data, options = {}) {
    const {
        updateTable = true,
        updateSummary = true,
        notifyOnNew = false,
        resetKnown = false,
        markAsSeen = false,
        showPendingMpAlert = true,
    } = options

    try {
        const response = await fetch(`${baseUrl}getActiveBookings`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const responseData = await response.json();
        const bookings = Array.isArray(responseData?.data) ? responseData.data : []

        if (resetKnown) {
            knownActiveBookingIds = new Set(bookings.map(item => `${item.id}`))
            if (markAsSeen) {
                markBookingNotificationsAsSeen()
            } else {
                updateBookingsTabState(unreadBookingIds.size)
            }
        } else {
            processIncomingBookings(bookings, notifyOnNew, markAsSeen)
        }

        if (updateSummary) {
            totalReservasHoy.innerHTML = '&nbsp;' + bookings.length
        }

        if (updateTable) {
            fillTableBookings(bookings, { showPendingMpAlert })
        }

        return bookings
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function getAnnulledBookings(data) {
    try {
        const response = await fetch(`${baseUrl}getAnnulledBookings`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const responseData = await response.json();

        fillTableBookings(responseData.data)

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function fillTableBookings(data, options = {}) {
    const { showPendingMpAlert = true } = options
    const divBookings = document.querySelector('.divBookings')
    const bookings = Array.isArray(data) ? [...data] : []

    let existPending = false
    let stateMP = ''
    let tr = ''
    let actions = ''
    let edit = ''
    let anular = ''
    let state = ''

    bookings.sort((a, b) => {
        const aPaid = a?.pago_total === 'Si' ? 1 : 0
        const bPaid = b?.pago_total === 'Si' ? 1 : 0

        if (aPaid !== bPaid) {
            return aPaid - bPaid
        }

        return 0
    })

    bookings.forEach(reserva => {

        if (reserva.mp == 0) {
            if (existPending == false) {
                existPending = true
                if (showPendingMpAlert) {
                    alert('Tiene pagos pendientes ingresantes de Mercado Pago')
                }
            }
        }

        reserva.anulada == 1 ? state = 'Anulada' : state = 'Activa'

        if (reserva.metodo_pago == 'mercado_pago') {
            reserva.mp == 0 ? stateMP = 'Pendiente' : stateMP = 'Confirmado'
        } else {
            stateMP = 'No aplica'
        }

        if (sessionUserSuperadmin == 1) {
            edit = `
            <li><button type="button" class="btn btn-primary dropdown-item" id="editarReservaModal" data-id="${reserva.id}">Editar reserva</button></li>
            `
            if (reserva.anulada == 0) {
                anular = `
                <li><button type="button" class="btn btn-primary dropdown-item" id="eliminarReservaModal" data-id="${reserva.id}">Anular reserva</button></li>
                `
            }
        }

        if (reserva.pago_total === 'Si') {
            if (sessionUserSuperadmin == 1) {
                if (reserva.anulada == 1) {
                    actions = `
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-success" disabled>
                            Sin acciones
                        </button>
                    </div>
                `
                } else {
                    actions = `
                    <div class="btn-group dropstart" role="group">
                        <button type="button" class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            Acciones
                        </button>
                        <ul class="dropdown-menu">
                            <input type="text" id="userId" data-id="${sessionUserId}" hidden>                        
                            <li><button type="button" class="btn btn-primary dropdown-item" id="resendBookingEmail" data-id="${reserva.id}">Reenviar email</button></li>
                            <li><button type="button" class="btn btn-primary dropdown-item" id="sendBookingInvoiceEmail" data-id="${reserva.id}">Enviar factura</button></li>
                            ${anular}
    
                            ${edit}
                        </ul>
                    </div>
                `;
                }


            } else {
                actions = `
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-success" disabled>
                        Sin acciones
                    </button>
                </div>
            `
            }

        } else {
            if (reserva.anulada == 1) {
                actions = `
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-success" disabled>
                        Sin acciones
                    </button>
                </div>
            `
            } else {
                actions = `
            <div class="btn-group dropstart" role="group">
                <button type="button" class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    Acciones
                </button>
                <ul class="dropdown-menu">
                    <input type="text" id="userId" data-id="${sessionUserId}" hidden>
                    <li><button type="button" class="btn btn-primary dropdown-item" id="resendBookingEmail" data-id="${reserva.id}">Reenviar email</button></li>
                    ${reserva.diferencia > 0 ? `<li><button type="button" class="btn btn-primary dropdown-item" id="modalCambiarEstado" data-id="${reserva.id}">Cambiar estado de pago</button></li>` : ''
                    }
                    ${reserva.diferencia > 0 ? `<li><button type="button" class="btn btn-primary dropdown-item" id="modalCompletarPago" data-id="${reserva.id}">Completar pago</button></li>` : ''
                    }
                    

                    ${anular}

                    ${edit}
                </ul>
            </div>
        `;
            }

        }


        let descripcion = ''
        descripcion = reserva.descripcion == '' || reserva.descripcion == null ? 'Reserva' : reserva.descripcion

        // console.log(typeof reserva.descripcion)

        const invoiceStatus = reserva.factura_enviada_fecha
            ? `<div class="d-flex flex-column"><span class="badge text-bg-success align-self-start">${escapeHtml(reserva.factura_enviada || 'Enviada')}</span><small class="text-muted mt-1">${escapeHtml(reserva.factura_enviada_fecha)}</small></div>`
            : `<span class="badge text-bg-secondary">${escapeHtml(reserva.factura_enviada || 'Pendiente')}</span>`

        const rowClass = reserva.pago_total === 'Si' ? 'admin-booking-row admin-booking-row--paid' : 'admin-booking-row'

        tr += `
        <tr class="${rowClass}">
            <td>${reserva.fecha}</th>
            <td>${reserva.cancha}</td>
            <td>${reserva.horario}</td>
            <td>${reserva.nombre}</td>
            <td>${reserva.telefono}</td>
            <td>${reserva.visitantes}</td>
            <td>${reserva.monto_reserva}</td>
            <td>${reserva.total_reserva}</td>
            <td>${reserva.diferencia > 0 ? '-' + reserva.diferencia : 0}</td>
            <td>${reserva.metodo_pago}</td>
            <td>${descripcion}</td>
            <td>${stateMP}</td>
            <td>${state}</td>
            <td>${reserva.code}</td>
            <td>${invoiceStatus}</td>
            <td>${actions}</td>
        </tr>
    `
    });

    divBookings.innerHTML = tr
}
