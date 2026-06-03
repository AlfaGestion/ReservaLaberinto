const code = document.getElementById('code');
const phoneSearch = document.getElementById('phoneSearch');
const emailSearch = document.getElementById('emailSearch');
const modalSpinner = new bootstrap.Modal(document.getElementById('modalSpinner'));
const bookingCardContainer = document.getElementById('bookingCardContainer');
const editBookingModal = new bootstrap.Modal('#editarReservaModal')
const horarioDesde = document.getElementById('horarioDesde');
const horarioHasta = document.getElementById('horarioHasta');
const fechaInput = document.getElementById('fecha');
const servicioInput = document.getElementById('cancha');
const inputqtyvisitors = document.getElementById('inputqtyvisitors');
const inputnombre = document.getElementById('nombre');
const inputtelefono = document.getElementById('telefono');
const inputMonto = document.getElementById('inputMonto');
const contentEditBookingResult = document.getElementById('bookingEditResult')
const modalResult = new bootstrap.Modal('#modalResult')

const showAvailability = document.getElementById('showAvailability')
const modalAvailability = new bootstrap.Modal('#modalAvailability')
const availabilityResult = document.getElementById('availabilityResult')

let minVisitantes = 0
let openingTime = []
let currentBooking = {}
let availables = { availability: [] }

function showBookingMessage(message, type = 'secondary') {
    bookingCardContainer.classList.remove('d-none');
    bookingCardContainer.innerHTML = `<div class="alert alert-${type} mb-0">${message}</div>`;
}

function formatBookingMoney(value) {
    return `$${new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(Number(value) || 0)}`
}

function getBookingUnitPrice(booking) {
    const unitPrice = Number(booking?.current_unit_price ?? booking?.unit_price ?? 0)
    if (Number.isFinite(unitPrice) && unitPrice > 0) {
        return unitPrice
    }

    const visitors = Number(booking?.visitors ?? 0)
    const total = Number(booking?.total ?? 0)
    return visitors > 0 && total > 0 ? total / visitors : 0
}

async function parseJsonResponse(response) {
    try {
        return await response.json();
    } catch (error) {
        return { message: 'No pudimos interpretar la respuesta del servidor.' };
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const prefillElement = document.getElementById('bookingPrefill');
    const urlParams = new URLSearchParams(window.location.search);
    const codeParam = prefillElement?.dataset?.code || urlParams.get('code') || '';
    const phoneParam = prefillElement?.dataset?.phone || urlParams.get('phone') || '';
    const emailParam = prefillElement?.dataset?.email || urlParams.get('email') || '';

    if (phoneSearch && emailSearch) {
        phoneSearch.value = phoneParam;
        emailSearch.value = emailParam;
    }

    if (codeParam) {
        code.value = codeParam;

        if (phoneParam && emailParam) {
            searchBookingWithHistory(codeParam, phoneParam, emailParam);
        } else {
            searchBooking(codeParam);
        }
        return;
    }

    if (phoneParam && emailParam) {
        searchCustomerBookings(phoneParam, emailParam);
    }
});

document.addEventListener('click', async (e) => {
    if (e.target && e.target.id === 'searchBooking') {
        bookingCardContainer.innerHTML = '';
        bookingCardContainer.classList.add('d-none');

        searchBooking(code.value);
    } else if (e.target && e.target.id === 'searchCustomerBookings') {
        bookingCardContainer.innerHTML = '';
        bookingCardContainer.classList.add('d-none');

        searchCustomerBookings(phoneSearch.value, emailSearch.value);
    } else if (e.target && e.target.id === 'editarReservaModal') {
        editBookingModal.show()

        initializeEditBookingModal();
    } else if (e.target.id == 'showAvailability') {
        modalAvailability.show();

        // Limpiamos el contenido del modal antes de agregar el nuevo
        availabilityResult.innerHTML = '';

        // Asumimos que 'availables' es el objeto completo con la propiedad 'availability'
        const availabilityData = availables.availability;

        if (availabilityData && availabilityData.length > 0) {
            // Recorremos cada dÃ­a disponible
            availabilityData.forEach(day => {
                // console.log(day);

                // La lÃ³gica de validaciÃ³n se simplifica.
                // Verificamos si el array tiene un solo elemento y si ese elemento
                // es la cadena de "cerrado" generada por el backend.
                const isClosed = day.available_slots.length === 1 && day.available_slots[0].startsWith('Cerrado los ');

                // Creamos una secciÃ³n para cada fecha
                let dayContent = `
                <h5 class="text-xl font-semibold mt-4 text-gray-800">${day.date}</h5>
                <hr class="my-2 border-gray-300">
                <div class="grid grid-cols-2 gap-4">
            `;

                if (isClosed) {
                    // Si el dÃ­a estÃ¡ cerrado, mostramos un solo mensaje
                    dayContent += `
                    <div class="col-span-2 text-center text-gray-600 p-2 rounded-lg bg-gray-100">
                        ${day.available_slots[0]}
                    </div>
                `;
                } else {
                    // Recorremos cada uno de los lapsos disponibles para ese dÃ­a
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

                dayContent += `</div>`; // Cierre del contenedor de la cuadrÃ­cula

                // Agregamos el contenido completo del dÃ­a al modal
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
    } else if (e.target.id === 'actualizarReserva') {
        updateBooking()
    }
});

horarioDesde.addEventListener('change', async () => {
    servicioInput.classList.remove('d-none');

    const indexDesde = horarioDesde.selectedIndex;

    // Resetear y limpiar opciones de horarioHasta
    horarioHasta.value = '';

    for (let i = 0; i < horarioHasta.options.length; i++) {
        horarioHasta.options[i].disabled = true;
    }

    // Habilitar opciones de horarioHasta
    for (let offset = 4; indexDesde + offset < horarioHasta.options.length; offset += 4) {
        horarioHasta.options[indexDesde + offset].disabled = false;
    }

    // Seleccionar automÃ¡ticamente la opciÃ³n de 90 minutos
    if (indexDesde + 4 < horarioHasta.options.length) {
        horarioHasta.value = horarioHasta.options[indexDesde + 4].value;
    }

    // Mover esta lÃ­nea para que se ejecute al final
    await getTimeFromBookings();
});

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

async function getFieldForTimeBookings(data) {
    const timeBookings = data.reservas;
    const todasLasCanchas = data.canchas;

    const currentDesde = horarioDesde.value;
    const currentHasta = horarioHasta.value;

    const indexDesde = time.indexOf(currentDesde);
    const indexHasta = time.indexOf(currentHasta);

    const rangoSeleccionado = time.slice(indexDesde, indexHasta);

    // Limpiar y preparar el select
    servicioInput.innerHTML = '';
    const defaultOption = new Option('Servicios disponibles', '');
    servicioInput.appendChild(defaultOption);

    todasLasCanchas.forEach(cancha => {
        const reservasDeCancha = timeBookings.find(e => e.id_cancha == cancha.id);
        const horariosOcupados = reservasDeCancha ? reservasDeCancha.time : [];

        // Creamos un nuevo array que excluye el Ãºltimo horario de la reserva.
        // Esto permite que el horario final de una reserva se convierta en el de inicio para la siguiente.
        const horariosOcupadosSinFinal = horariosOcupados.slice(0, -1);

        // Ahora verificamos si algÃºn horario seleccionado se cruza con los horarios ocupados.
        const hayCruce = rangoSeleccionado.some(hora => horariosOcupadosSinFinal.includes(hora));

        if (!hayCruce) {
            const nuevaOpcion = new Option(cancha.name, cancha.id);
            if (cancha.id == 1) {
                nuevaOpcion.selected = true;
            }
            servicioInput.appendChild(nuevaOpcion);
        }
    });

    if (servicioInput.options.length === 1) {

        servicioInput.options[0].text = 'No hay servicios disponibles en este horario';
        servicioInput.style.backgroundColor = '#e74959ff';
    } else {
        servicioInput.style.backgroundColor = '';
    }
}

async function updateBooking() {
    editBookingModal.hide()
    modalSpinner.show()

    const updateData = {
        bookingId: currentBooking.id,
        fecha: fechaInput.value,
        horarioDesde: horarioDesde.value,
        horarioHasta: horarioHasta.value,
        cancha: currentBooking.id_field,
        diferencia: currentBooking.diference,
        pagoTotal: currentBooking.total_payment,
        parcial: currentBooking.parcial,
        total: currentBooking.total,
        visitantes: currentBooking.visitors
    }


    try {
        const response = await fetch(`${baseUrl}editBooking`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(updateData)
        });

        if (response.ok) {

            contentEditBookingResult.innerHTML = `
            <div class="modal-body modalResultPayment d-flex justify-content-center align-items-center flex-column" style="background-color: #157347; color: #fff">
                <h4 class="mb-5">Reserva actualizada con éxito</h4>
                <i class="fa-regular fa-circle-check fa-2xl" style="margin-bottom: 20px;"></i>
            </div>`


            modalResult.show()

            setTimeout(() => { location.reload(true) }, 1000)


        } else {
            alert('Algo saliÃ³ mal. No se pudo editar la reserva.');
            return
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function initializeEditBookingModal() {
    // Obtiene la disponibilidad inicial de horarios.
    getAvailability();
    getTime();

    // Eliminar los Ãºltimos 3 horarios de "Desde" para ajustar los bloques de 2 horas.
    // Nota: Esto asume que tu lÃ³gica de backend genera 3 horarios extra.
    for (let i = 0; i < 3; i++) {
        if (horarioDesde.options.length > 1) { // Evitar errores si hay pocas opciones
            horarioDesde.remove(horarioDesde.options.length - 1);
        }
    }

    // Aseguramos que todas las opciones estÃ©n habilitadas por defecto.
    for (let i = 0; i < horarioDesde.options.length; i++) {
        horarioDesde.options[i].disabled = false;
    }

    // Aplicar lÃ³gica de bloques de 120 minutos (habilitar cada 4ta opciÃ³n).
    // Se salta el Ã­ndice 0 que es "Seleccionar".
    for (let i = 1; i < horarioDesde.options.length; i++) {
        if ((i - 1) % 4 !== 0) {
            horarioDesde.options[i].disabled = true;
        }
    }

    const fechaSistema = new Date();

    // 1. Calcular la fecha de maÃ±ana.
    const fechaMananaObj = new Date(fechaSistema);
    fechaMananaObj.setDate(fechaSistema.getDate() + 1);

    // 2. Formatear la fecha de maÃ±ana al formato 'YYYY-MM-DD' requerido por el input.
    const aÃ±oManana = fechaMananaObj.getFullYear();
    const mesManana = String(fechaMananaObj.getMonth() + 1).padStart(2, '0');
    const diaManana = String(fechaMananaObj.getDate()).padStart(2, '0');
    const fechaManana = `${aÃ±oManana}-${mesManana}-${diaManana}`;

    // 3. Establecer la fecha mÃ­nima y el valor por defecto en el input de fecha.
    // (Asumiendo que la variable de tu input de fecha es 'fechaInput').
    if (fechaInput) {
        fechaInput.setAttribute('min', fechaManana); // La fecha mÃ¡s temprana seleccionable es maÃ±ana.
        fechaInput.value = fechaManana;            // El valor inicial del campo serÃ¡ maÃ±ana.
    }

    // --- LÃ“GICA ADICIONAL ---

    inputqtyvisitors.disabled = true;

    if (esDomingo === '1') {
        checkSunday();
    }

    // Llamadas a funciones finales para inicializar el estado de la pÃ¡gina.
    // getRate();

    inputqtyvisitors.value = currentBooking.visitors
    horarioDesde.value = currentBooking.time_from
    horarioHasta.value = currentBooking.time_until
    servicioInput.value = currentBooking.id_field
    inputtelefono.value = currentBooking.phone
    inputnombre.value = currentBooking.name
    inputMonto.value = currentBooking.total
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

// async function getRate() {
//     try {
//         const response = await fetch(`${baseUrl}getRate`);
//         const responseData = await response.json();


//         if (responseData.data != '') {
//             minVisitantes = responseData.data.qty_visitors
//             return responseData.data
//         } else {
//             alert('Algo saliÃ³ mal. No se pudo obtener la informaciÃ³n.');
//         }
//     } catch (error) {
//         console.error('Error:', error);
//         throw error;
//     }
// }

async function getTime() {
    try {
        const response = await fetch(`${baseUrl}getTime`);
        const responseData = await response.json();


        if (responseData.data != '') {
            openingTime = responseData.data
            return responseData.data
        } else {
            alert('Algo saliÃ³ mal. No se pudo obtener la informaciÃ³n.');
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


async function searchBooking(codeValue) {
    // modalSpinner.show();

    if (!codeValue) {
        showBookingMessage('Debe ingresar el codigo de reserva.', 'warning');
        return;
    }

    try {
        const response = await fetch(`${baseUrl}customers/showCustomerBooking/${codeValue}`);
        const responseData = await parseJsonResponse(response);

        if (!response.ok) {
            showBookingMessage(responseData.message || 'No se encontro ninguna reserva con ese codigo.', 'warning');
            return;
        }

        if (responseData.data && responseData.data !== '') {
            currentBooking = responseData.data
            renderBookingCard(responseData.data); // Usamos la nueva funciÃ³n
        } else {
            showBookingMessage(responseData.message || 'No se encontro ninguna reserva con ese codigo.', 'warning');
        }

    } catch (error) {
        console.error('Error:', error);
        showBookingMessage('No pudimos consultar tu reserva. Intentá nuevamente.', 'danger');
    } finally {
        // modalSpinner.hide();
    }
}

async function searchCustomerBookings(phoneValue, emailValue) {
    if (!phoneValue || !emailValue) {
        showBookingMessage('Debe ingresar telefono y email para ver todas las reservas.', 'warning');
        return;
    }

    try {
        const response = await fetch(`${baseUrl}customers/showCustomerBookings`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                phone: phoneValue,
                email: emailValue
            })
        });
        const responseData = await parseJsonResponse(response);

        if (!response.ok) {
            showBookingMessage(responseData.message || 'No encontramos reservas para ese cliente.', 'warning');
            return;
        }

        if (responseData.data && responseData.data.length > 0) {
            renderBookingsList(responseData.data);
        } else {
            showBookingMessage(responseData.message || 'No encontramos reservas para ese cliente.', 'warning');
        }
    } catch (error) {
        console.error('Error:', error);
        showBookingMessage('No se pudieron consultar las reservas. Intente nuevamente.', 'danger');
    }
}

async function searchBookingWithHistory(codeValue, phoneValue, emailValue) {
    try {
        const [bookingResponse, historyResponse] = await Promise.all([
            fetch(`${baseUrl}customers/showCustomerBooking/${codeValue}`),
            fetch(`${baseUrl}customers/showCustomerBookings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    phone: phoneValue,
                    email: emailValue
                })
            })
        ]);

        const bookingData = await parseJsonResponse(bookingResponse);
        const historyData = historyResponse.ok ? await parseJsonResponse(historyResponse) : { data: [] };

        if (!bookingResponse.ok) {
            showBookingMessage(bookingData.message || 'No se encontro la reserva solicitada.', 'warning');
            return;
        }

        if (bookingData.data && bookingData.data !== '') {
            currentBooking = bookingData.data;
            renderBookingWithHistory(bookingData.data, historyData.data || []);
        } else {
            showBookingMessage(bookingData.message || 'No se encontro la reserva solicitada.', 'warning');
        }
    } catch (error) {
        console.error('Error:', error);
        showBookingMessage('No pudimos consultar tu reserva y el historial. Intentá nuevamente.', 'danger');
    }
}


function renderBookingCard(booking) {

    bookingCardContainer.classList.remove('d-none');
    let changeBookingButton = '';

    const today = new Date();
    const bookingDate = new Date(booking.date + 'T00:00:00');
    const diffTime = bookingDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    const showChangeBookingButton = (diffDays > 3);

    if (showChangeBookingButton) {
        changeBookingButton = `<button type="button" class="btn btn-primary" id="editarReservaModal" data-id="${booking.id}">Modificar reserva</button>`;
    }

    const cardHTML = `
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-success text-white p-3">
                <h5 class="mb-0">
                    <i class="bi bi-ticket-perforated-fill me-2"></i> Reserva #${booking.code}
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    
                    <div class="col-md-6 border-end">
                        <p class="mb-2"><strong>ðŸ“… Fecha:</strong> ${booking.date}</p>
                        <p class="mb-2"><strong>â° Horario:</strong> ${booking.time_from} - ${booking.time_until}</p>
                        <p class="mb-2"><strong>ðŸ›Žï¸ Servicio:</strong> Laberinto</p>
                        <p class="mb-0"><strong>ðŸ‘¤ Visitantes:</strong> <span class="badge bg-info text-dark">${booking.visitors}</span></p>
                    </div>

                    <div class="col-md-6">
                        <p class="mb-2"><strong>ðŸ™‹ Nombre:</strong> ${booking.name}</p>
                        <p class="mb-2"><strong>ðŸ“ž TelÃ©fono:</strong> ${booking.phone}</p>
                        <p class="mb-0"><strong>ðŸ“ DescripciÃ³n:</strong> ${booking.description || 'Sin descripciÃ³n'}</p>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-light d-flex flex-wrap justify-content-between align-items-center p-3">
                
                <div class="mb-2 mb-md-0">
                    <p class="mb-1"><strong>Precio por entrada individual:</strong> ${formatBookingMoney(getBookingUnitPrice(booking))}</p>
                    <p class="mb-1"><strong>ðŸ’° Total:</strong> <span class="text-dark fw-bold">$${booking.total}</span></p>
                    <p class="mb-1"><strong>âœ… Pagado:</strong> $${booking.payment} (${booking.payment_method})</p>
                    <p class="mb-0 text-danger fw-bold"><strong>ðŸ’¸ Saldo:</strong> $${booking.diference}</p>
                </div>
                
                <div class="d-flex flex-row justify-content-center align-items-end gap-2">
                    ${changeBookingButton}
                    <a class="mt-4 btn btn-danger" target="_blank" href="${baseUrl}bookingPdf/${booking.id}">Descargar PDF</a>
                </div>
            </div>
        </div>
    `;

    bookingCardContainer.innerHTML = cardHTML;
}

function renderBookingWithHistory(selectedBooking, bookings) {
    bookingCardContainer.classList.remove('d-none');

    const bookingDate = formatBookingDate(selectedBooking.date);
    const serviceName = selectedBooking.service_name || 'Reserva';
    const description = selectedBooking.description || 'Sin descripcion';

    const otherBookings = (bookings || []).filter((booking) => booking.id !== selectedBooking.id);
    const historyHtml = buildHistorySections(otherBookings);

    bookingCardContainer.innerHTML = `
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-success text-white p-3">
                <h5 class="mb-0">Reserva #${selectedBooking.code}</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6 border-end">
                        <p class="mb-2"><strong>Fecha:</strong> ${bookingDate}</p>
                        <p class="mb-2"><strong>Horario:</strong> ${selectedBooking.time_from} - ${selectedBooking.time_until}</p>
                        <p class="mb-2"><strong>Servicio:</strong> ${serviceName}</p>
                        <p class="mb-0"><strong>Visitantes:</strong> <span class="badge bg-info text-dark">${selectedBooking.visitors}</span></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Nombre:</strong> ${selectedBooking.name}</p>
                        <p class="mb-2"><strong>Telefono:</strong> ${selectedBooking.phone}</p>
                        <p class="mb-0"><strong>Descripcion:</strong> ${description}</p>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light d-flex flex-wrap justify-content-between align-items-center p-3">
                <div class="mb-2 mb-md-0">
                    <p class="mb-1"><strong>Precio por entrada individual:</strong> ${formatBookingMoney(getBookingUnitPrice(selectedBooking))}</p>
                    <p class="mb-1"><strong>Total:</strong> <span class="text-dark fw-bold">$${selectedBooking.total}</span></p>
                    <p class="mb-1"><strong>Pagado:</strong> $${selectedBooking.payment} (${selectedBooking.payment_method})</p>
                    <p class="mb-0 text-danger fw-bold"><strong>Saldo:</strong> $${selectedBooking.diference}</p>
                </div>
                <div class="d-flex flex-row justify-content-center align-items-end gap-2">
                    <a class="btn btn-danger" target="_blank" href="${baseUrl}bookingPdf/${selectedBooking.id}">Descargar PDF</a>
                </div>
            </div>
        </div>
        ${historyHtml}
    `;
}

function renderBookingsList(bookings) {
    bookingCardContainer.classList.remove('d-none');
    bookingCardContainer.innerHTML = buildHistorySections(bookings);
}

function formatBookingDate(dateValue) {
    if (!dateValue) {
        return '';
    }

    if (dateValue.includes('-')) {
        const [year, month, day] = dateValue.split('-');
        if (year && month && day) {
            return `${day}/${month}/${year}`;
        }
    }

    return dateValue;
}

function getBookingStartTimestamp(booking) {
    const dateValue = booking?.date || '';
    const timeValue = booking?.time_from || '00:00';

    if (!dateValue) {
        return Number.MAX_SAFE_INTEGER;
    }

    return new Date(`${dateValue}T${timeValue}:00`).getTime();
}

function getBookingEndTimestamp(booking) {
    const dateValue = booking?.date || '';
    const timeValue = booking?.time_until || booking?.time_from || '00:00';

    if (!dateValue) {
        return 0;
    }

    return new Date(`${dateValue}T${timeValue}:00`).getTime();
}

function isUpcomingBooking(booking) {
    return getBookingEndTimestamp(booking) >= Date.now();
}

function buildBookingAccordionItems(bookings, prefix) {
    return bookings.map((booking, index) => {
        const bookingDate = formatBookingDate(booking.date);
        const description = booking.description || 'Sin descripcion';
        const serviceName = booking.service_name || 'Reserva';
        const collapseId = `${prefix}-booking-${booking.id}-${index}`;
        const headingId = `${collapseId}-heading`;
        const paymentBadge = booking.total_payment == 1
            ? '<span class="badge bg-success">Pago completo</span>'
            : '<span class="badge bg-warning text-dark">Pago parcial</span>';

        return `
            <div class="accordion-item mb-2 border rounded-3 overflow-hidden">
                <h2 class="accordion-header" id="${headingId}">
                    <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="false" aria-controls="${collapseId}">
                        Reserva #${booking.code} - ${bookingDate} - ${booking.time_from} a ${booking.time_until}
                    </button>
                </h2>
                <div id="${collapseId}" class="accordion-collapse collapse" aria-labelledby="${headingId}">
                    <div class="accordion-body">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                            <div>
                                <p class="mb-1"><strong>Servicio:</strong> ${serviceName}</p>
                                <p class="mb-1"><strong>Visitantes:</strong> ${booking.visitors}</p>
                                <p class="mb-0"><strong>Descripcion:</strong> ${description}</p>
                            </div>
                            <div>${paymentBadge}</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Nombre:</strong> ${booking.name}</p>
                                <p class="mb-1"><strong>Telefono:</strong> ${booking.phone}</p>
                                <p class="mb-0"><strong>Metodo de pago:</strong> ${booking.payment_method}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Precio por entrada individual:</strong> ${formatBookingMoney(getBookingUnitPrice(booking))}</p>
                                <p class="mb-1"><strong>Total:</strong> $${booking.total}</p>
                                <p class="mb-1"><strong>Pagado:</strong> $${booking.payment}</p>
                                <p class="mb-0"><strong>Saldo:</strong> $${booking.diference}</p>
                            </div>
                        </div>
                        <div class="mt-3 d-flex flex-wrap gap-2">
                            <a class="btn btn-danger btn-sm" target="_blank" href="${baseUrl}bookingPdf/${booking.id}">Descargar PDF</a>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function buildHistorySections(bookings) {
    const sortedBookings = [...bookings].sort((a, b) => getBookingStartTimestamp(a) - getBookingStartTimestamp(b));
    const upcomingBookings = sortedBookings.filter(isUpcomingBooking);
    const pastBookings = sortedBookings.filter((booking) => !isUpcomingBooking(booking));

    let html = '';

    if (upcomingBookings.length > 0) {
        html += `
            <div class="mb-3">
                <h5 class="mb-3">Proximas reservas</h5>
                <div class="accordion" id="upcomingBookingsAccordion">
                    ${buildBookingAccordionItems(upcomingBookings, 'upcoming')}
                </div>
            </div>
        `;
    }

    if (pastBookings.length > 0) {
        html += `
            <div class="mt-4">
                <details class="card shadow-sm border-0">
                    <summary class="card-header bg-light fw-semibold" style="cursor: pointer;">Ver historial de reservas (${pastBookings.length})</summary>
                    <div class="card-body">
                        <div class="accordion" id="pastBookingsAccordion">
                            ${buildBookingAccordionItems(pastBookings, 'past')}
                        </div>
                    </div>
                </details>
            </div>
        `;
    }

    if (html === '') {
        return `<div class="alert alert-secondary mb-0">No hay reservas para este cliente.</div>`;
    }

    return html;
}


