const code = document.getElementById('code');
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

document.addEventListener('click', async (e) => {
    if (e.target && e.target.id === 'searchBooking') {
        bookingCardContainer.innerHTML = '';
        bookingCardContainer.classList.add('d-none');

        searchBooking(code.value);
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

    // Seleccionar automáticamente la opción de 90 minutos
    if (indexDesde + 4 < horarioHasta.options.length) {
        horarioHasta.value = horarioHasta.options[indexDesde + 4].value;
    }

    // Mover esta línea para que se ejecute al final
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
                <h4 class="mb-5">Reserva modificada!</h4>
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

async function initializeEditBookingModal() {
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
//             alert('Algo salió mal. No se pudo obtener la información.');
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
            alert('Algo salió mal. No se pudo obtener la información.');
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

    try {
        const response = await fetch(`${baseUrl}customers/showCustomerBooking/${codeValue}`);

        if (!response.ok) {
            throw new Error(`Error en la solicitud: ${response.status}`);
        }

        const responseData = await response.json();

        if (responseData.data && responseData.data !== '') {
            currentBooking = responseData.data
            renderBookingCard(responseData.data); // Usamos la nueva función
        } else {
            bookingCardContainer.classList.add('d-none');
            alert('No se encontró ninguna reserva con ese código o algo salió mal.');
        }

    } catch (error) {
        console.error('Error:', error);
        alert('Ocurrió un error al intentar obtener la reserva.');
    } finally {
        // modalSpinner.hide();
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
                        <p class="mb-2"><strong>📅 Fecha:</strong> ${booking.date}</p>
                        <p class="mb-2"><strong>⏰ Horario:</strong> ${booking.time_from} - ${booking.time_until}</p>
                        <p class="mb-2"><strong>🛎️ Servicio:</strong> Laberinto</p>
                        <p class="mb-0"><strong>👤 Visitantes:</strong> <span class="badge bg-info text-dark">${booking.visitors}</span></p>
                    </div>

                    <div class="col-md-6">
                        <p class="mb-2"><strong>🙋 Nombre:</strong> ${booking.name}</p>
                        <p class="mb-2"><strong>📞 Teléfono:</strong> ${booking.phone}</p>
                        <p class="mb-0"><strong>📝 Descripción:</strong> ${booking.description || 'Sin descripción'}</p>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-light d-flex flex-wrap justify-content-between align-items-center p-3">
                
                <div class="mb-2 mb-md-0">
                    <p class="mb-1"><strong>💰 Total:</strong> <span class="text-dark fw-bold">$${booking.total}</span></p>
                    <p class="mb-1"><strong>✅ Pagado:</strong> $${booking.payment} (${booking.payment_method})</p>
                    <p class="mb-0 text-danger fw-bold"><strong>💸 Saldo:</strong> $${booking.diference}</p>
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