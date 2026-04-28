// const formBooking = document.getElementById('formBooking')
// const selectMenuAbm = document.getElementById('selectMenuAbm')
// const openingTime = document.getElementById('openingTime')
// const switchCutTime = document.getElementById('switchCutTime')
// const horarioNocturno = document.getElementById('horarioNocturno')
const inputCompletarPagoReserva = document.getElementById('inputCompletarPagoReserva')
const inputRate = document.getElementById('rate')
const inputQtyVisitors = document.getElementById('visitors')
const inputAllowGroupCoordinator = document.getElementById('allowGroupCoordinator')
const inputNotificationEmail = document.getElementById('notificationEmail')
const inputInvoiceEmailSubject = document.getElementById('invoiceEmailSubject')
const inputInvoiceEmailMessage = document.getElementById('invoiceEmailMessage')
const inputEnablePayByEntries = document.getElementById('enablePayByEntries')
const inputPayByEntriesMinEntries = document.getElementById('payByEntriesMinEntries')
const inputPayByEntriesMinDaysBeforeBooking = document.getElementById('payByEntriesMinDaysBeforeBooking')
const inputPayByEntriesDefaultPercentage = document.getElementById('payByEntriesDefaultPercentage')
const inputOfferRate = document.getElementById('offerRate')
const descriptionOffer = document.getElementById('descriptionOffer')
const medioPagoSelect = document.getElementById('medioPagoSelect')
// const changeTimeFrom = document.getElementById('changeTimeFrom')
// const changeTimeUntil = document.getElementById('changeTimeUntil')
// const changeTimeFromCut = document.getElementById('changeTimeFromCut')
// const changeTimeUntilCut = document.getElementById('changeTimeUntilCut')
const completarPagoModalB = new bootstrap.Modal('#completarPagoModal')
// const cambiarEstadoMPModal = new bootstrap.Modal('#modalCambiarEstado')
const cancelBookingModal = new bootstrap.Modal('#anularReservaModal')
const editBookingModal = new bootstrap.Modal('#editarReservaModal')
const completarPagoModal = document.getElementById('completarPagoModal')
const spinnerCompletarPago = new bootstrap.Modal('#spinnerCompletarPago')
const modalResultPayment = new bootstrap.Modal('#modalResultPayment')
const contentPaymentResult = document.getElementById('paymentResult')
const enterFieldsForm = document.getElementById('enterFields')
const selectEditField = document.getElementById('selectEditField')
const editFieldDiv = document.getElementById('editFieldDiv')
const selectEditFields = document.getElementById('selectEditFields')
const adminTabs = new bootstrap.Tab(document.getElementById('nav-tab'))
const fieldForm = document.getElementById('fieldForm')
const fieldName = document.getElementById('fieldName')
const disableFieldInput = document.getElementById('disableField')
const fieldModalElement = document.getElementById('fieldModal')
const fieldModal = fieldModalElement ? new bootstrap.Modal(fieldModalElement) : null
const fieldsTableBody = document.getElementById('fieldsTableBody')

const serviceName = document.getElementById('serviceName')
const serviceValue = document.getElementById('serviceValue')
const serviceAmount = document.getElementById('serviceAmount')
const serviceDiscountPercentage = document.getElementById('serviceDiscountPercentage')
const serviceFinalAmount = document.getElementById('serviceFinalAmount')
const idValue = document.getElementById('idValue')
const valueModalElement = document.getElementById('valueModal')
const valueModal = valueModalElement ? new bootstrap.Modal(valueModalElement) : null
const valueForm = document.getElementById('valueForm')
const valuesTableBody = document.getElementById('valuesTableBody')

let idBooking
let currentFieldId = null

function formatAdminMoney(value) {
    return new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value)
}

function showAdminNotice(message, type = 'success', title = '') {
    const container = document.getElementById('adminNoticeContainer')

    if (!container) {
        return
    }

    const notice = document.createElement('div')
    const iconMap = {
        success: 'fa-circle-check',
        error: 'fa-circle-xmark',
        info: 'fa-circle-info'
    }

    notice.className = `admin-notice admin-notice--${type}`
    notice.innerHTML = `
        <div class="admin-notice__icon">
            <i class="fa-solid ${iconMap[type] || iconMap.info}"></i>
        </div>
        <div class="admin-notice__content">
            <span class="admin-notice__title">${title || (type === 'error' ? 'No se pudo completar' : 'Listo')}</span>
            <span class="admin-notice__message">${message}</span>
        </div>
    `

    container.appendChild(notice)

    setTimeout(() => {
        notice.remove()
    }, 4500)
}

adminTabs._element.addEventListener("shown.bs.tab", () => {})

serviceName.addEventListener('input', (e) => {
    serviceValue.value = serviceName.value.toLowerCase().replace(/\s+/g, '_')
})

function updateServiceFinalAmount() {
    if (!serviceFinalAmount) {
        return
    }

    const amount = parseFloat(serviceAmount?.value || 0)
    const discount = parseFloat(serviceDiscountPercentage?.value || 0)

    if (Number.isNaN(amount)) {
        serviceFinalAmount.value = ''
        return
    }

    const finalAmount = amount - ((amount * (Number.isNaN(discount) ? 0 : discount)) / 100)
    serviceFinalAmount.value = formatAdminMoney(finalAmount)
}

function renderFieldRow(item) {
    const row = document.createElement('tr')
    row.id = `field-row-${item.id}`
    row.innerHTML = `
        <td>${item?.name ?? 'No indicado'}</td>
        <td>${item?.disabled == 1 ? 'Deshabilitado' : 'Activo'}</td>
        <td>
            <button
                type="button"
                class="btn btn-primary btn-sm mb-1 field-edit-trigger"
                data-id="${item.id}"
                data-name="${item.name}"
                data-disabled="${item.disabled == 1 ? 1 : 0}">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <button
                type="button"
                class="btn btn-danger btn-sm mb-1 field-delete-trigger"
                data-id="${item.id}">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
    `

    return row
}

function upsertFieldRow(item, action = 'updated') {
    if (!fieldsTableBody || !item) {
        return
    }

    const newRow = renderFieldRow(item)
    const existingRow = document.getElementById(`field-row-${item.id}`)

    if (existingRow) {
        existingRow.replaceWith(newRow)
        return
    }

    if (action === 'created') {
        fieldsTableBody.prepend(newRow)
    } else {
        fieldsTableBody.appendChild(newRow)
    }
}

function renderValueRow(item) {
    const amount = parseFloat(item?.amount || 0)
    const discount = parseFloat(item?.discount_percentage || 0)
    const finalAmount = amount - ((amount * discount) / 100)

    const row = document.createElement('tr')
    row.id = `value-row-${item.id}`
    row.innerHTML = `
        <td>${item?.name ?? 'No indicado'}</td>
        <td>${formatAdminMoney(amount)}</td>
        <td>${item?.discount_percentage ?? 0}%</td>
        <td>${formatAdminMoney(finalAmount)}</td>
        <td>
            <button
                type="button"
                class="btn btn-primary btn-sm mb-1 value-edit-trigger"
                data-id="${item.id}"
                data-name="${item.name}"
                data-amount="${item.amount}"
                data-discount-percentage="${item.discount_percentage ?? 0}"
                data-value="${item.value}">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
        </td>
    `

    return row
}

function upsertValueRow(item, action = 'updated') {
    if (!valuesTableBody || !item) {
        return
    }

    const newRow = renderValueRow(item)
    const existingRow = document.getElementById(`value-row-${item.id}`)

    if (existingRow) {
        existingRow.replaceWith(newRow)
        return
    }

    if (action === 'created') {
        valuesTableBody.prepend(newRow)
    } else {
        valuesTableBody.appendChild(newRow)
    }
}

serviceAmount?.addEventListener('input', updateServiceFinalAmount)
serviceDiscountPercentage?.addEventListener('input', updateServiceFinalAmount)

fieldForm?.addEventListener('submit', async (e) => {
    e.preventDefault()

    const actionUrl = currentFieldId ? `${baseUrl}editField/${currentFieldId}` : fieldForm.action
    const formData = new FormData(fieldForm)

    try {
        const response = await fetch(actionUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })

        const result = await response.json()

        if (!response.ok || result.error) {
            showAdminNotice(result.message || 'No se pudo guardar el servicio', 'error')
            return
        }

        upsertFieldRow(result.item, result.action)
        fieldModal?.hide()
        showAdminNotice(result.message || 'Servicio guardado correctamente')
    } catch (error) {
        console.error('Error:', error)
        showAdminNotice('No se pudo guardar el servicio', 'error')
    }
})

valueForm?.addEventListener('submit', async (e) => {
    e.preventDefault()

    const formData = new FormData(valueForm)

    try {
        const response = await fetch(valueForm.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })

        const result = await response.json()

        if (!response.ok || result.error) {
            showAdminNotice(result.message || 'No se pudo guardar el valor', 'error')
            return
        }

        upsertValueRow(result.item, result.action)
        valueModal?.hide()
        showAdminNotice(result.message || 'Valor guardado correctamente')
    } catch (error) {
        console.error('Error:', error)
        showAdminNotice('No se pudo guardar el valor', 'error')
    }
})


document.addEventListener('click', async (e) => {
    const editFieldButton = e.target.closest('.field-edit-trigger')
    if (editFieldButton) {
        currentFieldId = editFieldButton.dataset.id
        fieldName.value = editFieldButton.dataset.name || ''
        disableFieldInput.checked = editFieldButton.dataset.disabled == '1'
        fieldModal?.show()
        return
    }

    const deleteFieldButton = e.target.closest('.field-delete-trigger')
    if (deleteFieldButton) {
        const fieldId = deleteFieldButton.dataset.id
        if (!confirm('Desea deshabilitar este servicio?')) {
            return
        }

        try {
            const response = await fetch(`${baseUrl}disableField/${fieldId}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })

            const result = await response.json()

            if (!response.ok || result.error) {
                showAdminNotice(result.message || 'No se pudo deshabilitar el servicio', 'error')
                return
            }

            const row = document.getElementById(`field-row-${fieldId}`)
            if (row) {
                row.remove()
            }
            showAdminNotice(result.message || 'Servicio deshabilitado')
        } catch (error) {
            console.error('Error:', error)
            showAdminNotice('No se pudo deshabilitar el servicio', 'error')
        }
        return
    }

    const editValueButton = e.target.closest('.value-edit-trigger')

    if (editValueButton) {
        serviceName.value = editValueButton.dataset.name
        serviceValue.value = editValueButton.dataset.value || editValueButton.dataset.name.toLowerCase().replace(/\s+/g, '_')
        serviceAmount.value = editValueButton.dataset.amount
        serviceDiscountPercentage.value = editValueButton.dataset.discountPercentage || '0'
        idValue.value = editValueButton.dataset.id
        updateServiceFinalAmount()
        valueModal?.show()
        return
    }

    if (e.target) {
        if (e.target.id == 'botonCompletarPago') {
            return
        } else if (e.target.id == 'saveRate' || e.target.id == 'saveRateSettings') {

            let data = {
                value: inputRate.value,
                qty_visitors: inputQtyVisitors.value
            }

            saveRate(`${baseUrl}saveRate`, data)

        } else if (e.target.id == 'saveGeneralSettings') {

            let data = {
                qty_visitors: inputQtyVisitors.value,
                allow_group_coordinator: inputAllowGroupCoordinator?.checked ? 1 : 0,
                notification_email: inputNotificationEmail?.value || '',
                invoice_email_subject: inputInvoiceEmailSubject?.value || '',
                invoice_email_message: inputInvoiceEmailMessage?.value || '',
                enable_pay_by_entries: inputEnablePayByEntries?.checked ? 1 : 0,
                pay_by_entries_min_entries: inputPayByEntriesMinEntries?.value || 0,
                pay_by_entries_min_days_before_booking: inputPayByEntriesMinDaysBeforeBooking?.value || 0,
                pay_by_entries_default_percentage: inputPayByEntriesDefaultPercentage?.value || 50
            }

            saveGeneralSettings(`${baseUrl}saveWebGeneral`, data)

        } else if (e.target.id == 'saveOfferRate') {

            let data = {
                value: inputOfferRate.value,
                description: descriptionOffer.value
            }

            saveOfferRate(`${baseUrl}saveOfferRate`, data)

        } else if (e.target.id == 'buttonCreateField') {
            currentFieldId = null
            fieldName.value = ''
            disableFieldInput.checked = false
            fieldModal?.show()

        } else if (e.target.id == 'eliminarReservaModal') {
            idBooking = e.target.dataset.id

            cancelBookingModal.show()
        } else if (e.target.id == 'cancelCancelBooking') {
            cancelBookingModal.hide()

        } else if (e.target.id == 'confirmCancelBooking') {
            let dataCancel = {
                idBooking: idBooking
            }

            cancelBooking(dataCancel)
        } else if (e.target.id == 'editarReservaModal') {

            editBookingModal.show()
        } else if (e.target.id == 'buttonCreateValue') {
            serviceName.value = ''
            serviceValue.value = ''
            serviceAmount.value = ''
            serviceDiscountPercentage.value = '0'
            idValue.value = ''
            updateServiceFinalAmount()
            valueModal?.show()
        }
    }
})

async function editBooking(data) {
    try {
        const response = await fetch(`${baseUrl}editBooking`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            alert('Reserva eliminada con ÃƒÆ’Ã‚Â©xito')

        } else {
            alert('Algo saliÃƒÆ’Ã‚Â³ mal. No se pudo eliminar la reserva.');
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}


async function cancelBooking(data) {
    try {
        const response = await fetch(`${baseUrl}cancelBooking`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            alert('Reserva anulada con ÃƒÆ’Ã‚Â©xito')

            cancelBookingModal.hide()
            location.reload(true)

        } else {
            alert('Algo saliÃƒÆ’Ã‚Â³ mal. No se pudo eliminar la reserva.');
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

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

                setTimeout(() => { spinnerCompletarPago.show() }, 500)

                completarPagoModalB.hide()

                contentPaymentResult.innerHTML = `
                <div class="modal-body modalResultPayment d-flex justify-content-center align-items-center flex-column" style="background-color: #157347;">
                    <h4 class="mb-5">Pago confirmado!</h4>
                    <i class="fa-regular fa-circle-check fa-2xl" style="margin-bottom: 20px;"></i>
                </div>`

                setTimeout(() => { modalResultPayment.show() }, 2000)
                setTimeout(() => { spinnerCompletarPago.hide() }, 2000)
                setTimeout(() => { location.reload(true) }, 3000)

            } else {
                setTimeout(() => { spinnerCompletarPago.show() }, 500)
                completarPagoModalB.hide()

                contentPaymentResult.innerHTML = `
                <div class="modal-body modalResultPayment d-flex justify-content-center align-items-center flex-column" style="background-color: #bb2d3b;">
                    <h4 class="mb-5">No se pudo guardar el pago. Vuelva a intentar</h4>
                    <i class="fa-regular fa-circle-xmark fa-2xl" style="margin-bottom: 20px;"></i>
                </div>`

                setTimeout(() => { modalResultPayment.show() }, 2000)
                setTimeout(() => { spinnerCompletarPago.hide() }, 2000)
                setTimeout(() => { location.reload(true) }, 3000)
            }

        } else {
            alert('Algo saliÃƒÆ’Ã‚Â³ mal. No se pudo ingresar el pago.');
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function saveRate(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            alert('Valor ingresado con ÃƒÆ’Ã‚Â©xito')
            location.reload(true)

        } else {
            alert('Algo saliÃƒÆ’Ã‚Â³ mal. No se pudo ingresar el valor.');
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function saveGeneralSettings(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })

        const responseData = await response.json()

        if (response.ok) {
            alert(responseData.message || 'ConfiguraciÃƒÆ’Ã‚Â³n guardada con ÃƒÆ’Ã‚Â©xito')
        } else {
            alert(responseData.message || 'No se pudo guardar la configuraciÃƒÆ’Ã‚Â³n')
        }

    } catch (error) {
        console.error('Error:', error)
        throw error
    }
}

async function saveOfferRate(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            alert('Valor ingresado con ÃƒÆ’Ã‚Â©xito')
            location.reload(true)

        } else {
            alert('Algo saliÃƒÆ’Ã‚Â³ mal. No se pudo ingresar el valor.');
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
            alert('Algo saliÃƒÆ’Ã‚Â³ mal. No se pudo obtener la informaciÃƒÆ’Ã‚Â³n.');
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function getEditField(id) {
    try {
        const response = await fetch(`${baseUrl}getField/${id}`);

        const responseData = await response.json();

        if (responseData.data != '') {

            fillDiv(responseData.data)

        } else {
            alert('Algo saliÃƒÆ’Ã‚Â³ mal. No se pudo obtener la informaciÃƒÆ’Ã‚Â³n.');
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function saveRate(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            showAdminNotice('Porcentaje de reserva actualizado')
        } else {
            showAdminNotice('No se pudo actualizar el porcentaje de reserva', 'error');
        }

    } catch (error) {
        console.error('Error:', error);
        showAdminNotice('No se pudo actualizar el porcentaje de reserva', 'error');
    }
}

async function saveGeneralSettings(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })

        const responseData = await response.json()

        if (response.ok) {
            showAdminNotice(responseData.message || 'Configuracion guardada correctamente')
        } else {
            showAdminNotice(responseData.message || 'No se pudo guardar la configuracion', 'error')
        }

    } catch (error) {
        console.error('Error:', error)
        showAdminNotice('No se pudo guardar la configuracion', 'error')
    }
}

async function saveOfferRate(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            showAdminNotice('Oferta guardada correctamente')
        } else {
            showAdminNotice('No se pudo guardar la oferta', 'error');
        }

    } catch (error) {
        console.error('Error:', error);
        showAdminNotice('No se pudo guardar la oferta', 'error');
    }
}

// async function getEditField(id) {
//     try {
//         const response = await fetch(`${baseUrl}getValue/${id}`);

//         const responseData = await response.json();

//         if (responseData.data != '') {

//             fillDivValues(responseData.data)

//         } else {
//             alert('Algo saliÃƒÆ’Ã‚Â³ mal. No se pudo obtener la informaciÃƒÆ’Ã‚Â³n.');
//         }

//     } catch (error) {
//         console.error('Error:', error);
//         throw error;
//     }
// }


function fillDiv(field) {
    let div = ''

    let disabledCheck
    if (field.disabled == 1) { disabledCheck = 'checked' }

    div = `
        <div class="editFields" id="editFields">
            <form action="${baseUrl}editField/${field.id}" method="POST">

                <div class="input-group mt-3 mb-3">
                    <span class="input-group-text" id="basic-addon1">Nombre servicio</span>
                    <input type="text" class="form-control" value="${field.name}" name="nombre" placeholder="Ingrese el nombre de la cancha" aria-label="Nombre servicio" aria-describedby="basic-addon1">
                </div>

                <div class="form-check form-switch mt-4 mb-4">
                    <input class="form-check-input" type="checkbox" role="switch" name="disabled" id="disableField" ${disabledCheck}>
                    <label class="form-check-label" for="disableField">Deshabilitar</label>
                </div>

                <button type="submit" class="btn btn-success">Guardar</button>
                <a href="${baseUrl}abmAdmin" type="button" class="btn btn-danger">Cancelar</a>
            </form>
        </div>
        `

    editFieldDiv.innerHTML = div
}

// function fillDivValues(value) {
//     let div = ''

//     let disabledCheck
//     if (field.disabled == 1) { disabledCheck = 'checked' }

//     div = `
//         <div class="editFields" id="editFields">
//             <form action="${baseUrl}editField/${value.id}" method="POST">

//                 <div class="form-check form-switch mt-4 mb-4">
//                     <input class="form-check-input" type="checkbox" role="switch" name="disabled" id="disableField" ${disabledCheck}>
//                     <label class="form-check-label" for="disableField">Deshabilitar</label>
//                 </div>

//                 <div class="input-group mb-3">
//                     <span class="input-group-text">Valor sin iluminaciÃƒÆ’Ã‚Â³n</span>
//                     <input type="text" class="form-control" value="${value.value}" name="valor" placeholder="Ingrese valor por hora sin iluminaciÃƒÆ’Ã‚Â³n" aria-label="Valor">
//                 </div>

//                 <div class="input-group mb-3">
//                     <span class="input-group-text">Valor con iluminaciÃƒÆ’Ã‚Â³n</span>
//                     <input type="text" class="form-control" value="${value.ilumination_value}" name="valorIluminacion" placeholder="Ingrese valor por hora con iluminaciÃƒÆ’Ã‚Â³n" aria-label="Valor">
//                 </div>

//                 <button type="submit" class="btn btn-success">Guardar</button>
//                 <a href="${baseUrl}abmAdmin" type="button" class="btn btn-danger">Cancelar</a>
//             </form>
//         </div>
//         `

//     editFieldDiv.innerHTML = div
// }
