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
const codigoArea = document.getElementById('codigoArea')
const pagoReserva = document.getElementById('inputPagoReserva')
const pagoTotal = document.getElementById('switchPagoTotal')
const divTime = document.getElementById('div-time')
const divTimeH = document.getElementById('div-time-h')
const modalConfirmarReserva = new bootstrap.Modal('#modalConfirmarReserva')
const modalSpinner = new bootstrap.Modal('#modalSpinner')
const modalIngresarPago = new bootstrap.Modal('#ingresarPago')
const modalResult = new bootstrap.Modal('#modalResult')
const contentBookingResult = document.getElementById('bookingResult')
const divSelectCancha = document.getElementById('divSelectCancha')
const powerOff = document.getElementsByName('powerOff')
const inputqtyvisitors = document.getElementById('inputqtyvisitors')
const selectServicio = document.getElementById('selectServicio')
const botonesContainer = document.getElementById('botones-container')
const showBooking = document.getElementById('showBooking')

const btnMpParcial = document.getElementById('btn-parcial')
const btnMpTotal = document.getElementById('btn-total')

const modalAvailability = new bootstrap.Modal('#modalAvailability')
const availabilityResult = document.getElementById('availabilityResult')
const showAvailability = document.getElementById('showAvailability')

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
// const publicKeyMp = document.getElementById('publicKeyMp').value
const divMessages = document.getElementById('divMessages')
const closeModalValidate = document.getElementById('closeModalValidate')

let data = {}
let preferencesIds = {}
let useOffer = false
let serviceValue = 0
// let idCustomer
let availables = []
let currentCustomer
let minVisitantes = 0
let openingTime = []

let dataOferta = {
    valor: 0,
    descripcion: '',
    fecha: 0,
}

// Fecha actual por defecto
document.addEventListener('DOMContentLoaded', (e) => {
    // Muestra el modal de bienvenida si el usuario no está logueado.
    if (!sessionUserLogued) {
        const welcomeModalEl = document.getElementById('welcomeModal');
        if (welcomeModalEl) {
            const welcomeModal = new bootstrap.Modal(welcomeModalEl);
            welcomeModal.show();
        }
    }

    // Obtiene la disponibilidad inicial de horarios.
    getAvailability();
    getTime();

    // Eliminar los últimos 3 horarios de "Desde" para ajustar los bloques de 2 horas.
    // Nota: Esto asume que tu lógica de backend genera 3 horarios extra.
    for (let i = 0; i < 3; i++) {
        if (horarioDesde.options.length > 1) { // Evitar errores si hay pocas opciones
            horarioDesde.remove(horarioDesde.options.length - 1);
        }
    }

    // Aseguramos que todas las opciones estén habilitadas por defecto.
    for (let i = 0; i < horarioDesde.options.length; i++) {
        horarioDesde.options[i].disabled = false;
    }

    // Aplicar lógica de bloques de 120 minutos (habilitar cada 4ta opción).
    // Se salta el índice 0 que es "Seleccionar".
    for (let i = 1; i < horarioDesde.options.length; i++) {
        if ((i - 1) % 4 !== 0) {
            horarioDesde.options[i].disabled = true;
        }
    }

    const fechaSistema = new Date();

    // 1. Calcular la fecha de mañana.
    const fechaMananaObj = new Date(fechaSistema);
    fechaMananaObj.setDate(fechaSistema.getDate() + 1);

    // 2. Formatear la fecha de mañana al formato 'YYYY-MM-DD' requerido por el input.
    const añoManana = fechaMananaObj.getFullYear();
    const mesManana = String(fechaMananaObj.getMonth() + 1).padStart(2, '0');
    const diaManana = String(fechaMananaObj.getDate()).padStart(2, '0');
    const fechaManana = `${añoManana}-${mesManana}-${diaManana}`;

    // 3. Establecer la fecha mínima y el valor por defecto en el input de fecha.
    // (Asumiendo que la variable de tu input de fecha es 'fechaInput').
    if (fechaInput) {
        fechaInput.setAttribute('min', fechaManana); // La fecha más temprana seleccionable es mañana.
        fechaInput.value = fechaManana;            // El valor inicial del campo será mañana.
    }

    // --- LÓGICA ADICIONAL ---

    inputqtyvisitors.disabled = true;

    if (esDomingo === '1') {
        checkSunday();
    }

    // Llamadas a funciones finales para inicializar el estado de la página.
    getRate();
    deleteRejected();
});

if (check1, check2, check3, check4, check5, check6, check7, check8, check9, check10) {
    const allChecks = [check1, check2, check3, check4, check5, check6, check7, check8, check9, check10];

    allChecks.forEach(check => {
        check.addEventListener('change', () => {
            const allChecked = allChecks.every(chk => chk.checked);
            confirmarReservabutton.disabled = !allChecked;
            confirmRulesButton.disabled = !allChecked;
        });
    });
}

horarioDesde.addEventListener('change', async () => {
    divTime.classList.remove('d-none');
    divTimeH.style.width = '49%';
    selectCancha.classList.remove('d-none');
    divqtyvisitors.classList.remove('d-none');
    inputqtyvisitors.disabled = false

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

    // Seleccionar automáticamente la opción de 90 minutos
    if (indexDesde + 4 < horarioHasta.options.length) {
        horarioHasta.value = horarioHasta.options[indexDesde + 4].value;
    }

    // Mover esta línea para que se ejecute al final
    await getTimeFromBookings();
});

document.addEventListener('change', async (e) => {
    if (e.target) {
        if (e.target.id == 'fecha') {
            const day = new Date(fechaInput.value);
            const dayOfWeek = day.getDay();

            const dayMap = [
                'is_monday',    // 0
                'is_tuesday',   // 1
                'is_wednesday', // 2
                'is_thursday',  // 3
                'is_friday',    // 4
                'is_saturday',  // 5
                'is_sunday',    // 6
            ];

            // Obtener la clave de cierre para el día seleccionado
            const dayKey = dayMap[dayOfWeek];

            // Obtener el valor de cierre (debe ser '1' o 1 para estar cerrado)
            const isClosed = openingTime.closed[dayKey];

            // 1. Calcular la fecha de mañana (día siguiente)
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowFormatted = tomorrow.toISOString().split('T')[0];

            // ⬇️ LÓGICA DE VERIFICACIÓN DE DÍA CERRADO
            // Verificamos si el valor de isClosed es '1' o el número 1.
            if (isClosed == 1) {
                // Si está cerrado, restablecemos la fecha a mañana.
                fechaInput.value = tomorrowFormatted;

                // También puedes restablecer los selectores aquí si prefieres un solo punto de salida:
                selectCancha.selectedIndex = 0;
                horarioDesde.selectedIndex = 0;
                horarioHasta.selectedIndex = 0;

                return alert('Ese día el laberinto permanecerá cerrado. Seleccione otra fecha.');
            }

            // Restablecer otros selectores SOLO si el día NO estaba cerrado
            selectCancha.selectedIndex = 0;
            horarioDesde.selectedIndex = 0;
            horarioHasta.selectedIndex = 0;
        } else if (e.target.id == 'horarioDesde') {
            divTime.classList.remove('d-none');
            divTimeH.style.width = '49%';
            selectCancha.classList.remove('d-none');
            divqtyvisitors.classList.remove('d-none');
            inputqtyvisitors.disabled = false

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

            // Seleccionar automáticamente la opción de 90 minutos
            if (indexDesde + 4 < horarioHasta.options.length) {
                horarioHasta.value = horarioHasta.options[indexDesde + 4].value;
            }

            // Mover esta línea para que se ejecute al final
            await getTimeFromBookings();
        } else if (e.target.id == 'cancha') {

            // if (!sessionUserLogued) {
            //     divMonto.classList.remove('d-none')
            // }

            // getAmount(selectCancha.value)

        } else if (e.target.id == 'horarioHasta') {
            // inputMonto.value = 0

            // getAmount(selectCancha.value)

        } else if (e.target.id == 'switchPagoTotal') {
            const btnMpParcial = document.getElementById('btn-parcial')
            const btnMpTotal = document.getElementById('btn-total')
            // const btnMpParcial = document.getElementById('checkout-btn-parcial')
            // const btnMpTotal = document.getElementById('checkout-btn-total')

            if (pagoTotal.checked) {
                btnMpParcial.classList.add('d-none')
                btnMpTotal.classList.remove('d-none')
            } else {
                btnMpParcial.classList.remove('d-none')
                btnMpTotal.classList.add('d-none')
            }
        } else if (e.target.id == 'check1') {
            if (check1.checked) {
                showBooking.classList.add('d-none');
                check1Div.classList.remove('d-block');
                check1Div.classList.add('d-none');
                check2Div.classList.remove('d-none');
            }
        } else if (e.target.id == 'check2') {
            if (check2.checked) {
                check2Div.classList.add('d-none');
                check3Div.classList.remove('d-none');
            }
        } else if (e.target.id == 'check3') {
            if (check3.checked) {
                check3Div.classList.add('d-none');
                check4Div.classList.remove('d-none');
            }
        } else if (e.target.id == 'check4') {
            if (check4.checked) {
                check4Div.classList.add('d-none');
                check5Div.classList.remove('d-none');
            }
        } else if (e.target.id == 'check5') {
            if (check5.checked) {
                check5Div.classList.add('d-none');
                check6Div.classList.remove('d-none');
            }
        } else if (e.target.id == 'check6') {
            if (check6.checked) {
                check6Div.classList.add('d-none');
                check7Div.classList.remove('d-none');
            }
        } else if (e.target.id == 'check7') {
            if (check7.checked) {
                check7Div.classList.add('d-none');
                check8Div.classList.remove('d-none');
            }
        } else if (e.target.id == 'check8') {
            if (check8.checked) {
                check8Div.classList.add('d-none');
                check9Div.classList.remove('d-none');
            }
        } else if (e.target.id == 'check9') {
            if (check9.checked) {
                check9Div.classList.add('d-none');
                check10Div.classList.remove('d-none');
            }
        } else if (e.target.id == 'check10') {
            if (check10.checked) {
                confirmRulesButton.classList.remove('d-none');
                confirmRulesButton.disabled = false
            }
        }
    }
})


document.addEventListener('click', async (e) => {
    if (e.target) {
        const rate = await getRate()

        if (sessionUserLogued) {
            data = {
                fecha: fecha.value,
                cancha: selectCancha.value,
                horarioDesde: horarioDesde.value,
                horarioHasta: horarioHasta.value,
                nombre: nombre.value,
                telefono: telefono.value,
                codigoArea: codigoArea.value,
                visitantes: inputqtyvisitors.value,
            }
        } else {
            data = {
                fecha: fecha.value,
                cancha: cancha.value,
                horarioDesde: horarioDesde.value,
                horarioHasta: horarioHasta.value,
                nombre: nombre.value,
                telefono: telefono.value,
                codigoArea: codigoArea.value,
                monto: pagoReserva.value,
                total: parseFloat(inputMonto.value),
                parcial: parseFloat(inputMonto.value) * rate.value / 100,
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
            if (inputqtyvisitors.value == '' || inputqtyvisitors.value == '0') {
                alert('Debe ingresar la cantidad de visitantes')
                return;
            }

            if (inputqtyvisitors.value < minVisitantes) {
                alert(`La cantidad mínima de visitantes es ${minVisitantes}`)
                return;
            }

            if (fecha.value == '' || cancha.value == '' || horarioDesde.value == '' || horarioHasta.value == '' || nombre.value == '' || telefono.value == '' || codigoArea.value == '') {
                alert('Debe completar todos los datos')
                return;
            } else {
                await setScriptMP(parseFloat(inputMonto.value))

            }

            if (horarioDesde.value == '23' && horarioHasta.value == '00' || horarioDesde.value == '23' && horarioHasta.value == '01' || horarioDesde.value == '22' && horarioHasta.value == '00' || horarioDesde.value == '22' && horarioHasta.value == '01') {
            } else if (parseInt(horarioDesde.value) >= parseInt(horarioHasta.value)) {
                alert('El horario de comienzo no puede ser mayor al de fin')
                return;
            }

            fetchFormInfo(data)

        } else if (e.target.id == 'buttonCancel' || e.target.id == 'btnClose' || e.target.id == 'cancelarReserva') {
            location.reload(true)
        } else if (e.target.id == 'switchPagoTotal') {
            const switchPagoTotal = document.getElementById('switchPagoTotal')
            const nocturnalTime = await getNocturnalTime()
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
            alert('Al realizar el pago de una reserva (ya sea parcial o total), se asume el compromiso y la responsabilidad de asistir en el día y horario acordados. En caso de inasistencia, no se realizarán devoluciones de dinero, y la reprogramación quedará sujeta a disponibilidad.')

            const rate = await getRate()

            if (pagoReserva) {
                pagoReserva.value = parseFloat(inputMonto.value) * rate.value / 100
            }

            modalIngresarPago.show()
            const amount = document.getElementById('adminBookingAmount')
            const description = document.getElementById('adminBookingDescription')
            const totalReserva = document.getElementById('adminBookingTotalAmount')

            amount.value = inputMonto.value * rate.value / 100
            totalReserva.value = inputMonto.value
            pagoReserva.value = parseFloat(inputMonto.value) * rate.value / 100

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
            if (inputqtyvisitors.value == '' || inputqtyvisitors.value == '0') {
                alert('Debe ingresar la cantidad de visitantes')
                return;
            }

            if (inputqtyvisitors.value < minVisitantes) {
                alert(`La cantidad mínima de visitantes es ${minVisitantes}`)
                return;
            }

            if (nombre.value == '' || telefono.value == '' || codigoArea.value == '') {
                alert('Debe completar todos los datos')
                return
            }

            fetchFormInfo(data)

            modalConfirmarReserva.show()
        } else if (e.target.id == 'validateDataButton') {
            const inputCodArea = document.getElementById('inputCodigoArea')
            const inputTelefono = document.getElementById('inputTelefono')
            // modalSpinner.show()
            const customer = await validateCustomer(inputTelefono.value, inputEmail.value)

            if (customer) {
                currentCustomer = customer
                // modalSpinner.hide()
                localStorage.setItem('customer', JSON.stringify(customer))
                // console.log(customer)
                getValue(customer.type_institution)
                codigoArea.value = customer.area_code
                telefono.value = customer.phone
                nombre.value = customer.name
                validateDataButton.classList.add('d-none')

                closeModalValidate.classList.remove('d-none')
                divMessages.innerHTML = `
                <div class="alert alert-success" role="alert">
                    <small>El cliente <strong>${customer.name}</strong> ya se encuentra registrado</small>
                </div>
                `
            } else {
                // modalSpinner.hide()
                closeModalValidate.classList.add('d-none')
                divMessages.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <small>El cliente no se encuentra registrado. Por favor, <a href="${baseUrl}customers/register">regístrese</a></small>
                </div>
                `
            }
        } else if (e.target.id == 'showAvailability') {
            modalAvailability.show();

            // Limpiamos el contenido del modal antes de agregar el nuevo
            availabilityResult.innerHTML = '';

            // Asumimos que 'availables' es el objeto completo con la propiedad 'availability'
            const availabilityData = availables.availability;

            if (availabilityData && availabilityData.length > 0) {
                // Recorremos cada día disponible
                availabilityData.forEach(day => {
                    // console.log(day);

                    // La lógica de validación se simplifica.
                    // Verificamos si el array tiene un solo elemento y si ese elemento
                    // es la cadena de "cerrado" generada por el backend.
                    const isClosed = day.available_slots.length === 1 && day.available_slots[0].startsWith('Cerrado los ');

                    // Creamos una sección para cada fecha
                    let dayContent = `
                <h5 class="text-xl font-semibold mt-4 text-gray-800">${day.date}</h5>
                <hr class="my-2 border-gray-300">
                <div class="grid grid-cols-2 gap-4">
            `;

                    if (isClosed) {
                        // Si el día está cerrado, mostramos un solo mensaje
                        dayContent += `
                    <div class="col-span-2 text-center text-gray-600 p-2 rounded-lg bg-gray-100">
                        ${day.available_slots[0]}
                    </div>
                `;
                    } else {
                        // Recorremos cada uno de los lapsos disponibles para ese día
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

                    dayContent += `</div>`; // Cierre del contenedor de la cuadrícula

                    // Agregamos el contenido completo del día al modal
                    availabilityResult.innerHTML += dayContent;
                });
            } else {
                availabilityResult.innerHTML = '<p class="text-center text-gray-500">No hay servicios disponibles en los horarios seleccionados.</p>';
            }
        } else if (e.target.id === 'selectSlotButton') {
            selectSlotButton = e.target;
            const selectedDate = selectSlotButton.getAttribute('data-date');
            const selectedSlotStart = selectSlotButton.getAttribute('data-slot-start');
            const selectedSlotEnd = selectSlotButton.getAttribute('data-slot-end');

            fechaInput.value = new Date(selectedDate.split('/').reverse().join('-')).toISOString().split('T')[0];
            horarioDesde.value = selectedSlotStart;
            horarioHasta.value = selectedSlotEnd;
            // console.log(horarioDesde);
            modalAvailability.hide();

            const event = new Event('change');
            horarioDesde.dispatchEvent(event);
        }

    }
})

async function getValue(type) {
    const response = await fetch(`${baseUrl}getValue/${type}`);
    const responseData = await response.json();

    if (responseData.data) {
        const amount = parseFloat(responseData.data.amount);
        serviceValue = amount;
        // if (sessionUserLogued) {
        //     inputMonto.value = (inputqtyvisitors.value * amount) - ((inputqtyvisitors.value * amount) * currentCustomer.offer / 100)
        // }

        inputMonto.value = (inputqtyvisitors.value * amount) - ((inputqtyvisitors.value * amount) * parseFloat(currentCustomer.offer) / 100)

        // inputMonto.value = amount.toLocaleString('es-AR', {
        //     minimumFractionDigits: 2,
        //     maximumFractionDigits: 2
        // });
    }
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
    // const totalAmount = serviceValue * inputqtyvisitors.value;
    inputMonto.value = (inputqtyvisitors.value * serviceValue) - ((inputqtyvisitors.value * serviceValue) * parseFloat(currentCustomer.offer) / 100)
    // inputMonto.value = totalAmount.toLocaleString('es-AR', {
    //     minimumFractionDigits: 2,
    //     maximumFractionDigits: 2
    // });
});

telefono.addEventListener('input', async () => {
    let content
    const phone = String(codigoArea.value + telefono.value)

    if (phone.length == 10) {
        modalSpinner.show()
        const customer = await getCustomer(telefono.value)
        // console.log(customer)
        if (customer) {
            currentCustomer = customer
            divMonto.classList.remove('d-none')
            nombre.value = customer.name
            getValue(customer.type_institution)
            inputqtyvisitors.disabled = false

            if (customer.offer > 0) {
                inputMonto.value = inputMonto.value / customer.offer * 100
            }
        } else {

            const nocturnalTime = await getNocturnalTime()
            const selectedField = await getField(selectCancha.value)


            if (nocturnalTime.time.includes(horarioDesde.value) && nocturnalTime.time.includes(horarioHasta.value)) {
                inputMonto.value = `${calculateAmount(horarioDesde.value, horarioHasta.value, selectedField.ilumination_value)}`
            } else {
                inputMonto.value = `${calculateAmount(horarioDesde.value, horarioHasta.value, selectedField.value)}`
            }
        }

        setTimeout(() => { modalSpinner.hide() }, 300);
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
        alert('Debe seleccionar un medio de pago')
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

async function setScriptMP(amount) {
    let preference = {}
    // console.log(publicKeyMp.value)
    modalSpinner.show()
    btnMpParcial.classList.remove('d-none')
    preference = {
        amount: amount,
    }

    const preferences = await setPreference(`${baseUrl}setPreference`, preference)
    const mp = new MercadoPago(publicKeyMp, {
        locale: "es-AR"
    })

    // Función para abrir el checkout
    function abrirCheckout(preferenceId) {
        mp.checkout({
            preference: {
                id: preferenceId
            },
            autoOpen: true, // Abre automáticamente el checkout (modal o redirección)
        });
    }

    // Asignar eventos a los botones
    document.getElementById('btn-parcial').addEventListener('click', () => {
        abrirCheckout(preferences.preferenceIdParcial);
    });

    document.getElementById('btn-total').addEventListener('click', () => {
        abrirCheckout(preferences.preferenceIdTotal);
    });

    // mp.checkout({
    //     preference: {
    //         id: preferences.preferenceIdParcial
    //     },
    //     render: {
    //         container: '#checkout-btn-parcial',
    //         label: 'Pagar con Mercado Pago',
    //     }
    // })

    // mp.checkout({
    //     preference: {
    //         id: preferences.preferenceIdTotal
    //     },
    //     render: {
    //         container: '#checkout-btn-total',
    //         label: 'Pagar con Mercado Pago',
    //     }
    // })


    data.preferenceIdParcial = preferences.preferenceIdParcial,
        data.preferenceIdTotal = preferences.preferenceIdTotal,


        saveBooking(data)

    modalSpinner.hide()
    modalConfirmarReserva.show()
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

        const responseData = await response.json();

        return responseData.data

    } catch (error) {
        console.error('Error:', error);
        throw error;
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
            return responseData.data
        } else {
            alert('Algo salió mal. No se pudo obtener la información.');
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
            alert('Algo salió mal. No se pudo obtener la información.');
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


async function getFields() {
    try {
        const response = await fetch(`${baseUrl}getFields`);

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

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

// Trae la información a mostrar en el modal
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
            alert('Algo salió mal. No se pudo obtener la información.');
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
            alert('Algo salió mal. No se pudo obtener la información.');
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function validateCustomer(phone, email) {
    try {
        const response = await fetch(`${baseUrl}validateCustomer/${phone}/${email}`);

        const responseData = await response.json();

        if (responseData.data != '') {
            currentCustomer = responseData.data
            return responseData.data

        } else {
            alert('Algo salió mal. No se pudo obtener la información.');
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

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
            <li>📅 <b>Fecha:</b> ${fecha}</li>
            <li>🛎️ <b>Servicio:</b> ${data?.data?.cancha}</li>
            <li>👤 <b>Visitantes:</b> ${data?.data?.visitantes}</li>
            <li>⏰ <b>Horario:</b> ${data?.data?.horarioDesde} a ${data?.data?.horarioHasta}</li>
            <li>🙋 <b>Nombre:</b> ${data?.data?.nombre}</li>
            <li>📞 <b>Teléfono:</b> ${data?.data?.codigoArea + data?.data?.telefono}</li>
        </ul>
        `;
    } else {
        info =
            `
        <ul id="bookingDetail">
            <li>📅 <b>Fecha:</b> ${fecha}</li>
            <li>🛎️ <b>Servicio:</b> ${data?.data?.cancha}</li>
            <li>👤 <b>Visitantes:</b> ${data?.data?.visitantes}</li>
            <li>⏰ <b>Horario:</b> ${data?.data?.horarioDesde} a ${data?.data?.horarioHasta}</li>
            <li>💰 <b>Monto:</b> $${amount}</li>
            <li>🙋 <b>Nombre:</b> ${data?.data?.nombre}</li>
            <li>📞 <b>Teléfono:</b> ${data?.data?.codigoArea + data?.data?.telefono}</li>
        </ul>
        `;
    }



    modalBody.innerHTML = info;
}

function convertDateFormat(date) {
    return date.split("-").reverse().join("/")
}


// Trae los horarios de las reservas hechas
async function getTimeFromBookings() {
    const fecha = document.getElementById('fecha').value

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

        // Creamos un nuevo array que excluye el último horario de la reserva.
        // Esto permite que el horario final de una reserva se convierta en el de inicio para la siguiente.
        const horariosOcupadosSinFinal = horariosOcupados.slice(0, -1);

        // Ahora verificamos si algún horario seleccionado se cruza con los horarios ocupados.
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

    // ✅ Dividimos por 90 minutos (1.5 horas)
    const bloquesDeHoraYMedia = durationInMinutes / 90;

    const result = bloquesDeHoraYMedia * parseFloat(amount);

    return result;
}
