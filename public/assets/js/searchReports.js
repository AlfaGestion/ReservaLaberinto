const fechaDesde = document.getElementById('buscarFechaDesde')
const fechaHasta = document.getElementById('buscarFechaHasta')
const selectUser = document.getElementById('selectUserReport')
const generateReportButton = document.getElementById('generateReport')
const downloadReportButton = document.getElementById('downloadReport')
const downloadPaymentsReportButton = document.getElementById('downloadPaymentsReport')
const rateModalElement = document.getElementById('rateModal')
const rateModal = rateModalElement ? new bootstrap.Modal(rateModalElement) : null
const generateReportModalElement = document.getElementById('generateReportModal')
const generateReportModal = generateReportModalElement ? new bootstrap.Modal(generateReportModalElement) : null
const switchPaymentsMp = document.getElementById('checkPaymetsMp')
const reservePaymentsButton = document.getElementById('reservePayments')
const searchReportsButton = document.getElementById('searchReports')
const selectDateRange = document.getElementById('selectDateRange')
const selectDateReport = document.getElementById('selectDateReport')
const tableReports = document.getElementById('tableReports')
const tableReservations = document.getElementById('tableReservations')
const reportsSummary = document.getElementById('reportsSummary')
const reservationsSummary = document.getElementById('reservationsSummary')

function formatMoney(value) {
    return new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(Number(value || 0))
}

function formatLocalDate(date) {
    const year = date.getFullYear()
    const month = `${date.getMonth() + 1}`.padStart(2, '0')
    const day = `${date.getDate()}`.padStart(2, '0')
    return `${year}-${month}-${day}`
}

function getMondayOfWeek(referenceDate = new Date()) {
    const date = new Date(referenceDate)
    const day = date.getDay()
    const diff = day === 0 ? -6 : 1 - day
    date.setDate(date.getDate() + diff)
    return date
}

function applySelectedDateRange(range) {
    if (!fechaDesde || !fechaHasta) {
        return
    }

    const today = new Date()

    if (range === 'FD') {
        fechaDesde.value = formatLocalDate(today)
        fechaHasta.value = formatLocalDate(today)
        return
    }

    if (range === 'MA') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0)
        fechaDesde.value = formatLocalDate(firstDay)
        fechaHasta.value = formatLocalDate(lastDay)
        return
    }

    if (range === 'MP') {
        const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1)
        const firstDay = new Date(lastMonth.getFullYear(), lastMonth.getMonth(), 1)
        const lastDay = new Date(lastMonth.getFullYear(), lastMonth.getMonth() + 1, 0)
        fechaDesde.value = formatLocalDate(firstDay)
        fechaHasta.value = formatLocalDate(lastDay)
        return
    }

    if (range === 'SA') {
        const weekStart = selectDateReport?.dataset.weekStart || formatLocalDate(getMondayOfWeek(today))
        const weekStartDate = new Date(`${weekStart}T00:00:00`)
        const weekEndDate = new Date(weekStartDate)
        weekEndDate.setDate(weekStartDate.getDate() + 6)
        fechaDesde.value = weekStart
        fechaHasta.value = formatLocalDate(weekEndDate)
        return
    }

    if (range === 'SP') {
        const currentWeekStart = getMondayOfWeek(today)
        const previousWeekStart = new Date(currentWeekStart)
        previousWeekStart.setDate(currentWeekStart.getDate() - 7)
        const previousWeekEnd = new Date(previousWeekStart)
        previousWeekEnd.setDate(previousWeekStart.getDate() + 6)
        fechaDesde.value = formatLocalDate(previousWeekStart)
        fechaHasta.value = formatLocalDate(previousWeekEnd)
    }
}

function loadCurrentWeekReports() {
    const data = {
        fechaDesde: fechaDesde.value,
        fechaHasta: fechaHasta.value,
        user: selectUser?.value || '',
    }

    tableReports?.classList.remove('d-none')
    tableReservations?.classList.add('d-none')
    generateReportButton?.classList.remove('d-none')
    downloadReportButton?.classList.remove('d-none')
    downloadPaymentsReportButton?.classList.add('d-none')

    getReports(data)
}

document.addEventListener('DOMContentLoaded', () => {
    if (!selectDateRange || !fechaDesde || !fechaHasta) {
        return
    }

    selectDateRange.value = 'SA'
    applySelectedDateRange('SA')
})

function handleDateRangeChange() {
    applySelectedDateRange(selectDateRange.value)
}

selectDateRange?.addEventListener('change', handleDateRangeChange)
selectDateRange?.addEventListener('input', handleDateRangeChange)

switchPaymentsMp?.addEventListener('change', () => {
    if (switchPaymentsMp.checked) {
        reservePaymentsButton?.classList.remove('d-none')
        searchReportsButton?.classList.add('d-none')
        generateReportButton?.classList.add('d-none')
        downloadReportButton?.classList.add('d-none')
        downloadPaymentsReportButton?.classList.add('d-none')
        selectUser?.classList.add('d-none')
    } else {
        searchReportsButton?.classList.remove('d-none')
        reservePaymentsButton?.classList.add('d-none')
        generateReportButton?.classList.add('d-none')
        downloadReportButton?.classList.add('d-none')
        downloadPaymentsReportButton?.classList.add('d-none')
        selectUser?.classList.remove('d-none')
    }
})

document.addEventListener('change', (e) => {
    if (e.target?.id === 'selectUserReport') {
        generateReportButton?.classList.add('d-none')
        downloadReportButton?.classList.add('d-none')
        reservePaymentsButton?.classList.add('d-none')
        searchReportsButton?.classList.remove('d-none')
        if (switchPaymentsMp) {
            switchPaymentsMp.checked = false
        }
    }
})

document.addEventListener('click', async (e) => {
    if (!e.target) {
        return
    }

    if (e.target.id === 'searchReports') {
        const data = {
            fechaDesde: fechaDesde.value,
            fechaHasta: fechaHasta.value,
            user: selectUser?.value || '',
        }

        tableReports?.classList.remove('d-none')
        tableReservations?.classList.add('d-none')
        generateReportButton?.classList.remove('d-none')
        downloadReportButton?.classList.remove('d-none')
        downloadPaymentsReportButton?.classList.add('d-none')

        getReports(data)
        return
    }

    if (e.target.id === 'nav-reports-tab') {
        tableReservations?.classList.add('d-none')
        loadCurrentWeekReports()
        return
    }

    if (e.target.id === 'openRateModal') {
        rateModal?.show()
        return
    }

    if (e.target.id === 'generateReport') {
        generateReportModal?.show()
        return
    }

    if (e.target.id === 'reservePayments') {
        const data = {
            fechaDesde: fechaDesde.value,
            fechaHasta: fechaHasta.value,
        }

        tableReports?.classList.add('d-none')
        tableReservations?.classList.remove('d-none')
        generateReportButton?.classList.remove('d-none')
        downloadPaymentsReportButton?.classList.remove('d-none')
        downloadReportButton?.classList.add('d-none')

        getMpPayments(data)
        return
    }

    if (e.target.id === 'downloadReport') {
        const buscarFechaDesde = fechaDesde.value
        const buscarFechaHasta = fechaHasta.value
        const idUser = selectUser?.value === '' ? 'all' : (selectUser?.value || 'all')

        const a = document.createElement('a')
        a.href = `${baseUrl}generateReportPdf/${idUser}/${buscarFechaDesde}/${buscarFechaHasta}`
        a.target = '_blank'
        a.click()
        return
    }

    if (e.target.id === 'downloadPaymentsReport') {
        const buscarFechaDesde = fechaDesde.value
        const buscarFechaHasta = fechaHasta.value

        const a = document.createElement('a')
        a.href = `${baseUrl}generatePaymentsReportPdf/${buscarFechaDesde}/${buscarFechaHasta}`
        a.target = '_blank'
        a.click()
    }
})

async function getMpPayments(data) {
    try {
        const response = await fetch(`${baseUrl}getMpPayments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })

        const responseData = await response.json()
        fillReservations(responseData.data || [])
    } catch (error) {
        console.error('Error:', error)
        throw error
    }
}

async function fillReservations(data) {
    const divReservas = document.querySelector('.divReservas')
    const resumePayments = document.querySelector('.paymentsMethodsResume')

    let tr = ''
    let resume = ''
    let totalReservations = 0

    data.forEach((pago) => {
        tr += `
        <tr>
            <td>${pago.fecha}</td>
            <td>$${pago.reserva}</td>
        </tr>
    `

        totalReservations += Number(pago.reserva)
    })

    resume = `
        <p>Total: <b>$${formatMoney(totalReservations)}</b></p>
    `

    if (divReservas) {
        divReservas.innerHTML = tr
    }
    if (reservationsSummary) {
        reservationsSummary.innerHTML = resume
    } else if (resumePayments) {
        resumePayments.innerHTML = resume
    }
}

async function getReports(data) {
    try {
        const response = await fetch(`${baseUrl}getReports`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })

        const responseData = await response.json()
        fillTable(responseData.data || [])
    } catch (error) {
        console.error('Error:', error)
        throw error
    }
}

async function fillTable(data) {
    const divTr = document.querySelector('.divTr')
    const resumePayments = document.querySelector('.paymentsMethodsResume')
    const reportsCount = document.getElementById('reportsCount')

    let tr = ''
    let resume = ''
    let efectivo = 0
    let transferencia = 0
    let mercadoPago = 0
    let reservasCount = 0
    let totalImporte = 0

    const formatMetodo = (m) => {
        if (m === 'mercado_pago' || m === 'Mercado Pago') return 'Mercado Pago'
        if (m === 'efectivo') return 'Efectivo'
        if (m === 'transferencia') return 'Transferencia'
        return m || 'N/D'
    }

    const groups = new Map()
    data.forEach((pago, index) => {
        const fallbackKey = `${pago.fecha || ''}-${pago.cliente || ''}-${pago.telefonoCliente || ''}-${pago.totalReserva || ''}`
        const key = pago.bookingId ? String(pago.bookingId) : (fallbackKey || `no-booking-${index}`)
        if (!groups.has(key)) {
            groups.set(key, {
                bookingId: pago.bookingId || null,
                fecha: pago.fecha,
                usuario: pago.usuario,
                cliente: pago.cliente,
                telefono: pago.telefonoCliente,
                totalReserva: pago.totalReserva ? Number(pago.totalReserva) : null,
                pagos: [],
            })
        }

        groups.get(key).pagos.push({
            metodo: pago.metodoPago,
            monto: Number(pago.pago),
        })
    })

    groups.forEach((group) => {
        reservasCount += 1
        const totalPagado = group.pagos.reduce((acc, pago) => acc + Number(pago.monto || 0), 0)
        const totalReserva = group.totalReserva ?? totalPagado
        const saldo = totalReserva - totalPagado
        totalImporte += Number(totalReserva || 0)
        const methods = Array.from(new Set(group.pagos.map((pago) => formatMetodo(pago.metodo))))
        const methodSummary = methods.length > 1 ? methods.join(' + ') : (methods[0] || 'N/D')

        tr += `
        <tr class="report-summary" data-booking="${group.bookingId ?? ''}">
            <td>${group.fecha}</td>
            <td>${group.usuario}</td>
            <td>$${totalReserva}</td>
            <td>${methodSummary}</td>
            <td>${group.cliente}</td>
            <td>${group.telefono}</td>
        </tr>
        <tr class="report-detail d-none" data-booking="${group.bookingId ?? ''}">
            <td colspan="6">
                <div class="report-detail-box">
                    <div><strong>Pagado:</strong> $${totalPagado}</div>
                    <div><strong>Saldo:</strong> $${saldo}</div>
                    <div class="report-detail-list">
                        ${group.pagos.map((pago) => `<div>${formatMetodo(pago.metodo)}: $${pago.monto}</div>`).join('')}
                    </div>
                </div>
            </td>
        </tr>
        `

        group.pagos.forEach((pago) => {
            if (pago.metodo === 'efectivo') {
                efectivo += Number(pago.monto)
            } else if (pago.metodo === 'transferencia') {
                transferencia += Number(pago.monto)
            } else if (pago.metodo === 'mercado_pago' || pago.metodo === 'Mercado Pago') {
                mercadoPago += Number(pago.monto)
            }
        })
    })

    resume = `
        <p>Total de reservas: <b>${reservasCount}</b></p>
        <p>Total del importe: <b>$${formatMoney(totalImporte)}</b></p>
        <hr>
        <p>Efectivo: <b>$${formatMoney(efectivo)}</b></p>
        <p>Mercado Pago: <b>$${formatMoney(mercadoPago)}</b></p>
        <p>Transferencia: <b>$${formatMoney(transferencia)}</b></p>
        <hr>
        <p>Total cobrado: <b>$${formatMoney(efectivo + mercadoPago + transferencia)}</b></p>
    `

    if (divTr) {
        divTr.innerHTML = tr
    }
    if (reportsSummary) {
        reportsSummary.innerHTML = resume
    }
    if (resumePayments) {
        resumePayments.innerHTML = resume
    }
    if (reportsCount) {
        reportsCount.textContent = `Reservas: ${reservasCount} | Importe total: $${formatMoney(totalImporte)}`
    }
}

document.addEventListener('click', (e) => {
    const row = e.target.closest('.report-summary')
    if (!row) {
        return
    }

    const bookingId = row.dataset.booking
    const detailRow = document.querySelector(`.report-detail[data-booking="${bookingId}"]`)
    if (detailRow) {
        detailRow.classList.toggle('d-none')
    }
})
