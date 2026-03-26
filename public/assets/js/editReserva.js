const fecha = document.getElementById('fecha')
const horarioDesde = document.getElementById('horarioDesde')
const horarioHasta = document.getElementById('horarioHasta')
const cancha = document.getElementById('cancha')
const inputMonto = document.getElementById('inputMonto')
const telefono = document.getElementById('telefono')
const nombre = document.getElementById('nombre')
const modalSpinner = new bootstrap.Modal('#modalSpinner')
const modalResult = new bootstrap.Modal('#modalResult')
const contentEditBookingResult = document.getElementById('bookingEditResult')
const inputqtyvisitors = document.getElementById('inputqtyvisitors')
const actualizarReserva = document.getElementById('actualizarReserva')

let booking_id
let updateData
let value = 0


document.addEventListener('DOMContentLoaded', (e) => {
    const fechaActual = new Date().toISOString().split('T')[0]
    fecha.setAttribute('min', fechaActual)
    fecha.value = fechaActual;

    // Eliminar los últimos 3 horarios de "Desde"
    for (let i = 0; i < 3; i++) {
        horarioDesde.remove(horarioDesde.options.length - 1);
    }

    // Aseguramos que todos estén habilitados
    for (let i = 0; i < horarioDesde.options.length; i++) {
        horarioDesde.options[i].disabled = false;
    }

    // Aplicar lógica de bloques de 120 minutos (cada 4)
    // Saltamos índice 0 ("Seleccionar")
    for (let i = 1; i < horarioDesde.options.length; i++) {
        if ((i - 1) % 4 !== 0) {
            horarioDesde.options[i].disabled = true;
        }
    }

})

document.addEventListener('click', async (e) => {
    if (e.target) {
        const rate = await getRate()

        updateData = {
            fecha: fecha.value,
            cancha: cancha.value,
            horarioDesde: horarioDesde.value,
            horarioHasta: horarioHasta.value,
            total: inputMonto.value,
            parcial: inputMonto.value * rate / 100,
            visitantes: inputqtyvisitors.value,
        }

        if (e.target.id == 'editarReservaModal') {
            booking_id = e.target.dataset.id

            const currentBooking = await getBooking(booking_id)
            const currentCustomer = await getCustomer(currentBooking.phone)
            const currentValue = await getValue(currentCustomer.type_institution)
            value = currentValue

            fecha.value = currentBooking.date
            horarioDesde.value = currentBooking.time_from
            horarioHasta.value = currentBooking.time_until
            cancha.value = currentBooking.id_field
            telefono.value = currentBooking.phone
            nombre.value = currentBooking.name
            inputMonto.value = currentBooking.total
            inputqtyvisitors.value = currentBooking.visitors

        } else if (e.target.id == 'actualizarReserva') {
            const currentBooking = await getBooking(booking_id)

            updateData.bookingId = booking_id
            updateData.diferencia = inputMonto.value - currentBooking.payment
            updateData.pagoTotal = (inputMonto.value - currentBooking.payment) == 0 ? 1 : 0


            if (fecha.value == '' || cancha.value == '' || horarioDesde.value == '' || nombre.value == '' || telefono.value == '') {
                alert('Debe completar todos los datos')
                return;
            }

            // if (horarioDesde.value == '23' && horarioHasta.value == '00' || horarioDesde.value == '23' && horarioHasta.value == '01' || horarioDesde.value == '22' && horarioHasta.value == '00' || horarioDesde.value == '22' && horarioHasta.value == '01') {
            // } else if (parseInt(horarioDesde.value) >= parseInt(horarioHasta.value)) {
            //     alert('El horario de comienzo no puede ser mayor al de fin')
            //     return;
            // }

            updateBooking(updateData)
        } else if (e.target.id == 'cancelarReserva') {
            editBookingModal.hide()
        }
    }
})

document.addEventListener('change', async (e) => {
    if (e.target) {
        if (e.target.id == 'horarioDesde') {

            const indexDesde = horarioDesde.selectedIndex;
            horarioHasta.value = horarioHasta[indexDesde + 4].value

            // Resetear y limpiar opciones de horarioHasta
            horarioHasta.value = '';

            for (let i = 0; i < horarioHasta.options.length; i++) {
                // Deshabilitar todas las opciones primero
                horarioHasta.options[i].disabled = true;
            }

            // Habilitar opciones que estén a múltiplos de 4 bloques (120 min, 240, etc.)
            for (let offset = 4; indexDesde + offset < horarioHasta.options.length; offset += 4) {
                horarioHasta.options[indexDesde + offset].disabled = false;
            }

            // Seleccionar automáticamente la opción de 90 minutos (1er múltiplo)
            if (indexDesde + 4 < horarioHasta.options.length) {
                horarioHasta.value = horarioHasta.options[indexDesde + 4].value;
            }

            await getTimeFromBookings()
        } else if (e.target.id == 'fecha') {
            // horarioDesde.selectedIndex = 0
            // horarioHasta.selectedIndex = 0
            // cancha.selectedIndex = 0
            // inputMonto.value = 0

        } else if (e.target.id == 'cancha') {
            await getAmount(cancha.value)
        }
    }
})

inputqtyvisitors.addEventListener('input', (e) => {
    // if (inputqtyvisitors.value == '' || inputqtyvisitors.value == '0') {
    //     alert('Debe ingresar la cantidad de visitantes')
    //     return;
    // }

    // if (inputqtyvisitors.value < 10) {
    //     alert('La cantidad mínima de visitantes es 10')
    //     return;
    // }

    if (inputqtyvisitors.value == '' || inputqtyvisitors.value == '0') {
        inputMonto.value = 0
    } else {
        inputMonto.value = (parseInt(inputqtyvisitors.value)) * value
    }

})

async function updateBooking(data) {
    editBookingModal.hide()

    modalSpinner.show()

    try {
        const response = await fetch(`${baseUrl}editBooking`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {

            contentEditBookingResult.innerHTML = `
            <div class="modal-body modalResultPayment d-flex justify-content-center align-items-center flex-column" style="background-color: #157347; color: #fff">
                <h4 class="mb-5">Reserva editada!</h4>
                <i class="fa-regular fa-circle-check fa-2xl" style="margin-bottom: 20px;"></i>
            </div>`


            modalResult.show()

            setTimeout(() => { location.reload(true) }, 1000)


        } else {
            alert('Algo salió mal. No se pudo editar la reserva.');
            return
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function getValue(type) {
    const response = await fetch(`${baseUrl}getValue/${type}`);
    const responseData = await response.json();

    if (responseData.data) {
        // console.log(responseData)
        return responseData.data.amount
        // const amount = parseFloat(responseData.data.amount);
        // serviceValue = amount;

        // inputMonto.value = amount.toLocaleString('es-AR', {
        //     minimumFractionDigits: 2,
        //     maximumFractionDigits: 2
        // });
    }
}

async function getCustomer(phone) {
    try {
        const response = await fetch(`${baseUrl}getCustomer/${phone}`);
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


// Trae los horarios de las reservas hechas
async function getTimeFromBookings() {
    const fecha = document.getElementById('fecha').value

    try {
        const response = await fetch(`${baseUrl}getBookings/${fecha}`);
        const responseData = await response.json();

        if (responseData.data != '') {
            getFieldForTimeBookings(responseData.data)
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

function generateTimeSlotsWithEnd() {
    const timeSlots = [];
    for (let h = 0; h < 24; h++) {
        for (let m = 0; m < 60; m += 30) {
            const hour = String(h).padStart(2, '0');
            const minute = String(m).padStart(2, '0');
            timeSlots.push(`${hour}:${minute}`);
        }
    }
    // Agrego 24:00 como tope para poder “cerrar” el rango sin trucos
    timeSlots.push('24:00');
    return timeSlots;
}

// Normaliza una hora cualquiera a la grilla de 30'.
// start = redondeo hacia abajo; end = hacia arriba.
function normalizeToSlot(hhmm, { mode = 'start' } = {}) {
    if (!hhmm) return null;
    let [h, m] = hhmm.split(':').map(Number);
    if (Number.isNaN(h) || Number.isNaN(m)) return null;
    let total = h * 60 + m;
    if (mode === 'start') {
        total = Math.max(0, Math.min(24 * 60, Math.floor(total / 30) * 30));
    } else {
        total = Math.max(0, Math.min(24 * 60, Math.ceil(total / 30) * 30));
    }
    const H = String(Math.floor(total / 60)).padStart(2, '0');
    const M = String(total % 60).padStart(2, '0');
    return `${H}:${M}`;
}

async function getFieldForTimeBookings(data) {
    // Renombro para evitar sombras
    const selectCancha = cancha;              // <- tu <select> ya existente
    const inputDesde = horarioDesde;        // <- tus inputs existentes
    const inputHasta = horarioHasta;

    const SLOTS = generateTimeSlotsWithEnd();

    const timeBookings = data.reservas || [];
    const todasLasCanchas = data.canchas || [];

    // Normalizo a la grilla
    const currentDesde = normalizeToSlot(inputDesde.value, { mode: 'start' });
    const currentHasta = normalizeToSlot(inputHasta.value, { mode: 'end' });

    const indexDesde = SLOTS.indexOf(currentDesde);
    const indexHasta = SLOTS.indexOf(currentHasta);

    // Si algo no está en la grilla o el rango es inválido, muestro mensaje y salgo
    if (indexDesde === -1 || indexHasta === -1 || indexHasta <= indexDesde) {
        selectCancha.innerHTML = '';
        const opt = new Option('Seleccioná un rango válido (múltiplos de 30 min)', '');
        selectCancha.appendChild(opt);
        selectCancha.style.backgroundColor = '#bb2d3b';
        return;
    }

    // Rango seleccionado (sin incluir el final)
    const rangoSeleccionado = SLOTS.slice(indexDesde, indexHasta);

    // Limpiar y preparar el select
    selectCancha.innerHTML = '';
    const defaultOption = new Option('Servicios disponibles', '');
    selectCancha.appendChild(defaultOption);

    // Recorro las canchas (evito sombrear nombres)
    todasLasCanchas.forEach(c => {
        const reservasDeCancha = timeBookings.find(e => e.id_cancha == c.id);
        const horariosOcupados = Array.isArray(reservasDeCancha?.time) ? reservasDeCancha.time : [];

        // Excluyo el último slot de cada reserva (el final es “libre” para iniciar la siguiente)
        const ocupadosSinFinal = horariosOcupados.slice(0, -1);
        const ocupadosSet = new Set(ocupadosSinFinal);

        const hayCruce = rangoSeleccionado.some(hora => ocupadosSet.has(hora));

        if (!hayCruce) {
            const nuevaOpcion = new Option(c.name, c.id);
            if (c.id == 1) nuevaOpcion.selected = true;
            selectCancha.appendChild(nuevaOpcion);
        }
    });

    if (selectCancha.options.length === 1) {
        actualizarReserva.disabled = true;
        selectCancha.options[0].text = 'No hay servicios disponibles en este horario';
        selectCancha.style.backgroundColor = '#bb2d3b';
    } else {
        actualizarReserva.disabled = false;
        selectCancha.style.backgroundColor = '';
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
            alert('Algo salió mal. No se pudo obtener la información.');
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

        if (responseData.data != '') {

            const nocturnalTime = { time: responseData.data }

            return nocturnalTime
        } else {
            alert('Algo salió mal. No se pudo obtener la información.');
        }
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function getAmount(field = "1") {
    try {
        const nocturnalTime = await getNocturnalTime()
        const selectedField = await getField(field)

        if (nocturnalTime.time.includes(horarioDesde.value) && nocturnalTime.time.includes(horarioHasta.value)) {
            inputMonto.value = `${calculateAmount(horarioDesde.value, horarioHasta.value, selectedField.ilumination_value)}`
        } else {
            inputMonto.value = `${calculateAmount(horarioDesde.value, horarioHasta.value, selectedField.value)}`
        }
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

// Calcula el total $ de la reserva
function calculateAmount(from, until, amount) {
    let hours = 0
    let result = ''

    if (Number(from) == 23 && Number(until) == 0) {
        hours = 1
    } else if (Number(from) == 23 && Number(until == 1)) {
        hours = 2
    }

    for (i = Number(from); i < Number(until); i++) {

        hours = hours + 1
    }

    result = parseInt(hours) * parseInt(amount)

    return result
}

async function getRate() {
    try {
        const response = await fetch(`${baseUrl}getRate`);
        const responseData = await response.json();


        if (responseData.data != '') {

            return responseData.data.value
        } else {
            alert('Algo salió mal. No se pudo obtener la información.');
        }
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}