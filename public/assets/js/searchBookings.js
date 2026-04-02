const searchBookingButton = document.getElementById('searchBooking')
const inputDesdeBooking = document.getElementById('fechaDesdeBooking')
const inputHastaBooking = document.getElementById('fechaHastaBooking')
const completarPagoModalButton = new bootstrap.Modal('#completarPagoModal')
const contentPaymentResults = document.getElementById('paymentResult')
const spinnerCompletarPagos = new bootstrap.Modal('#spinnerCompletarPago')
const cambiarEstadoMPModal = new bootstrap.Modal('#modalCambiarEstado')
const totalReservasHoy = document.getElementById('totalReservasHoy')
const botonCompletarPago = document.getElementById('botonCompletarPago')
const selectDateBooking = document.getElementById('selectDateBooking')

let bookingData = {}
let bookingId = ''

function formatLocalDate(date) {
    const year = date.getFullYear()
    const month = `${date.getMonth() + 1}`.padStart(2, '0')
    const day = `${date.getDate()}`.padStart(2, '0')
    return `${year}-${month}-${day}`
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

    getActiveBookings(bookingData)
})


document.addEventListener('click', async (e) => {
    if (e.target) {
        if (e.target.id == 'searchBooking') {
            bookingData = {
                fechaDesde: inputDesdeBooking.value,
                fechaHasta: inputHastaBooking.value
            }

            getActiveBookings(bookingData)
        } else if (e.target.id == 'searchAnnulledBooking') {
            bookingData = {
                fechaDesde: inputDesdeBooking.value,
                fechaHasta: inputHastaBooking.value
            }

            getAnnulledBookings(bookingData)
        } else if (e.target.id == 'modalCompletarPago') {

            const bookingId = e.target.dataset.id
            const botonPagar = document.getElementById('botonCompletarPago')
            const booking = await getBooking(bookingId)
            botonPagar.setAttribute('data-id', bookingId)

            completarPagoModalButton.show()
            inputCompletarPagoReserva.value = booking.diference
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

        }
    }
})

async function completePayment(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            if (response.ok) {

                setTimeout(() => { spinnerCompletarPagos.show() }, 500)

                completarPagoModalButton.hide()

                contentPaymentResults.innerHTML = `
                <div class="modal-body modalResultPayment d-flex justify-content-center align-items-center flex-column" style="background-color: #157347;">
                    <h4 class="mb-5">Pago confirmado!</h4>
                    <i class="fa-regular fa-circle-check fa-2xl" style="margin-bottom: 20px;"></i>
                </div>`

                setTimeout(() => { modalResultPayment.show() }, 2000)
                setTimeout(() => { spinnerCompletarPagos.hide() }, 2000)
                setTimeout(() => { location.reload(true) }, 3000)

            } else {
                setTimeout(() => { spinnerCompletarPagos.show() }, 500)
                completarPagoModalButton.hide()

                contentPaymentResults.innerHTML = `
                <div class="modal-body modalResultPayment d-flex justify-content-center align-items-center flex-column" style="background-color: #bb2d3b;">
                    <h4 class="mb-5">No se pudo guardar el pago. Vuelva a intentar</h4>
                    <i class="fa-regular fa-circle-xmark fa-2xl" style="margin-bottom: 20px;"></i>
                </div>`

                setTimeout(() => { modalResultPayment.show() }, 2000)
                setTimeout(() => { spinnerCompletarPagos.hide() }, 2000)
                setTimeout(() => { location.reload(true) }, 3000)
            }

        } else {
            alert('Algo salió mal. No se pudo ingresar el pago.');
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function getBooking(id) {
    try {
        const response = await fetch(`${baseUrl}getBooking/${id}`);

        const responseData = await response.json();

        if (responseData.data != '') {

            return responseData.data

        } else {
            alert('Algo salió mal. No se pudo obtener la información.');
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

async function getActiveBookings(data) {
    try {
        const response = await fetch(`${baseUrl}getActiveBookings`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const responseData = await response.json();

        totalReservasHoy.innerHTML = '&nbsp;' + (responseData?.data?.length || 0);


        fillTableBookings(responseData.data)

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

async function fillTableBookings(data) {
    const divBookings = document.querySelector('.divBookings')

    let existPending = false
    let stateMP = ''
    let tr = ''
    let actions = ''
    let edit = ''
    let anular = ''
    let state = ''

    data.forEach(reserva => {

        if (reserva.mp == 0) {
            if (existPending == false) {
                existPending = true
                alert('Tiene pagos pendientes ingresantes de Mercado Pago')
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

        tr += `
        <tr >
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
            <td>${actions}</td>
        </tr>
    `
    });

    divBookings.innerHTML = tr
}
