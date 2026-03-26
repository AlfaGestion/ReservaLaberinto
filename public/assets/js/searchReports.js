const fechaDesde = document.getElementById('buscarFechaDesde')
const fechaHasta = document.getElementById('buscarFechaHasta')
const selectUser = document.getElementById('selectUserReport')
// const generateReportButton = document.getElementById('generateReport')
const downloadReportButton = document.getElementById('downloadReport')
const downloadPaymentsReportButton = document.getElementById('downloadPaymentsReport')
const rateModal = new bootstrap.Modal('#rateModal')
// const generateReportModal = new bootstrap.Modal('#generateReportModal')
const switchPaymentsMp = document.getElementById('checkPaymetsMp')
const reservePaymentsButton = document.getElementById('reservePayments')
const searchReportsButton = document.getElementById('searchReports')
const selectDateRange = document.getElementById('selectDateRange')

document.addEventListener('DOMContentLoaded', (e) => {
    const fechaActual = new Date();
    const fechaAnterior = new Date(fechaActual)
    fechaAnterior.setDate(fechaActual.getDate() - 7)

    fechaDesde.value = fechaAnterior.toISOString().split('T')[0]
    fechaHasta.value = fechaActual.toISOString().split('T')[0]
})

selectDateRange.addEventListener('input', () => {
    const fechaActual = new Date();
    const primerDiaDelMesActual = new Date(fechaActual.getFullYear(), fechaActual.getMonth(), 1);

    if (selectDateRange.value === 'FD') {
        const fechaAnterior = new Date(fechaActual)
        fechaAnterior.setDate(fechaActual.getDate());

        fechaDesde.value = fechaAnterior.toISOString().split('T')[0];
        fechaHasta.value = fechaActual.toISOString().split('T')[0];
    } else if (selectDateRange.value === 'MA') {
        const primerDiaDelMes = new Date(fechaActual.getFullYear(), fechaActual.getMonth(), 1);
        const ultimoDiaDelMes = new Date(fechaActual.getFullYear(), fechaActual.getMonth() + 1, 0);

        fechaDesde.value = primerDiaDelMes.toISOString().split('T')[0];
        fechaHasta.value = ultimoDiaDelMes.toISOString().split('T')[0];
    } else if (selectDateRange.value === 'MP') {
        const fechaMesPasado = new Date(fechaActual);
        fechaMesPasado.setMonth(fechaMesPasado.getMonth() - 1);

        const primerDiaDelMesPasado = new Date(fechaMesPasado.getFullYear(), fechaMesPasado.getMonth(), 1);
        const ultimoDiaDelMesPasado = new Date(fechaMesPasado.getFullYear(), fechaMesPasado.getMonth() + 1, 0);

        fechaDesde.value = primerDiaDelMesPasado.toISOString().split('T')[0];
        fechaHasta.value = ultimoDiaDelMesPasado.toISOString().split('T')[0];
    } else if (selectDateRange.value === 'SA') {
        const fechaInicioSemanaActual = new Date(fechaActual);
        const diaSemanaActual = fechaActual.getDay(); 

        fechaInicioSemanaActual.setDate(fechaActual.getDate() - diaSemanaActual + 1);
        const fechaFinSemanaActual = new Date(fechaInicioSemanaActual);
        fechaFinSemanaActual.setDate(fechaInicioSemanaActual.getDate() + 6);

        fechaDesde.value = fechaInicioSemanaActual.toISOString().split('T')[0];
        fechaHasta.value = fechaFinSemanaActual.toISOString().split('T')[0];
    } else if (selectDateRange.value === 'SP') {
        const fechaInicioSemanaPasada = new Date(fechaActual);
        const diaSemanaActual = fechaActual.getDay();

        fechaInicioSemanaPasada.setDate(fechaActual.getDate() - diaSemanaActual - 6);
        const fechaFinSemanaPasada = new Date(fechaInicioSemanaPasada);
        fechaFinSemanaPasada.setDate(fechaInicioSemanaPasada.getDate() + 6);

        fechaDesde.value = fechaInicioSemanaPasada.toISOString().split('T')[0];
        fechaHasta.value = fechaFinSemanaPasada.toISOString().split('T')[0];
    }
});

switchPaymentsMp.addEventListener('change', (e) => {
    if (switchPaymentsMp.checked) {
        reservePaymentsButton.classList.remove('d-none')
        searchReportsButton.classList.add('d-none')
        // generateReportButton.classList.add('d-none')
        downloadReportButton.classList.add('d-none')
        downloadPaymentsReportButton.classList.add('d-none')
        selectUser.classList.add('d-none')

    } else {
        searchReportsButton.classList.remove('d-none')
        reservePaymentsButton.classList.add('d-none')
        // generateReportButton.classList.add('d-none')
        downloadReportButton.classList.add('d-none')
        downloadPaymentsReportButton.classList.add('d-none')
        selectUser.classList.remove('d-none')


    }
})


document.addEventListener('change', (e) => {
    if (e.target) {
        if (e.target.id == 'selectUserReport') {
            // generateReportButton.classList.add('d-none')
            downloadReportButton.classList.add('d-none')
            reservePaymentsButton.classList.add('d-none')
            searchReportsButton.classList.remove('d-none')
            switchPaymentsMp.checked = false
        }
    }
})


document.addEventListener('click', async (e) => {
    if (e.target) {
        if (e.target.id == 'searchReports') {

            let data = {
                fechaDesde: fechaDesde.value,
                fechaHasta: fechaHasta.value,
                user: selectUser.value,
            }
            const tableReports = document.getElementById('tableReports')
            // generateReportButton.classList.remove('d-none')
            downloadReportButton.classList.remove('d-none')
            tableReports.classList.remove('d-none')
            tableReservations.classList.add('d-none')

            getReports(data)
        } else if (e.target.id == 'nav-reports-tab') {
            const tableReports = document.getElementById('tableReports')
            const tableReservations = document.getElementById('tableReservations')

            tableReservations.classList.add('d-none')
            tableReports.classList.add('d-none')
            // generateReportButton.classList.add('d-none')
            downloadReportButton.classList.add('d-none')


        } else if (e.target.id == 'openRateModal') {
            rateModal.show()

        } else if (e.target.id == 'generateReport') {
            generateReportModal.show()

        } else if (e.target.id == 'reservePayments') {
            const tableReservations = document.getElementById('tableReservations')

            let data = {
                fechaDesde: fechaDesde.value,
                fechaHasta: fechaHasta.value,
            }

            tableReports.classList.add('d-none')
            tableReservations.classList.remove('d-none')
            // generateReportButton.classList.remove('d-none')
            downloadPaymentsReportButton.classList.remove('d-none')

            getMpPayments(data)
        }
        else if (e.target.id == 'downloadReport') {
            const buscarFechaDesde = fechaDesde.value
            const buscarFechaHasta = fechaHasta.value
            const idUser = selectUser.value == '' ? 'all' : selectUser.value

            const a = document.createElement("a")
            a.href = `${baseUrl}generateReportPdf/${idUser}/${buscarFechaDesde}/${buscarFechaHasta}`
            a.target = "_blank"
            a.click()

        } else if (e.target.id == 'downloadPaymentsReport') {
            const buscarFechaDesde = fechaDesde.value
            const buscarFechaHasta = fechaHasta.value
            const idUser = selectUser.value == '' ? 'all' : selectUser.value

            const a = document.createElement("a")
            a.href = `${baseUrl}generatePaymentsReportPdf/${buscarFechaDesde}/${buscarFechaHasta}`
            a.target = "_blank"
            a.click()
        }
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
        });

        const responseData = await response.json();

        fillReservations(responseData.data)

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}


async function fillReservations(data) {

    const divReservas = document.querySelector('.divReservas')
    const resumePayments = document.querySelector('.paymentsMethodsResume')

    let tr = ''
    let resume = ''
    let totalReservations = 0

    data.forEach(pago => {
        tr += `
        <tr >
            <td>${pago.fecha}</th>
            <td>$${pago.reserva}</td>
        </tr>
    `

        totalReservations += Number(pago.reserva)
    })

    resume = `
        <p>Total: <b>$${totalReservations}</b></p>
    `

    divReservas.innerHTML = tr
    resumePayments.innerHTML = resume
}


async function getReports(data) {
    try {
        const response = await fetch(`${baseUrl}getReports`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const responseData = await response.json();

        fillTable(responseData.data)

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function getReportsForPdf(data) {
    try {
        const response = await fetch(`${baseUrl}getReports`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const responseData = await response.json();

        return responseData.data

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function fillTable(data) {
    const divTr = document.querySelector('.divTr')
    const resumePayments = document.querySelector('.paymentsMethodsResume')

    let tr = ''
    let resume = ''
    let efectivo = 0
    let transferencia = 0
    let mercadoPago = 0


    data.forEach(pago => {

        tr += `
        <tr >
            <td>${pago.fecha}</th>
            <td>${pago.usuario}</td>
            <td>$${pago.pago}</td>
            <td>${pago.metodoPago}</td>
            <td>${pago.cliente}</td>
            <td>${pago.telefonoCliente}</td>
        </tr>
    `

        if (pago.metodoPago == "efectivo") {
            efectivo += Number(pago.pago)
        } else if (pago.metodoPago == "transferencia") {
            transferencia += Number(pago.pago)
        } else if (pago.metodoPago == "mercado_pago") {
            mercadoPago += Number(pago.pago)
        }
    })

    resume = `
        <p>Efectivo: <b> $${efectivo} </b></p>
        <p>Mercado Pago: <b> $${mercadoPago} </b></p>
        <p>Transferencia: <b> $${transferencia} </b></p>
        <hr>
        <p>Total: <b>$${efectivo + mercadoPago + transferencia}</b></p>
    `

    divTr.innerHTML = tr
    resumePayments.innerHTML = resume
}
