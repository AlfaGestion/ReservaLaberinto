const confirmarReservabutton = document.getElementById('confirmarReserva')
const bookingForm = document.getElementById('bookingForm')
const selectCancha = document.getElementById('cancha')
const fechaInput = document.getElementById('fecha')
const horarioDesde = document.getElementById('horarioDesde')
const horarioHasta = document.getElementById('horarioHasta')
const inputMonto = document.getElementById('inputMonto')
const divMonto = document.getElementById('div-monto')
const divqtyvisitors = document.getElementById('div-qtyvisitors')
const nombre = document.getElementById('nombre')
const telefono = document.getElementById('telefono')
const pagoReserva = document.getElementById('inputPagoReserva')
const pagoTotal = document.getElementById('switchPagoTotal')
const divTime = document.getElementById('div-time')
const divTimeH = document.getElementById('div-time-h')
const modalConfirmarReserva = new bootstrap.Modal('#modalConfirmarReserva')
const modalSpinner = new bootstrap.Modal('#modalSpinner')
const modalIngresarPago = new bootstrap.Modal('#ingresarPago')
const modalIngresarPagoElement = document.getElementById('ingresarPago')
const modalResult = new bootstrap.Modal('#modalResult')
const contentBookingResult = document.getElementById('bookingResult')
const divSelectCancha = document.getElementById('divSelectCancha')
const powerOff = document.getElementsByName('powerOff')
const inputqtyvisitors = document.getElementById('inputqtyvisitors')
const selectServicio = document.getElementById('selectServicio')
const botonesContainer = document.getElementById('botones-container')
const showBooking = document.getElementById('showBooking')
const bookingStageAvailability = document.getElementById('bookingStageAvailability')
const bookingStageDetails = document.getElementById('bookingStageDetails')
const continueBookingStep = document.getElementById('continueBookingStep')
const backBookingStep = document.getElementById('backBookingStep')

const btnMpParcial = document.getElementById('btn-parcial')
const btnMpTotal = document.getElementById('btn-total')

const modalAvailability = new bootstrap.Modal('#modalAvailability')
const availabilityResult = document.getElementById('availabilityResult')
const showAvailability = document.getElementById('showAvailability')
const availabilityInlineResult = document.getElementById('availabilityInlineResult')
const showTermsLink = document.getElementById('showTermsLink')
const toggleTermsAudio = document.getElementById('toggleTermsAudio')
const termsPrevButton = document.getElementById('termsPrevButton')
const termsNextButton = document.getElementById('termsNextButton')
const termsAudioRate = document.getElementById('termsAudioRate')
const welcomeModalEl = document.getElementById('welcomeModal')
const publicNoticeModalElement = document.getElementById('publicNoticeModal')
const publicNoticeModal = publicNoticeModalElement ? new bootstrap.Modal(publicNoticeModalElement) : null
const publicNoticeInline = document.getElementById('publicNoticeInline')
const publicNoticeIcon = document.getElementById('publicNoticeIcon')
const publicNoticeTitle = document.getElementById('publicNoticeTitle')
const publicNoticeMessage = document.getElementById('publicNoticeMessage')
const publicNoticeAccept = document.getElementById('publicNoticeAccept')

const check1 = document.getElementById('check1')
const check2 = document.getElementById('check2')
const check3 = document.getElementById('check3')
const check4 = document.getElementById('check4')
const check5 = document.getElementById('check5')
const check6 = document.getElementById('check6')
const check7 = document.getElementById('check7')
const check8 = document.getElementById('check8')
const check9 = document.getElementById('check9')
const check10 = document.getElementById('check10')
const check1Div = document.getElementById('check1Div')
const check2Div = document.getElementById('check2Div')
const check3Div = document.getElementById('check3Div')
const check4Div = document.getElementById('check4Div')
const check5Div = document.getElementById('check5Div')
const check6Div = document.getElementById('check6Div')
const check7Div = document.getElementById('check7Div')
const check8Div = document.getElementById('check8Div')
const check9Div = document.getElementById('check9Div')
const check10Div = document.getElementById('check10Div')

const confirmRulesButton = document.getElementById('confirmRulesButton')
const validateDataButton = document.getElementById('validateDataButton')
const inputEmail = document.getElementById('inputEmail')
// const welcomeModal = new bootstrap.Modal('#welcomeModal')
const ofertaModal = new bootstrap.Modal('#ofertaModal')
const publicKeyMp = document.getElementById('publicKeyMp')?.value || ''
const divMessages = document.getElementById('divMessages')
const closeModalValidate = document.getElementById('closeModalValidate')
const termsSections = [check1Div, check2Div, check3Div, check4Div, check5Div, check6Div, check7Div, check8Div, check9Div, check10Div].filter(Boolean)

let data = {}
let preferencesIds = {}
let useOffer = false
let serviceValue = 0
// let idCustomer
let availables = []
let currentCustomer
let minVisitantes = 0
let openingTime = []
let bookingDatePicker = null
let currentTermsUtterance = null
let isTermsSpeechPaused = false
let currentTermsSegments = []
let currentTermsSegmentIndex = -1
let currentHighlightedTerm = null
let currentTermsElement = null
let currentTermsPlaybackId = 0
let currentTermsRate = 1
let publicNoticeAcceptAction = null
let publicBookingPrepared = false
let pendingMpCleanupTimer = null
let pendingMpContext = null
let skipCancelOnHide = false
let selectedAvailabilityDate = ''
let availabilityPageStart = 0
const availabilityPageSize = 5
const termsSessionKey = 'bookingTermsAccepted'
const customerValidatedSessionKey = 'bookingCustomerValidated'

function isAvailabilityStepComplete() {
    return Boolean(
        fechaInput?.value &&
        horarioDesde?.value &&
        horarioHasta?.value &&
        selectCancha?.value &&
        Number(inputqtyvisitors?.value || 0) > 0
    )
}

function updateBookingStageAvailability() {
    if (!continueBookingStep) {
        return
    }

    continueBookingStep.classList.toggle('d-none', !isAvailabilityStepComplete())
}

function showBookingDetailsStep() {
    if (!bookingStageAvailability || !bookingStageDetails) {
        return
    }

    bookingStageAvailability.classList.add('d-none')
    bookingStageDetails.classList.remove('d-none')
    bookingStageDetails.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function showBookingAvailabilityStep() {
    if (!bookingStageAvailability || !bookingStageDetails) {
        return
    }

    bookingStageDetails.classList.add('d-none')
    bookingStageAvailability.classList.remove('d-none')
    bookingStageAvailability.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function setSelectedAvailabilityDate(date) {
    selectedAvailabilityDate = date || ''

    if (bookingDatePicker && selectedAvailabilityDate) {
        bookingDatePicker.setDate(selectedAvailabilityDate, true, 'd/m/Y')
    } else if (fechaInput && selectedAvailabilityDate) {
        fechaInput.value = selectedAvailabilityDate
    }
}

function syncAvailabilityPage(dates, selectedDate) {
    if (!Array.isArray(dates) || !dates.length) {
        availabilityPageStart = 0
        return
    }

    const normalizedSelectedDate = normalizeAvailabilityDate(selectedDate || '')
    const selectedIndex = dates.findIndex((day) => normalizeAvailabilityDate(day.date) === normalizedSelectedDate)

    if (selectedIndex >= 0) {
        const currentPageEnd = availabilityPageStart + availabilityPageSize

        if (selectedIndex < availabilityPageStart || selectedIndex >= currentPageEnd) {
            availabilityPageStart = Math.floor(selectedIndex / availabilityPageSize) * availabilityPageSize
        }
        return
    }

    if (availabilityPageStart >= dates.length) {
        availabilityPageStart = Math.max(0, dates.length - availabilityPageSize)
    }
}

function normalizeAvailabilityDate(value) {
    const parsedDate = parseInputDate(value)
    return parsedDate ? formatDateForDisplay(parsedDate) : (value || '')
}

function getRenderableAvailabilityDates() {
    const availabilityData = availables?.availability || []
    const visibleDates = availabilityData.filter((day) => {
        const slots = Array.isArray(day.available_slots) ? day.available_slots : []

        if (!day?.date || slots.length === 0) {
            return false
        }

        if (slots.length === 1 && typeof slots[0] === 'string' && slots[0].startsWith('Cerrado los ')) {
            return false
        }

        return slots.some((slot) => typeof slot === 'string' && slot.includes(' a '))
    })

    const selectedDateValue = normalizeAvailabilityDate(selectedAvailabilityDate || fechaInput?.value || '')
    const datesToRender = [...visibleDates]

    if (selectedDateValue && !datesToRender.some((day) => normalizeAvailabilityDate(day.date) === selectedDateValue)) {
        datesToRender.unshift({
            date: selectedDateValue,
            available_slots: []
        })
    }

    return datesToRender
}

function renderAvailabilityList(container) {
    if (!container) {
        return
    }

    container.innerHTML = ''

    const visibleDates = getRenderableAvailabilityDates()

    if (!visibleDates.length) {
        container.innerHTML = '<p class="text-center text-muted mb-0">No hay servicios disponibles en los horarios seleccionados.</p>'
        return
    }
    const currentDate = normalizeAvailabilityDate(selectedAvailabilityDate || fechaInput?.value || '')
    const initialDate = currentDate || visibleDates[0]?.date || ''
    const activeDay = visibleDates.find((day) => normalizeAvailabilityDate(day.date) === normalizeAvailabilityDate(initialDate)) || null

    if (!currentDate && activeDay?.date) {
        selectedAvailabilityDate = activeDay.date
        if (bookingDatePicker) {
            bookingDatePicker.setDate(activeDay.date, true, 'd/m/Y')
        } else if (fechaInput) {
            fechaInput.value = activeDay.date
        }
    }

    const selectedDateValue = normalizeAvailabilityDate(selectedAvailabilityDate || fechaInput?.value || '')
    const datesToRender = [...visibleDates]
    syncAvailabilityPage(datesToRender, selectedDateValue)

    const pagedDates = datesToRender.slice(availabilityPageStart, availabilityPageStart + availabilityPageSize)
    const datesMarkup = pagedDates.map((day) => {
        const normalizedDayDate = normalizeAvailabilityDate(day.date)
        const isActive = normalizedDayDate === selectedDateValue

        return `
            <button
                type="button"
                class="booking-date-pill${isActive ? ' booking-date-pill--active' : ''}"
                data-availability-date="${normalizedDayDate}"
            >
                ${day.date}
            </button>
        `
    }).join('')

    const navigationMarkup = datesToRender.length > availabilityPageSize
        ? `
            <div class="booking-availability-picker__nav">
                <button type="button" class="booking-date-nav" id="availabilityPrevDates" aria-label="Ver fechas anteriores">&lt;</button>
                <button type="button" class="booking-date-nav" id="availabilityNextDates" aria-label="Ver mas fechas">&gt;</button>
            </div>
        `
        : ''

    let slotsMarkup = '<p class="text-muted mb-0">Selecciona una fecha para ver horarios.</p>'

    if (activeDay) {
        const isClosed = activeDay.available_slots.length === 1 && activeDay.available_slots[0].startsWith('Cerrado los ')

        if (isClosed) {
            slotsMarkup = `<div class="booking-slot-card booking-slot-card--closed">${activeDay.available_slots[0]}</div>`
        } else {
            slotsMarkup = activeDay.available_slots.map((slot) => {
                const [start, end] = slot.split(' a ')

                return `
                    <div class="booking-slot-card">
                        <span class="booking-slot-card__time">${slot}</span>
                        <button 
                            type="button"
                            class="btn btn-outline-success btn-sm booking-slot-card__button"
                            data-date="${activeDay.date}"
                            data-slot-start="${start}"
                            data-slot-end="${end}"
                            id="selectSlotButton"
                        >
                            Elegir
                        </button>
                    </div>
                `
            }).join('')
        }
    } else if (selectedDateValue) {
        slotsMarkup = '<p class="text-muted mb-0">No hay horarios publicados para esa fecha. Podes elegir otra fecha o escribir una diferente.</p>'
    }

    container.innerHTML = `
        <div class="booking-availability-picker">
            <div class="booking-availability-picker__dates-wrap">
                <div class="booking-availability-picker__dates">${datesMarkup}</div>
                ${navigationMarkup}
            </div>
            <div class="booking-availability-picker__slots">${slotsMarkup}</div>
        </div>
    `
}

function hasAcceptedTermsInSession() {
    try {
        return sessionStorage.getItem(termsSessionKey) === '1'
    } catch (error) {
        return false
    }
}

function setAcceptedTermsInSession(accepted = true) {
    try {
        if (accepted) {
            sessionStorage.setItem(termsSessionKey, '1')
            return
        }

        sessionStorage.removeItem(termsSessionKey)
    } catch (error) {
        // ignore storage errors
    }
}

function hasValidatedCustomerInSession() {
    try {
        return sessionStorage.getItem(customerValidatedSessionKey) === '1'
    } catch (error) {
        return false
    }
}

function setValidatedCustomerInSession(validated = true) {
    try {
        if (validated) {
            sessionStorage.setItem(customerValidatedSessionKey, '1')
            return
        }

        sessionStorage.removeItem(customerValidatedSessionKey)
    } catch (error) {
        // ignore storage errors
    }
}

function updateTermsNextStep() {
    if (!confirmRulesButton) {
        return
    }

    if (hasValidatedCustomerInSession()) {
        confirmRulesButton.removeAttribute('data-bs-toggle')
        confirmRulesButton.removeAttribute('data-bs-target')
        return
    }

    confirmRulesButton.setAttribute('data-bs-toggle', 'modal')
    confirmRulesButton.setAttribute('data-bs-target', '#verifyVisitorsModal')
}

function resetMercadoPagoButtons() {
    const checkoutParcial = document.getElementById('checkout-btn-parcial')
    const checkoutTotal = document.getElementById('checkout-btn-total')

    if (checkoutParcial) {
        checkoutParcial.innerHTML = ''
        checkoutParcial.style.display = 'block'
    }

    if (checkoutTotal) {
        checkoutTotal.innerHTML = ''
        checkoutTotal.style.display = 'none'
    }
}

function applyPreferenceIdsToBookingData(preferences = {}) {
    data.preferenceIdParcial = preferences?.preferenceIdParcial || null
    data.preferenceIdTotal = preferences?.preferenceIdTotal || null
}

function showPublicNotice(message, type = 'error', title = '') {
    showPublicNoticeWithAction(message, type, title)
}

function showPublicNoticeWithAction(message, type = 'error', title = '', onAccept = null) {
    if (!publicNoticeModal || !publicNoticeInline || !publicNoticeTitle || !publicNoticeMessage || !publicNoticeIcon) {
        return
    }

    const iconMap = {
        error: 'fa-circle-xmark',
        info: 'fa-circle-info',
        success: 'fa-circle-check'
    }

    const defaultTitleMap = {
        error: 'No se pudo completar',
        info: 'Atencion',
        success: 'Listo'
    }

    const normalizedTitle = title || defaultTitleMap[type] || defaultTitleMap.error
    publicNoticeInline.className = `public-notice-inline public-notice-inline--${type}`
    publicNoticeIcon.innerHTML = `<i class="fa-solid ${iconMap[type] || iconMap.error}"></i>`
    publicNoticeTitle.textContent = normalizedTitle
    publicNoticeMessage.textContent = message
    publicNoticeAcceptAction = typeof onAccept === 'function' ? onAccept : null
    publicNoticeModal.show()
}

publicNoticeAccept?.addEventListener('click', () => {
    const acceptAction = publicNoticeAcceptAction
    publicNoticeAcceptAction = null
    publicNoticeModal?.hide()

    if (acceptAction) {
        setTimeout(() => {
            acceptAction()
        }, 180)
    }
})

let dataOferta = {
    valor: 0,
    descripcion: '',
    fecha: 0,
}

function formatDateForInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatDateForDisplay(date) {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

function parseInputDate(value) {
    if (!value) {
        return null
    }

    if (value.includes('/')) {
        const [day, month, year] = value.split('/').map(Number)

        if (!year || !month || !day) {
            return null
        }

        return new Date(year, month - 1, day)
    }

    const [year, month, day] = value.split('-').map(Number)

    if (!year || !month || !day) {
        return null
    }

    return new Date(year, month - 1, day)
}

function normalizeDateValue(value) {
    const parsedDate = parseInputDate(value)
    return parsedDate ? formatDateForInput(parsedDate) : value
}

function formatAmountValue(amount) {
    const numericAmount = Number(amount)

    if (!Number.isFinite(numericAmount)) {
        return '0'
    }

    const roundedAmount = Math.round((numericAmount + Number.EPSILON) * 100) / 100
    return Number.isInteger(roundedAmount) ? String(roundedAmount) : roundedAmount.toFixed(2)
}

function updateVisitorsFieldConfig() {
    if (!inputqtyvisitors) {
        return
    }

    const minimumVisitors = Number(minVisitantes) > 0 ? Number(minVisitantes) : 1
    inputqtyvisitors.min = String(minimumVisitors)
    inputqtyvisitors.placeholder = `Minimo ${minimumVisitors}`
    inputqtyvisitors.title = `Cantidad minima: ${minimumVisitors}`
}

function applyMinimumVisitorsDefault(force = false) {
    if (!inputqtyvisitors) {
        return
    }

    const minimumVisitors = Number(minVisitantes) > 0 ? Number(minVisitantes) : 1

    if (force || inputqtyvisitors.value === '' || inputqtyvisitors.value === '0') {
        inputqtyvisitors.value = String(minimumVisitors)
    }

    updateBookingStageAvailability()
}

function validateVisitorsCount() {
    const visitors = Number(inputqtyvisitors?.value || 0)

    if (!visitors) {
        showPublicNotice('Debe ingresar la cantidad de visitantes')
        return false
    }

    if (Number(minVisitantes) > 0 && visitors < Number(minVisitantes)) {
        showPublicNotice(`La cantidad minima de visitantes es ${minVisitantes}`)
        return false
    }

    return true
}

function getCurrentCustomerOffer() {
    const offer = parseFloat(currentCustomer?.offer ?? 0)
    return Number.isNaN(offer) ? 0 : offer
}

function applyValidatedCustomer(customer) {
    if (!customer) {
        return
    }

    currentCustomer = customer
    setValidatedCustomerInSession(true)
    setAcceptedTermsInSession(true)
    localStorage.setItem('customer', JSON.stringify(customer))
    telefono.value = customer.phone || ''
    nombre.value = customer.name || ''
    inputEmail.value = customer.email || inputEmail.value || ''

    validateDataButton?.classList.add('d-none')
    closeModalValidate?.classList.remove('d-none')

    if (divMessages) {
        divMessages.innerHTML = `
        <div class="alert alert-success" role="alert">
            <small>El cliente <strong>${customer.name}</strong> ya se encuentra registrado</small>
        </div>
        `
    }

    updateTermsNextStep()
    refreshBookingAmount()
}

async function handleValidateCustomerClick() {
    const inputTelefono = document.getElementById('inputTelefono')
    const phone = (inputTelefono?.value || '').trim()
    const email = (inputEmail?.value || '').trim()

    if (!phone || !email) {
        showPublicNotice('Debe completar telefono y email para validar la reserva.')
        return
    }

    if (validateDataButton) {
        validateDataButton.disabled = true
        validateDataButton.textContent = 'Validando...'
    }

    try {
        const customer = await validateCustomer(phone, email)
        const registerUrl = `${baseUrl}Registrarme?phone=${encodeURIComponent(phone)}&email=${encodeURIComponent(email)}&returnValidate=1`

        if (customer) {
            applyValidatedCustomer(customer)
            return
        }

        if (sessionUserLogued) {
            currentCustomer = {
                id: null,
                phone,
                email,
                offer: 0,
            }

            telefono.value = phone
            validateDataButton?.classList.add('d-none')
            closeModalValidate?.classList.remove('d-none')

            if (divMessages) {
                divMessages.innerHTML = `
                <div class="alert alert-warning" role="alert">
                    <small>No encontramos un cliente con esos datos. Podes continuar y se dara de alta automaticamente al confirmar la reserva.</small>
                </div>
                `
            }

            showPublicNotice('Cliente no registrado. Podes continuar y se dara de alta automaticamente.', 'info', 'Dato importante')
        } else if (divMessages) {
            closeModalValidate?.classList.add('d-none')
            divMessages.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <small>El cliente no se encuentra registrado. Por favor, <a href="${registerUrl}">registrarme</a></small>
            </div>
            `
        }
    } catch (error) {
        console.error('Error:', error)
        showPublicNotice('No se pudo validar el cliente en este momento. Intente nuevamente.', 'error', 'No se pudo validar el cliente')
    } finally {
        if (validateDataButton) {
            validateDataButton.disabled = false
            validateDataButton.textContent = 'Validar'
        }
    }
}

async function hydrateRegisteredCustomerFromUrl() {
    const params = new URLSearchParams(window.location.search)
    const registered = params.get('registered')
    const phone = params.get('phone') || ''
    const email = params.get('email') || ''

    if (registered !== '1' || phone === '' || email === '') {
        return
    }

    const inputTelefono = document.getElementById('inputTelefono')

    if (inputTelefono) {
        inputTelefono.value = phone
    }

    if (inputEmail) {
        inputEmail.value = email
    }

    try {
        const customer = await validateCustomer(phone, email)

        if (customer) {
            applyValidatedCustomer(customer)
            showPublicNotice('Registro confirmado. Ya tomamos tus datos para continuar con la reserva.', 'success', 'Listo')

            if (welcomeModalEl) {
                const welcomeModal = bootstrap.Modal.getOrCreateInstance(welcomeModalEl)
                welcomeModal.hide()
            }

            const verifyVisitorsModalEl = document.getElementById('verifyVisitorsModal')
            if (verifyVisitorsModalEl) {
                const verifyVisitorsModal = bootstrap.Modal.getOrCreateInstance(verifyVisitorsModalEl)
                verifyVisitorsModal.hide()
            }
        }
    } catch (error) {
        console.error('Error:', error)
    } finally {
        params.delete('registered')
        params.delete('phone')
        params.delete('email')
        const queryString = params.toString()
        const nextUrl = `${window.location.pathname}${queryString ? `?${queryString}` : ''}${window.location.hash}`
        window.history.replaceState({}, '', nextUrl)
    }
}

function getClosedDayKey(date) {
    const dayMap = [
        'is_sunday',
        'is_monday',
        'is_tuesday',
        'is_wednesday',
        'is_thursday',
        'is_friday',
        'is_saturday',
    ];

    return dayMap[date.getDay()];
}

function findNextOpenDate(startDate) {
    const nextDate = new Date(startDate);
    nextDate.setHours(0, 0, 0, 0);

    for (let i = 0; i < 14; i++) {
        const dayKey = getClosedDayKey(nextDate);
        const isClosed = openingTime?.closed?.[dayKey] == 1;

        if (!isClosed) {
            return nextDate;
        }

        nextDate.setDate(nextDate.getDate() + 1);
    }

    return new Date(startDate);
}

function initBookingDatePicker(defaultDate) {
    if (!fechaInput) {
        return;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (window.flatpickr) {
        if (bookingDatePicker) {
            bookingDatePicker.destroy();
        }

        bookingDatePicker = flatpickr(fechaInput, {
            dateFormat: 'd/m/Y',
            allowInput: true,
            disableMobile: true,
            minDate: today,
            defaultDate,
            disable: [
                function (date) {
                    const currentDate = new Date(date);
                    currentDate.setHours(0, 0, 0, 0);
                    return currentDate < today || openingTime?.closed?.[getClosedDayKey(currentDate)] == 1;
                }
            ]
        });

        return;
    }

    fechaInput.value = formatDateForDisplay(defaultDate);
}

function setActiveTermsSection(section) {
    if (currentHighlightedTerm) {
        currentHighlightedTerm.classList.remove('terms-reading-active')
    }

    currentHighlightedTerm = section || null

    if (currentHighlightedTerm) {
        currentHighlightedTerm.classList.add('terms-reading-active')
        currentHighlightedTerm.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        })
    }
}

function hydrateValidationReturnFromUrl() {
    const params = new URLSearchParams(window.location.search)
    const returnValidate = params.get('returnValidate')
    const phone = params.get('phone') || ''
    const email = params.get('email') || ''

    if (returnValidate !== '1' || phone === '' || email === '') {
        return
    }

    const inputTelefono = document.getElementById('inputTelefono')
    const verifyVisitorsModalEl = document.getElementById('verifyVisitorsModal')

    if (inputTelefono) {
        inputTelefono.value = phone
    }

    if (inputEmail) {
        inputEmail.value = email
    }

    if (welcomeModalEl) {
        const welcomeModal = bootstrap.Modal.getOrCreateInstance(welcomeModalEl)
        welcomeModal.hide()
    }

    if (verifyVisitorsModalEl) {
        const verifyVisitorsModal = bootstrap.Modal.getOrCreateInstance(verifyVisitorsModalEl)
        verifyVisitorsModal.show()
    }

    params.delete('returnValidate')
    const queryString = params.toString()
    const nextUrl = `${window.location.pathname}${queryString ? `?${queryString}` : ''}${window.location.hash}`
    window.history.replaceState({}, '', nextUrl)
}

function getTermsSegmentsForSpeech() {
    const termsContainer = document.querySelector('.terms-container');
    if (!termsContainer) {
        return [];
    }

    const acceptedLabel = termsContainer.querySelector('label[for="termsAccepted"]');
    const buttonText = toggleTermsAudio?.textContent?.trim() ?? '';
    const segments = []

    const introBlock = termsContainer.querySelector('.mb-3')
    if (introBlock) {
        let introText = introBlock.innerText || ''

        if (buttonText) {
            introText = introText.replace(buttonText, '')
        }

        introText = introText.replace(/\s+/g, ' ').trim()

        if (introText) {
            segments.push({ text: introText, element: introBlock })
        }
    }

    termsSections.forEach((section) => {
        let text = section.innerText || ''
        text = text.replace(/\s+/g, ' ').trim()

        if (text) {
            segments.push({ text, element: section })
        }
    })

    const finalTextNode = Array.from(termsContainer.querySelectorAll('p'))
        .find((paragraph) => paragraph.classList.contains('fw-bold'))

    if (finalTextNode) {
        const finalText = (finalTextNode.innerText || '').replace(/\s+/g, ' ').trim()
        if (finalText) {
            segments.push({ text: finalText, element: finalTextNode })
        }
    }

    if (acceptedLabel) {
        const acceptanceText = (acceptedLabel.innerText || '').replace(/\s+/g, ' ').trim()
        if (acceptanceText) {
            segments.push({ text: acceptanceText, element: acceptedLabel.closest('.terms-acceptance') || acceptedLabel })
        }
    }

    return segments
}

function speakCurrentTermsSegment() {
    if (!currentTermsSegments.length || currentTermsSegmentIndex < 0 || currentTermsSegmentIndex >= currentTermsSegments.length) {
        stopTermsSpeech()
        return
    }

    const segment = currentTermsSegments[currentTermsSegmentIndex]
    const playbackId = currentTermsPlaybackId
    currentTermsElement = segment.element || null
    setActiveTermsSection(segment.element || null)

    currentTermsUtterance = new SpeechSynthesisUtterance(segment.text)
    currentTermsUtterance.lang = 'es-AR'
    currentTermsUtterance.rate = currentTermsRate
    currentTermsUtterance.pitch = 1
    currentTermsUtterance.onend = () => {
        if (playbackId !== currentTermsPlaybackId) {
            return
        }

        currentTermsSegmentIndex += 1

        if (currentTermsSegmentIndex < currentTermsSegments.length) {
            speakCurrentTermsSegment()
            return
        }

        stopTermsSpeech()
    }
    currentTermsUtterance.onerror = () => {
        if (playbackId !== currentTermsPlaybackId) {
            return
        }

        stopTermsSpeech()
    }

    window.speechSynthesis.speak(currentTermsUtterance)
}

function updateTermsAudioButton(label) {
    if (!toggleTermsAudio) {
        return;
    }

    let iconClass = 'fa-play'
    let title = 'Reproducir'

    if (label === 'Pausar lectura') {
        iconClass = 'fa-pause'
        title = 'Pausar'
    } else if (label === 'Continuar lectura') {
        iconClass = 'fa-play'
        title = 'Continuar'
    } else if (label === 'Audio no disponible') {
        iconClass = 'fa-volume-xmark'
        title = 'Audio no disponible'
    }

    toggleTermsAudio.innerHTML = `<i class="fa-solid ${iconClass}"></i>`
    toggleTermsAudio.title = title
    toggleTermsAudio.setAttribute('aria-label', title)
}

function startTermsSpeechFromIndex(targetIndex) {
    if (!('speechSynthesis' in window) || typeof SpeechSynthesisUtterance === 'undefined') {
        updateTermsAudioButton('Audio no disponible')
        if (toggleTermsAudio) {
            toggleTermsAudio.disabled = true
        }
        return
    }

    const segments = getTermsSegmentsForSpeech()
    if (!segments.length || targetIndex < 0 || targetIndex >= segments.length) {
        return
    }

    currentTermsPlaybackId += 1
    window.speechSynthesis.cancel()
    currentTermsSegments = segments
    currentTermsSegmentIndex = targetIndex
    currentTermsElement = segments[targetIndex]?.element || null
    isTermsSpeechPaused = false
    updateTermsAudioButton('Pausar lectura')
    speakCurrentTermsSegment()
}

function moveTermsSpeech(step) {
    const segments = getTermsSegmentsForSpeech()
    if (!segments.length) {
        return
    }

    let targetIndex = currentTermsSegmentIndex

    if (targetIndex < 0) {
        targetIndex = 0
    } else {
        targetIndex += step
    }

    targetIndex = Math.max(0, Math.min(targetIndex, segments.length - 1))
    startTermsSpeechFromIndex(targetIndex)
}

function stopTermsSpeech(resetButton = true) {
    if (!window.speechSynthesis) {
        return;
    }

    currentTermsPlaybackId += 1
    window.speechSynthesis.cancel();
    currentTermsUtterance = null;
    isTermsSpeechPaused = false;
    currentTermsSegments = []
    currentTermsSegmentIndex = -1
    currentTermsElement = null
    setActiveTermsSection(null)

    if (resetButton) {
        updateTermsAudioButton('Escuchar terminos');
    }
}

function toggleTermsSpeech() {
    if (!toggleTermsAudio) {
        return;
    }

    if (!('speechSynthesis' in window) || typeof SpeechSynthesisUtterance === 'undefined') {
        updateTermsAudioButton('Audio no disponible');
        toggleTermsAudio.disabled = true;
        return;
    }

    if (window.speechSynthesis.speaking && !window.speechSynthesis.paused) {
        window.speechSynthesis.pause();
        isTermsSpeechPaused = true;
        updateTermsAudioButton('Continuar lectura');
        return;
    }

    if (window.speechSynthesis.paused) {
        window.speechSynthesis.resume();
        isTermsSpeechPaused = false;
        updateTermsAudioButton('Pausar lectura');
        return;
    }

    currentTermsSegments = getTermsSegmentsForSpeech()

    if (!currentTermsSegments.length) {
        return;
    }
    startTermsSpeechFromIndex(0)
}

// Fecha actual por defecto
document.addEventListener('DOMContentLoaded', async (e) => {
    // Muestra el modal de bienvenida si el usuario no estÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ logueado.
    const returningFromRegistration = new URLSearchParams(window.location.search).get('registered') === '1'

    if (!sessionUserLogued && !hasAcceptedTermsInSession() && !returningFromRegistration) {
        const welcomeModalEl = document.getElementById('welcomeModal');
        if (welcomeModalEl) {
            const welcomeModal = new bootstrap.Modal(welcomeModalEl);
            welcomeModal.show();
        }
    }

    // Obtiene la disponibilidad inicial de horarios.
    getAvailability();
    await getTime();

    // Eliminar los ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºltimos 3 horarios de "Desde" para ajustar los bloques de 2 horas.
    // Nota: Esto asume que tu lÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³gica de backend genera 3 horarios extra.
    for (let i = 0; i < 3; i++) {
        if (horarioDesde.options.length > 1) { // Evitar errores si hay pocas opciones
            horarioDesde.remove(horarioDesde.options.length - 1);
        }
    }

    // Aseguramos que todas las opciones estÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©n habilitadas por defecto.
    for (let i = 0; i < horarioDesde.options.length; i++) {
        horarioDesde.options[i].disabled = false;
    }

    // Aplicar lÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³gica de bloques de 120 minutos (habilitar cada 4ta opciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n).
    // Se salta el ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­ndice 0 que es "Seleccionar".
    for (let i = 1; i < horarioDesde.options.length; i++) {
        if ((i - 1) % 4 !== 0) {
            horarioDesde.options[i].disabled = true;
        }
    }

    const fechaInicial = new Date();
    const proximaFechaDisponible = findNextOpenDate(fechaInicial);
    initBookingDatePicker(proximaFechaDisponible);

    // --- LÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œGICA ADICIONAL ---

    inputqtyvisitors.disabled = true;

    if (esDomingo === '1') {
        checkSunday();
    }

    // Llamadas a funciones finales para inicializar el estado de la pÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gina.
    getRate();
    deleteRejected();
    setupTermsModal();
    await hydrateRegisteredCustomerFromUrl()
    hydrateValidationReturnFromUrl()
    showBookingAvailabilityStep()
    updateBookingStageAvailability()

    if (modalIngresarPagoElement) {
        modalIngresarPagoElement.addEventListener('hidden.bs.modal', async () => {
            if (skipCancelOnHide) {
                skipCancelOnHide = false
                return
            }

            await cancelPendingMpReservation()
        })
    }

    if (toggleTermsAudio) {
        toggleTermsAudio.addEventListener('click', toggleTermsSpeech);
    }

    if (termsPrevButton) {
        termsPrevButton.addEventListener('click', () => moveTermsSpeech(-1))
    }

    if (termsNextButton) {
        termsNextButton.addEventListener('click', () => moveTermsSpeech(1))
    }

    if (termsAudioRate) {
        termsAudioRate.addEventListener('change', () => {
            const selectedRate = parseFloat(termsAudioRate.value || '1')
            currentTermsRate = Number.isNaN(selectedRate) ? 1 : selectedRate

            if (window.speechSynthesis.speaking || window.speechSynthesis.paused) {
                const restartIndex = currentTermsSegmentIndex >= 0 ? currentTermsSegmentIndex : 0
                startTermsSpeechFromIndex(restartIndex)
            }
        })
    }

    if (welcomeModalEl) {
        welcomeModalEl.addEventListener('hidden.bs.modal', () => stopTermsSpeech());
    }
});

function setupTermsModal() {
    if (!confirmRulesButton || termsSections.length === 0) {
        return;
    }

    termsSections.forEach((section) => {
        section.classList.remove('d-none');
        section.classList.add('d-block');
        section.classList.add('terms-section-clickable');

        const counter = section.querySelector('p');
        if (counter && counter.querySelector('strong')) {
            counter.classList.add('d-none');
        }

        const checkbox = section.querySelector('.term-check');
        if (checkbox) {
            checkbox.classList.add('d-none');
        }

        section.addEventListener('click', (event) => {
            if (event.target.closest('a, button, input')) {
                return
            }

            const segments = getTermsSegmentsForSpeech()
            const clickedIndex = segments.findIndex((segment) => segment.element === section)

            if (clickedIndex === -1) {
                return
            }

            if ((window.speechSynthesis.speaking || window.speechSynthesis.paused) && currentTermsElement === section) {
                return
            }

            setActiveTermsSection(section)

            if (!('speechSynthesis' in window) || typeof SpeechSynthesisUtterance === 'undefined') {
                return
            }

            startTermsSpeechFromIndex(clickedIndex)
        })

        section.addEventListener('dblclick', (event) => {
            if (event.target.closest('a, button, input')) {
                return
            }

            const segments = getTermsSegmentsForSpeech()
            const clickedIndex = segments.findIndex((segment) => segment.element === section)

            if (clickedIndex === -1 || !('speechSynthesis' in window) || typeof SpeechSynthesisUtterance === 'undefined') {
                return
            }

            setActiveTermsSection(section)
            startTermsSpeechFromIndex(clickedIndex)
        })
    });

    confirmRulesButton.classList.remove('d-none');
    confirmRulesButton.disabled = true;

    const termsContainer = document.querySelector('.terms-container');
    if (!termsContainer) {
        return;
    }

    const existingTermsAccepted = document.getElementById('termsAccepted');
    if (existingTermsAccepted) {
        existingTermsAccepted.addEventListener('change', () => {
            confirmRulesButton.disabled = !existingTermsAccepted.checked;
            setAcceptedTermsInSession(existingTermsAccepted.checked)
        });
        const acceptedInSession = hasAcceptedTermsInSession()
        existingTermsAccepted.checked = acceptedInSession || existingTermsAccepted.checked
        confirmRulesButton.disabled = !existingTermsAccepted.checked;
        updateTermsNextStep()
        return;
    }

    const acceptanceWrapper = document.createElement('div');
    acceptanceWrapper.className = 'terms-acceptance form-check mt-3';
    acceptanceWrapper.innerHTML = `
        <input class="form-check-input" type="checkbox" id="termsAccepted">
        <label class="form-check-label fw-semibold" for="termsAccepted">
            Lei y acepto los terminos y condiciones de visita.
        </label>
    `;

    termsContainer.appendChild(acceptanceWrapper);

    const termsAccepted = document.getElementById('termsAccepted');
    termsAccepted?.addEventListener('change', () => {
        confirmRulesButton.disabled = !termsAccepted.checked;
        setAcceptedTermsInSession(termsAccepted.checked)
    });
    updateTermsNextStep()
}

document.addEventListener('change', (e) => {
    if (e.target?.id === 'termsAccepted' && confirmRulesButton) {
        confirmRulesButton.disabled = !e.target.checked;
        setAcceptedTermsInSession(e.target.checked)
    }
});

confirmRulesButton?.addEventListener('click', (event) => {
    if (!hasValidatedCustomerInSession()) {
        return
    }

    event.preventDefault()
    const welcomeModal = welcomeModalEl ? bootstrap.Modal.getOrCreateInstance(welcomeModalEl) : null
    welcomeModal?.hide()
})

showTermsLink?.addEventListener('click', () => {
    if (!welcomeModalEl) {
        return
    }

    const welcomeModal = bootstrap.Modal.getOrCreateInstance(welcomeModalEl)
    welcomeModal.show()
})

continueBookingStep?.addEventListener('click', async () => {
    if (!isAvailabilityStepComplete()) {
        showPublicNotice('Completa fecha, horarios, servicio y cantidad para continuar.', 'info', 'Falta informacion')
        return
    }

    await refreshBookingAmount()
    showBookingDetailsStep()
})

backBookingStep?.addEventListener('click', () => {
    showBookingAvailabilityStep()
})

horarioDesde.addEventListener('change', async () => {
    divTime.classList.remove('d-none');
    divTimeH.style.width = '49%';
    selectCancha.classList.remove('d-none');
    divqtyvisitors.classList.remove('d-none');
    inputqtyvisitors.disabled = false
    applyMinimumVisitorsDefault()

    if (!sessionUserLogued) {
        divMonto.classList.remove('d-none')
    }

    const indexDesde = horarioDesde.selectedIndex;

    // Resetear y limpiar opciones de horarioHasta
    inputMonto.value = 0;
    horarioHasta.value = '';

    for (let i = 0; i < horarioHasta.options.length; i++) {
        horarioHasta.options[i].disabled = true;
    }

    // Habilitar opciones de horarioHasta
    for (let offset = 4; indexDesde + offset < horarioHasta.options.length; offset += 4) {
        horarioHasta.options[indexDesde + offset].disabled = false;
    }

    // Seleccionar automÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ticamente la opciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n de 90 minutos
    if (indexDesde + 4 < horarioHasta.options.length) {
        horarioHasta.value = horarioHasta.options[indexDesde + 4].value;
    }

    // Mover esta lÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­nea para que se ejecute al final
    await getTimeFromBookings();
    await refreshBookingAmount();
});

document.addEventListener('change', async (e) => {
    if (e.target) {
        if (e.target.id == 'fecha') {
            selectedAvailabilityDate = normalizeAvailabilityDate(fechaInput.value || selectedAvailabilityDate)
            renderAvailabilityList(availabilityInlineResult)
            const day = parseInputDate(fechaInput.value);
            if (!day) {
                updateBookingStageAvailability()
                return;
            }
            const dayKey = getClosedDayKey(day);

            // Obtener el valor de cierre (debe ser '1' o 1 para estar cerrado)
            const isClosed = openingTime.closed[dayKey];

            // ÃƒÆ’Ã‚Â¢Ãƒâ€šÃ‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¸Ãƒâ€šÃ‚Â LÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œGICA DE VERIFICACIÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œN DE DÃƒÆ’Ã†â€™Ãƒâ€šÃ‚ÂA CERRADO
            // Verificamos si el valor de isClosed es '1' o el nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºmero 1.
            if (isClosed == 1) {
                const nextAvailableDate = new Date(day);
                nextAvailableDate.setDate(nextAvailableDate.getDate() + 1);
                const nextOpenDate = findNextOpenDate(nextAvailableDate);

                if (bookingDatePicker) {
                    bookingDatePicker.setDate(nextOpenDate, true);
                } else {
                    fechaInput.value = formatDateForDisplay(nextOpenDate);
                }

                // TambiÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©n puedes restablecer los selectores aquÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­ si prefieres un solo punto de salida:
                selectCancha.selectedIndex = 0;
                horarioDesde.selectedIndex = 0;
                horarioHasta.selectedIndex = 0;
                updateBookingStageAvailability()

                showPublicNotice('Ese dia el laberinto permanecera cerrado. Seleccione otra fecha.');
                return;
            }

            // Restablecer otros selectores SOLO si el dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­a NO estaba cerrado
            selectCancha.selectedIndex = 0;
            horarioDesde.selectedIndex = 0;
            horarioHasta.selectedIndex = 0;
            updateBookingStageAvailability()
        } else if (e.target.id == 'horarioDesde') {
            divTime.classList.remove('d-none');
            divTimeH.style.width = '49%';
            selectCancha.classList.remove('d-none');
            divqtyvisitors.classList.remove('d-none');
            inputqtyvisitors.disabled = false
            applyMinimumVisitorsDefault()

            if (!sessionUserLogued) {
                divMonto.classList.remove('d-none')
            }

            const indexDesde = horarioDesde.selectedIndex;

            // Resetear y limpiar opciones de horarioHasta
            inputMonto.value = 0;
            horarioHasta.value = '';

            for (let i = 0; i < horarioHasta.options.length; i++) {
                horarioHasta.options[i].disabled = true;
            }

            // Habilitar opciones de horarioHasta
            for (let offset = 4; indexDesde + offset < horarioHasta.options.length; offset += 4) {
                horarioHasta.options[indexDesde + offset].disabled = false;
            }

            // Seleccionar automÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ticamente la opciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n de 90 minutos
            if (indexDesde + 4 < horarioHasta.options.length) {
                horarioHasta.value = horarioHasta.options[indexDesde + 4].value;
            }

            // Mover esta lÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­nea para que se ejecute al final
            await getTimeFromBookings();
            await refreshBookingAmount();
            updateBookingStageAvailability()
        } else if (e.target.id == 'cancha') {
            await refreshBookingAmount()
            updateBookingStageAvailability()

        } else if (e.target.id == 'horarioHasta') {
            await refreshBookingAmount()
            updateBookingStageAvailability()

        } else if (e.target.id == 'switchPagoTotal') {
            const checkoutParcial = document.getElementById('checkout-btn-parcial')
            const checkoutTotal = document.getElementById('checkout-btn-total')

            if (pagoTotal.checked) {
                if (checkoutParcial) checkoutParcial.style.display = 'none'
                if (checkoutTotal) checkoutTotal.style.display = 'block'
            } else {
                if (checkoutParcial) checkoutParcial.style.display = 'block'
                if (checkoutTotal) checkoutTotal.style.display = 'none'
            }
        }
    }
})


document.addEventListener('click', async (e) => {
    if (e.target) {
        if (sessionUserLogued) {
            data = {
                fecha: normalizeDateValue(fecha.value),
                cancha: selectCancha.value,
                horarioDesde: horarioDesde.value,
                horarioHasta: horarioHasta.value,
                nombre: nombre.value,
                telefono: telefono.value,
                visitantes: inputqtyvisitors.value,
            }
        } else {
            data = {
                fecha: normalizeDateValue(fecha.value),
                cancha: cancha.value,
                horarioDesde: horarioDesde.value,
                horarioHasta: horarioHasta.value,
                nombre: nombre.value,
                telefono: telefono.value,
                email: inputEmail?.value || currentCustomer?.email || '',
                monto: pagoReserva.value,
                total: parseFloat(inputMonto.value),
                parcial: 0,
                diferencia: parseFloat(inputMonto.value) - pagoReserva.value,
                reservacion: pagoReserva.value,
                pagoTotal: pagoTotal.checked,
                metodoDePago: 'Mercado Pago',
                oferta: useOffer,
                visitantes: inputqtyvisitors.value,
                // idCliente: idCustomer,
            }
        }

        if (e.target.id == 'confirmarReserva') {
            const rate = await getRate()
            data.parcial = parseFloat(inputMonto.value) * rate.value / 100
            if (!validateVisitorsCount()) {
                return;
            }

            if (fecha.value == '' || cancha.value == '' || horarioDesde.value == '' || horarioHasta.value == '' || nombre.value == '' || telefono.value == '') {
                showPublicNotice('Debe completar todos los datos')
                return
            }

            if (horarioDesde.value == '23' && horarioHasta.value == '00' || horarioDesde.value == '23' && horarioHasta.value == '01' || horarioDesde.value == '22' && horarioHasta.value == '00' || horarioDesde.value == '22' && horarioHasta.value == '01') {
            } else if (parseInt(horarioDesde.value) >= parseInt(horarioHasta.value)) {
                showPublicNotice('El horario de comienzo no puede ser mayor al de fin')
                return;
            }

            await fetchFormInfo(data)
            modalConfirmarReserva.show()

        } else if (e.target.id == 'buttonCancel' || e.target.id == 'btnClose' || e.target.id == 'cancelarReserva') {
            location.reload(true)
        } else if (e.target.id == 'switchPagoTotal') {
            const switchPagoTotal = document.getElementById('switchPagoTotal')
            const rate = await getRate()
            const parcial = parseFloat(inputMonto.value) * rate.value / 100
            const total = parseFloat(inputMonto.value)

            if (switchPagoTotal.checked) {
                // if (nocturnalTime.time.includes(horarioDesde.value) && nocturnalTime.time.includes(horarioHasta.value)) {
                //     pagoReserva.value = parseFloat(inputMonto.value)
                // } else {
                //     pagoReserva.value = parseFloat(inputMonto.value)
                // }
                pagoReserva.value = total
            } else {
                pagoReserva.value = parcial
            }
        } else if (e.target.id == 'abonarReservaBoton') { //Por defecto me va a traer el valor del porcentual
            const rate = await getRate()
            const openPaymentFlow = async () => {
                publicBookingPrepared = false
                preferencesIds = {}
                data.preferenceIdParcial = null
                data.preferenceIdTotal = null
                pendingMpContext = null
                resetMercadoPagoButtons()

                modalConfirmarReserva.hide()
                modalIngresarPago.show()

                const preferences = await setScriptMP(parseFloat(inputMonto.value))
                if (!preferences) {
                    skipCancelOnHide = true
                    modalIngresarPago.hide()
                    return
                }

                if (pagoReserva) {
                    pagoReserva.value = parseFloat(inputMonto.value) * rate.value / 100
                }
                const amount = document.getElementById('adminBookingAmount')
                const description = document.getElementById('adminBookingDescription')
                const totalReserva = document.getElementById('adminBookingTotalAmount')

                if (amount) {
                    amount.value = inputMonto.value * rate.value / 100
                }

                if (totalReserva) {
                    totalReserva.value = inputMonto.value
                }

                if (pagoReserva) {
                    pagoReserva.value = parseFloat(inputMonto.value) * rate.value / 100
                }
            }

            showPublicNoticeWithAction(
                'Al realizar el pago de una reserva, se asume el compromiso y la responsabilidad de asistir en el dia y horario acordados. En caso de inasistencia, no se realizaran devoluciones de dinero y la reprogramacion quedara sujeta a disponibilidad.',
                'info',
                'Importante',
                openPaymentFlow
            )
        } else if (e.target.id == 'confirmBooking') {
            const amount = document.getElementById('adminBookingAmount')
            const paymentMethod = document.getElementById('adminPaymentMethod')
            const description = document.getElementById('adminBookingDescription')
            const totalReserva = document.getElementById('adminBookingTotalAmount')

            data.monto = amount.value
            data.metodoDePago = paymentMethod.value
            data.descripcion = description.value
            data.total = totalReserva.value
            data.idCustomer = currentCustomer ? currentCustomer.id : null

            saveAdminBooking(data)
        } else if (e.target.id == 'confirmarAdminReserva') {
            if (!validateVisitorsCount()) {
                return;
            }

            if (nombre.value == '' || telefono.value == '') {
                showPublicNotice('Debe completar todos los datos')
                return
            }

            fetchFormInfo(data)

            modalConfirmarReserva.show()
        } else if (e.target.id == 'showAvailability') {
            modalAvailability.show();

            // Limpiamos el contenido del modal antes de agregar el nuevo
            availabilityResult.innerHTML = '';

            // Asumimos que 'availables' es el objeto completo con la propiedad 'availability'
            const availabilityData = availables.availability;

            if (availabilityData && availabilityData.length > 0) {
                // Recorremos cada dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­a disponible
                availabilityData.forEach(day => {
                    // console.log(day);

                    // La lÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³gica de validaciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n se simplifica.
                    // Verificamos si el array tiene un solo elemento y si ese elemento
                    // es la cadena de "cerrado" generada por el backend.
                    const isClosed = day.available_slots.length === 1 && day.available_slots[0].startsWith('Cerrado los ');

                    // Creamos una secciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n para cada fecha
                    let dayContent = `
                <h5 class="text-xl font-semibold mt-4 text-gray-800">${day.date}</h5>
                <hr class="my-2 border-gray-300">
                <div class="grid grid-cols-2 gap-4">
            `;

                    if (isClosed) {
                        // Si el dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­a estÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ cerrado, mostramos un solo mensaje
                        dayContent += `
                    <div class="col-span-2 text-center text-gray-600 p-2 rounded-lg bg-gray-100">
                        ${day.available_slots[0]}
                    </div>
                `;
                    } else {
                        // Recorremos cada uno de los lapsos disponibles para ese dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­a
                        day.available_slots.forEach(slot => {
                            const [start, end] = slot.split(' a ');

                            let selectButton = `
                        <button 
                            type="button"
                            class="btn btn-sm btn-outline-success"
                            data-date="${day.date}" 
                            data-slot-start="${start}" 
                            data-slot-end="${end}"
                            id="selectSlotButton"
                        >
                            Seleccionar <i class="fa-solid fa-square-check" style="color: #0d6a3a"></i>
                        </button>
                    `;

                            dayContent += `
                        <div class="flex items-center justify-between bg-gray-100 p-1 rounded-lg shadow-sm hover:bg-gray-200 transition-colors duration-200">
                            <span class="text-gray-800 font-medium text-sm">${slot}</span>
                            ${selectButton}
                        </div>
                    `;
                        });
                    }

                    dayContent += `</div>`; // Cierre del contenedor de la cuadrÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­cula

                    // Agregamos el contenido completo del dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­a al modal
                    availabilityResult.innerHTML += dayContent;
                });
            } else {
                availabilityResult.innerHTML = '<p class="text-center text-gray-500">No hay servicios disponibles en los horarios seleccionados.</p>';
            }
        } else if (e.target.dataset?.availabilityDate) {
            setSelectedAvailabilityDate(e.target.dataset.availabilityDate)
            renderAvailabilityList(availabilityInlineResult)
        } else if (e.target.id === 'availabilityPrevDates') {
            const renderableDates = getRenderableAvailabilityDates()
            const lastPageStart = Math.floor((Math.max(0, renderableDates.length - 1)) / availabilityPageSize) * availabilityPageSize
            availabilityPageStart = availabilityPageStart <= 0
                ? lastPageStart
                : Math.max(0, availabilityPageStart - availabilityPageSize)
            const previousPageDate = renderableDates[availabilityPageStart]?.date || ''
            if (previousPageDate) {
                setSelectedAvailabilityDate(previousPageDate)
            }
            renderAvailabilityList(availabilityInlineResult)
        } else if (e.target.id === 'availabilityNextDates') {
            const renderableDates = getRenderableAvailabilityDates()
            const lastPageStart = Math.floor((Math.max(0, renderableDates.length - 1)) / availabilityPageSize) * availabilityPageSize
            availabilityPageStart = availabilityPageStart >= lastPageStart
                ? 0
                : Math.min(availabilityPageStart + availabilityPageSize, lastPageStart)
            const nextPageDate = renderableDates[availabilityPageStart]?.date || ''
            if (nextPageDate) {
                setSelectedAvailabilityDate(nextPageDate)
            }
            renderAvailabilityList(availabilityInlineResult)
        } else if (e.target.id === 'selectSlotButton') {
            selectSlotButton = e.target;
            const selectedDate = selectSlotButton.getAttribute('data-date');
            const selectedSlotStart = selectSlotButton.getAttribute('data-slot-start');
            const selectedSlotEnd = selectSlotButton.getAttribute('data-slot-end');

            setSelectedAvailabilityDate(selectedDate)
            horarioDesde.value = selectedSlotStart;
            horarioHasta.value = selectedSlotEnd;
            // console.log(horarioDesde);
            modalAvailability.hide();

            const event = new Event('change');
            horarioDesde.dispatchEvent(event);
            updateBookingStageAvailability()
        }

    }
})

async function getValue(type) {
    const response = await fetch(`${baseUrl}getValue/${type}`);
    const responseData = await response.json();

    if (responseData.data) {
        const amount = parseFloat(responseData.data.amount);
        serviceValue = amount;
        return amount
    }

    return null
}

async function refreshBookingAmount() {
    if (!inputMonto || !horarioDesde.value || !horarioHasta.value) {
        return
    }

    const visitors = Number(inputqtyvisitors?.value || 0)

    if (!visitors) {
        inputMonto.value = '0'
        return
    }

    let totalAmount = null
    const institutionType = currentCustomer?.type_institution || ''

    if (institutionType) {
        const amountByInstitution = await getValue(institutionType)

        if (Number.isFinite(amountByInstitution) && amountByInstitution > 0) {
            serviceValue = amountByInstitution
            totalAmount = (visitors * amountByInstitution) - ((visitors * amountByInstitution) * getCurrentCustomerOffer() / 100)
        }
    }

    if (totalAmount === null) {
        const selectedFieldId = selectCancha?.value || ''

        if (!selectedFieldId) {
            inputMonto.value = '0'
            return
        }

        const [nocturnalTime, selectedField] = await Promise.all([
            getNocturnalTime(),
            getField(selectedFieldId)
        ])

        if (!nocturnalTime || !selectedField) {
            inputMonto.value = '0'
            return
        }

        const usesNocturnalRate = nocturnalTime.time.includes(horarioDesde.value) && nocturnalTime.time.includes(horarioHasta.value)
        const fieldAmount = parseFloat(usesNocturnalRate ? selectedField.ilumination_value : selectedField.value)

        if (!Number.isFinite(fieldAmount)) {
            inputMonto.value = '0'
            return
        }

        serviceValue = fieldAmount
        totalAmount = calculateAmount(horarioDesde.value, horarioHasta.value, fieldAmount)
        totalAmount = totalAmount - (totalAmount * getCurrentCustomerOffer() / 100)
    }

    inputMonto.value = formatAmountValue(totalAmount)
}


function checkSunday() {
    const today = new Date();
    const dayOfWeek = today.getDay();
    const form = document.getElementById("formBooking")
    const container = document.getElementById("isSunday")

    if (dayOfWeek === 0) {
        form.classList.add("d-none")
        container.classList.remove("d-none")
    }
}

inputqtyvisitors.addEventListener('input', () => {
    if (Number(minVisitantes) > 0 && Number(inputqtyvisitors.value) < Number(minVisitantes) && inputqtyvisitors.value !== '') {
        inputqtyvisitors.setCustomValidity(`La cantidad minima es ${minVisitantes}`)
    } else {
        inputqtyvisitors.setCustomValidity('')
    }

    refreshBookingAmount()
    updateBookingStageAvailability()
});

inputqtyvisitors.addEventListener('blur', () => {
    if (!inputqtyvisitors.value || inputqtyvisitors.value === '0') {
        applyMinimumVisitorsDefault(true)
    }

    validateVisitorsCount()
    updateBookingStageAvailability()
})

telefono.addEventListener('input', async () => {
    const phone = String(telefono.value)

    if (phone.length >= 7) {
        modalSpinner.show()

        try {
            const customer = await getCustomer(telefono.value)

            if (customer) {
                currentCustomer = customer
                divMonto.classList.remove('d-none')
                nombre.value = customer.name
                inputqtyvisitors.disabled = false
                applyMinimumVisitorsDefault()
            } else {
                currentCustomer = {
                    id: null,
                    phone: telefono.value,
                    offer: 0,
                }
            }

            await refreshBookingAmount()
        } catch (error) {
            console.error('Error:', error)
            showPublicNotice('No se pudo validar el cliente en este momento.')
        } finally {
            setTimeout(() => { modalSpinner.hide() }, 300);
        }
    }
})

async function deleteRejected() {
    try {
        const response = await fetch(`${baseUrl}deleteRejected`);

        const responseData = await response.json();

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}


async function saveAdminBooking(data) {
    modalIngresarPago.hide()

    if (data.metodoDePago == '') {
        showPublicNotice('Debe seleccionar un medio de pago')
        return;
    }

    try {
        const response = await fetch(`${baseUrl}saveAdminBooking`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {

            modalResult.show()

            contentBookingResult.innerHTML = `
            <div class="modal-body modalResultPayment d-flex justify-content-center align-items-center flex-column" style="background-color: #157347; color: #fff">
                <h4 class="mb-5">Reserva confirmada!</h4>
                <i class="fa-regular fa-circle-check fa-2xl" style="margin-bottom: 20px;"></i>
            </div>`
        }

        setTimeout(() => {
            modalResult.hide();
            location.reload(true)
        }, 2000);
        // const responseData = await response.json();

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

function buildPublicPaymentData(rateValue, paymentType = 'parcial') {
    const totalAmount = Number(inputMonto?.value || 0)
    const partialAmount = totalAmount * Number(rateValue || 0) / 100
    const amountToPay = paymentType === 'total' ? totalAmount : partialAmount

    data.email = inputEmail?.value || currentCustomer?.email || data.email || ''
    data.monto = amountToPay
    data.total = totalAmount
    data.parcial = partialAmount
    data.diferencia = totalAmount - amountToPay
    data.reservacion = amountToPay
    data.pagoTotal = paymentType === 'total'
    data.metodoDePago = 'Mercado Pago'
    data.oferta = useOffer
}

async function setScriptMP(amount) {
    const numericAmount = Number(amount)

    if (!Number.isFinite(numericAmount) || numericAmount <= 0) {
        showPublicNotice('El monto de la reserva no es valido. Revise el servicio, horario y cantidad de personas.')
        return null
    }

    if (preferencesIds?.preferenceIdParcial && preferencesIds?.preferenceIdTotal) {
        applyPreferenceIdsToBookingData(preferencesIds)
        return preferencesIds
    }

    modalSpinner.show()
    resetMercadoPagoButtons()

    try {
        const preferences = await setPreference(`${baseUrl}setPreference`, {
            amount: numericAmount,
            booking: data,
        })

        if (!preferences?.preferenceIdParcial || !preferences?.preferenceIdTotal) {
            throw new Error('No se pudieron generar las preferencias de pago')
        }

        if (!publicKeyMp) {
            throw new Error('No se encontro la clave publica de Mercado Pago.')
        }

        const mp = new MercadoPago(publicKeyMp, {
            locale: 'es-AR'
        })

        resetMercadoPagoButtons()
        mp.checkout({
            preference: {
                id: preferences.preferenceIdParcial
            },
            render: {
                container: '#checkout-btn-parcial',
                label: 'Pagar con Mercado Pago'
            }
        })

        mp.checkout({
            preference: {
                id: preferences.preferenceIdTotal
            },
            render: {
                container: '#checkout-btn-total',
                label: 'Pagar con Mercado Pago'
            }
        })

        applyPreferenceIdsToBookingData(preferences)
        preferencesIds = preferences
        data.pendingBookingId = preferences.bookingId || null
        pendingMpContext = {
            bookingId: preferences.bookingId || null,
            preferenceIdParcial: preferences.preferenceIdParcial || null,
            preferenceIdTotal: preferences.preferenceIdTotal || null,
        }

        if (pendingMpCleanupTimer) {
            clearTimeout(pendingMpCleanupTimer)
        }

        pendingMpCleanupTimer = setTimeout(async () => {
            await cancelPendingMpReservation()
        }, 3 * 60 * 1000)

        return preferences
    } catch (error) {
        console.error('Error:', error)
        showPublicNotice('No se pudo preparar el pago de la reserva. Intente nuevamente.')
        return null
    } finally {
        modalSpinner.hide()
    }
}


async function savePreferenceIds(data) {
    try {
        const response = await fetch(`${baseUrl}savePreferenceIds`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const responseData = await response.json();

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function setPreference(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const raw = await response.text()
        const responseData = raw ? JSON.parse(raw) : null

        if (!response.ok || responseData?.error) {
            throw new Error(responseData?.message || 'El horario seleccionado ya no esta disponible. Elegi otro e intenta nuevamente.')
        }

        return responseData.data

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function cancelPendingMpReservation() {
    if (!pendingMpContext?.bookingId && !pendingMpContext?.preferenceIdParcial && !pendingMpContext?.preferenceIdTotal) {
        return
    }

    try {
        await fetch(`${baseUrl}cancelPendingMpReservation`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(pendingMpContext)
        })
    } catch (error) {
        console.error('Error:', error)
    } finally {
        if (pendingMpCleanupTimer) {
            clearTimeout(pendingMpCleanupTimer)
            pendingMpCleanupTimer = null
        }

        pendingMpContext = null
        publicBookingPrepared = false
        preferencesIds = {}
        data.pendingBookingId = null
        data.preferenceIdParcial = null
        data.preferenceIdTotal = null
        resetMercadoPagoButtons()
    }
}


// async function getAmount(field = "1") {
//     try {
//         const nocturnalTime = await getNocturnalTime()
//         const selectedField = await getField(field)

//         if (nocturnalTime.time.includes(horarioDesde.value) && nocturnalTime.time.includes(horarioHasta.value)) {
//             serviceValue = selectedField.ilumination_value
//             inputMonto.value = `${calculateAmount(horarioDesde.value, horarioHasta.value, selectedField.ilumination_value)}`
//         } else {
//             serviceValue = selectedField.value
//             inputMonto.value = `${calculateAmount(horarioDesde.value, horarioHasta.value, selectedField.value)}`
//         }
//     } catch (error) {
//         console.error('Error:', error);
//         throw error;
//     }
// }

async function getRate() {
    try {
        const response = await fetch(`${baseUrl}getRate`);
        const responseData = await response.json();


        if (responseData.data != '') {
            minVisitantes = responseData.data.qty_visitors
            updateVisitorsFieldConfig()
            applyMinimumVisitorsDefault()
            return responseData.data
        } else {
            showPublicNotice('Algo salio mal. No se pudo obtener la informacion.');
        }
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function getTime() {
    try {
        const response = await fetch(`${baseUrl}getTime`);
        const responseData = await response.json();


        if (responseData.data != '') {
            openingTime = responseData.data
            return responseData.data
        } else {
            showPublicNotice('Algo salio mal. No se pudo obtener la informacion.');
        }
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function getOffer() {
    try {
        const response = await fetch(`${baseUrl}getOffersRate`);
        const responseData = await response.json();


        if (responseData.data != '') {

            return responseData.data
        } else {
            showPublicNotice('Algo salio mal. No se pudo obtener la informacion.');
        }
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}


async function getNocturnalTime() {
    try {
        const response = await fetch(`${baseUrl}getNocturnalTime`);
        const responseData = await response.json();

        if (responseData?.error) {
            throw new Error(responseData.message || 'No se pudo obtener la informacion.')
        }

        const nocturnalTime = { time: Array.isArray(responseData?.data) ? responseData.data : [] }

        return nocturnalTime
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}


async function getFields() {
    try {
        const response = await fetch(`${baseUrl}getFields`);

        const responseData = await response.json();

        if (responseData.data != '') {

            return responseData.data

        } else {
            showPublicNotice('Algo salio mal. No se pudo obtener la informacion.');
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

// Busca la cancha seleccionada para colocar valor
async function getField(id) {
    try {
        const response = await fetch(`${baseUrl}getField/${id}`);

        const responseData = await response.json();

        if (responseData.data != '') {

            return responseData.data

        } else {
            showPublicNotice('Algo salio mal. No se pudo obtener la informacion.');
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}


// Guarda reserva
async function saveBooking(data) {

    try {
        const response = await fetch(`${baseUrl}saveBooking`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const responseData = await response.json();

        if (!response.ok || responseData?.error) {
            throw new Error(responseData?.message || 'No se pudo guardar la reserva');
        }

        return responseData
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

// Trae la informaciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n a mostrar en el modal
async function fetchFormInfo(data) {
    try {
        const response = await fetch(`${baseUrl}formInfo`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const responseData = await response.json();

        // console.log(responseData)

        if (responseData.data != '') {
            fillModal(responseData);
        } else {
            showPublicNotice('Algo salio mal. No se pudo obtener la informacion.');
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function getCustomer(phone) {
    try {
        const response = await fetch(`${baseUrl}getCustomer/${phone}`);
        const responseData = await response.json();

        if (responseData.data != '') {

            return responseData.data

        } else {
            return null
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function validateCustomer(phone, email) {
    try {
        const normalizedPhone = encodeURIComponent((phone || '').trim())
        const normalizedEmail = encodeURIComponent((email || '').trim())
        const response = await fetch(`${baseUrl}validateCustomer/${normalizedPhone}/${normalizedEmail}`);

        if (!response.ok) {
            throw new Error(`validateCustomer ${response.status}`)
        }

        const responseData = await response.json();

        if (responseData?.error) {
            throw new Error(responseData.message || 'Error validando cliente')
        }

        if (responseData?.data) {
            currentCustomer = responseData.data
            return responseData.data

        } else {
            return null
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

validateDataButton?.addEventListener('click', async (event) => {
    event.preventDefault()
    await handleValidateCustomerClick()
})

// Rellena el modal
async function fillModal(data) {

    const modalBody = document.querySelector('.modal-resume-body')
    let amount = inputMonto.value

    if (data == '') {
        return;
    }

    let info = '';

    const fecha = convertDateFormat(data?.data?.fecha)

    if (sessionUserLogued) {
        info =
            `
        <ul id="bookingDetail">
            <li><b>Fecha:</b> ${fecha}</li>
            <li><b>Servicio:</b> ${data?.data?.cancha}</li>
            <li><b>Visitantes:</b> ${data?.data?.visitantes}</li>
            <li><b>Horario:</b> ${data?.data?.horarioDesde} a ${data?.data?.horarioHasta}</li>
            <li><b>Nombre:</b> ${data?.data?.nombre}</li>
            <li><b>Telefono:</b> ${data?.data?.telefono}</li>
        </ul>
        `;
    } else {
        info =
            `
        <ul id="bookingDetail">
            <li><b>Fecha:</b> ${fecha}</li>
            <li><b>Servicio:</b> ${data?.data?.cancha}</li>
            <li><b>Visitantes:</b> ${data?.data?.visitantes}</li>
            <li><b>Horario:</b> ${data?.data?.horarioDesde} a ${data?.data?.horarioHasta}</li>
            <li><b>Monto:</b> $${amount}</li>
            <li><b>Nombre:</b> ${data?.data?.nombre}</li>
            <li><b>Telefono:</b> ${data?.data?.telefono}</li>
        </ul>
        `;
    }



    modalBody.innerHTML = info;
}

function convertDateFormat(date) {
    if (!date) {
        return ''
    }

    if (date.includes('/')) {
        return date
    }

    return date.split("-").reverse().join("/")
}


// Trae los horarios de las reservas hechas
async function getTimeFromBookings() {
    const fecha = normalizeDateValue(document.getElementById('fecha').value)

    try {
        const response = await fetch(`${baseUrl}getBookings/${fecha}`);
        const responseData = await response.json();

        if (responseData.data != null && responseData.data != '') {
            getFieldForTimeBookings(responseData.data);
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function getAvailability() {
    // Dejamos fijo el ID 1 por ahora, luego traeremos el id del servicio seleccionado
    try {
        const response = await fetch(`${baseUrl}scheduleAvailability/1`);
        const responseData = await response.json();

        if (responseData.data != null && responseData.data != '') {
            availables = responseData.data
            renderAvailabilityList(availabilityInlineResult)
            // console.log(availables)
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}


async function getFieldForTimeBookings(data) {
    const timeBookings = data.reservas;
    const todasLasCanchas = data.canchas;

    const currentDesde = horarioDesde.value;
    const currentHasta = horarioHasta.value;

    const indexDesde = time.indexOf(currentDesde);
    const indexHasta = time.indexOf(currentHasta);

    const rangoSeleccionado = time.slice(indexDesde, indexHasta);

    // Limpiar y preparar el select
    selectCancha.innerHTML = '';
    const defaultOption = new Option('Servicios disponibles', '');
    selectCancha.appendChild(defaultOption);

    todasLasCanchas.forEach(cancha => {
        const reservasDeCancha = timeBookings.find(e => e.id_cancha == cancha.id);
        const horariosOcupados = reservasDeCancha ? reservasDeCancha.time : [];

        // Creamos un nuevo array que excluye el ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºltimo horario de la reserva.
        // Esto permite que el horario final de una reserva se convierta en el de inicio para la siguiente.
        const horariosOcupadosSinFinal = horariosOcupados.slice(0, -1);

        // Ahora verificamos si algÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºn horario seleccionado se cruza con los horarios ocupados.
        const hayCruce = rangoSeleccionado.some(hora => horariosOcupadosSinFinal.includes(hora));

        if (!hayCruce) {
            const nuevaOpcion = new Option(cancha.name, cancha.id);
            if (cancha.id == 1) {
                nuevaOpcion.selected = true;
            }
            selectCancha.appendChild(nuevaOpcion);
        }
    });

    if (selectCancha.options.length === 1) {

        selectCancha.options[0].text = 'No hay servicios disponibles en este horario';
        selectCancha.style.backgroundColor = '#e74959ff';
    } else {
        selectCancha.style.backgroundColor = '';
    }
}


// Calcula el total $ de la reserva

function calculateAmount(from, until, amount) {
    const [fromHour, fromMinute] = from.split(':').map(Number);
    const [untilHour, untilMinute] = until.split(':').map(Number);

    let fromTotal = fromHour * 60 + fromMinute;
    let untilTotal = untilHour * 60 + untilMinute;

    // Ajuste para cruces de medianoche
    if (untilTotal <= fromTotal) {
        untilTotal += 24 * 60;
    }

    const durationInMinutes = untilTotal - fromTotal;

    // ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ Dividimos por 90 minutos (1.5 horas)
    const bloquesDeHoraYMedia = durationInMinutes / 90;

    const result = bloquesDeHoraYMedia * parseFloat(amount);

    return result;
}
