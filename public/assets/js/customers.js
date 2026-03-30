// const checkCustomersWithOffer = document.getElementById('checkCustomersWithOffer')

// checkCustomersWithOffer.addEventListener('change', async (e) => {
//     if (checkCustomersWithOffer.checked) {
//         await getCustomersWithOffer()
//     }
// })

const customerFrameModalElement = document.getElementById('customerFrameModal')
const customerFrame = document.getElementById('customerFrame')
const customerFrameModal = customerFrameModalElement ? new bootstrap.Modal(customerFrameModalElement) : null
const customersDiv = document.getElementById('customersDiv')

function renderCustomerRow(customer) {
    const row = document.createElement('tr')
    row.id = `customer-row-${customer.id}`

    const phoneCell = customer?.complete_phone ?
        `<td><a href="https://wa.me/+54${customer.complete_phone}" target="_blank">${customer.complete_phone}</a></td>` :
        `<td>No indicado</td>`

    const emailCell = customer?.email ?
        `<td><a href="mailto:${customer.email}" target="_blank">${customer.email}</a></td>` :
        `<td>No indicado</td>`

    row.innerHTML = `
        <td>${customer?.name ?? 'No indicado'}</td>
        <td>${customer?.type_institution ?? 'No indicado'}</td>
        <td>${customer?.dni ?? 'No indicado'}</td>
        ${phoneCell}
        <td>${customer?.city ?? 'No indicado'}</td>
        ${emailCell}
        <td>${customer?.quantity ?? 0}</td>
        <td>${customer?.offer ?? 0}%</td>
        <td>
            <button type="button" class="btn btn-primary btn-sm mb-1 customer-frame-trigger" data-customer-frame-url="${baseUrl}customers/editWindow/${customer?.id}?embed=1"><i class="fa-solid fa-pen-to-square"></i></button>
            <a href="${baseUrl}customers/deleteCustomer/${customer?.id}" class="btn btn-danger btn-sm mb-1" data-id="${customer?.id}"><i class="fa-solid fa-trash"></i></a>
        </td>
    `

    return row
}

function upsertCustomerRow(customer, action = 'updated') {
    if (!customersDiv || !customer) {
        return
    }

    const newRow = renderCustomerRow(customer)
    const existingRow = document.getElementById(`customer-row-${customer.id}`)

    if (existingRow) {
        existingRow.replaceWith(newRow)
        return
    }

    if (action === 'created') {
        customersDiv.prepend(newRow)
    } else {
        customersDiv.appendChild(newRow)
    }
}

function openCustomerFrame(url) {
    if (!customerFrameModal || !customerFrame) {
        return
    }

    customerFrame.src = url
    customerFrameModal.show()
}

function closeCustomerFrame() {
    if (!customerFrameModal || !customerFrame) {
        return
    }

    customerFrameModal.hide()
    customerFrame.src = 'about:blank'
}

document.addEventListener('click', async (e) => {
    const triggerButton = e.target.closest('.customer-frame-trigger')
    if (triggerButton) {
        e.preventDefault()
        openCustomerFrame(triggerButton.dataset.customerFrameUrl)
        return
    }

    if (e.target) {
        if (e.target.id == 'searchCustomerButton') {
            // checkCustomersWithOffer.checked = false
            const customerPhone = document.getElementById('searchCustomerInput')
            let customers

            if (customerPhone.value == '') {
                customers = await searchCustomer(`${baseUrl}customers/getCustomers`)
            } else {
                customers = await searchCustomer(`${baseUrl}customers/getCustomer/${customerPhone.value}`)
            }
        } else if (e.target.id == 'setOfferTrue') {
            setOfferTrue(true)

        } else if (e.target.id == 'setOfferFalse') {
            setOfferFalse(false)
        }
    }
})

if (customerFrameModalElement) {
    customerFrameModalElement.addEventListener('hidden.bs.modal', () => {
        if (customerFrame) {
            customerFrame.src = 'about:blank'
        }
    })
}

window.addEventListener('message', (event) => {
    if (event.origin !== window.location.origin) {
        return
    }

    if (event.data?.type === 'customer-form-saved') {
        closeCustomerFrame()
        upsertCustomerRow(event.data.customer, event.data.action)
    }
})

async function setOfferTrue(data) {
    try {
        const response = await fetch(`${baseUrl}customers/setOfferTrue`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            alert('Operación exitosa')
        } else {
            alert('Ocurrió un error y no se pudo actualizar el valor')
        }

        location.reload(true)

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function setOfferFalse(data) {
    try {
        const response = await fetch(`${baseUrl}customers/setOfferFalse`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            alert('Operación exitosa')
        } else {
            alert('Ocurrió un error y no se pudo actualizar el valor')
        }

        location.reload(true)

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}


async function getCustomersWithOffer() {
    try {
        const response = await fetch(`${baseUrl}customers/getCustomersWithOffer`);

        const responseData = await response.json();

        fillCustomersTable(responseData.data)

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function searchCustomer(url) {
    try {
        const response = await fetch(url);

        const responseData = await response.json();

        if (responseData.data) {
            fillCustomersTable(responseData.data)

        } else {
            alert('No hay resultados para la búqueda realizada');
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function fillCustomersTable(data) {
    let tr = ''
    let actions = ''

    if (Array.isArray(data)) {
        data.forEach(customer => {
            const phoneCell = customer?.complete_phone ?
                `<td><a href="https://wa.me/+54${customer.complete_phone}" target="_blank">${customer.complete_phone}</a></td>` :
                `<td>No indicado</td>`

            const emailCell = customer?.email ?
                `<td><a href="mailto:${customer.email}" target="_blank">${customer.email}</a></td>` :
                `<td>No indicado</td>`

            actions = `
            <button type="button" class="btn btn-primary btn-sm mb-1 customer-frame-trigger" data-customer-frame-url="${baseUrl}customers/editWindow/${customer?.id}?embed=1">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <a href="${baseUrl}customers/deleteCustomer/${customer?.id}" class="btn btn-danger btn-sm mb-1" data-id="${customer?.id}">
                <i class="fa-solid fa-trash"></i>
            </a>
            `

            tr += `
            <tr id="customer-row-${customer?.id}">
                <td>${customer?.name}</td>
                <td>${customer?.type_institution}</td>
                <td>${customer?.dni}</td>
                ${phoneCell}
                <td>${customer?.city}</td>
                ${emailCell}
                <td>${customer?.quantity}</td>
                <td>${customer?.offer ?? 0}%</td>
                <td>${actions}</td>
            </tr>
            `
        })
    } else if (typeof data === 'object') {
        const phoneCell = data?.complete_phone ?
            `<td><a href="https://wa.me/+54${data.complete_phone}" target="_blank">${data.complete_phone}</a></td>` :
            `<td>No indicado</td>`;

        const emailCell = data?.email ?
            `<td><a href="mailto:${data.email}" target="_blank">${data.email}</a></td>` :
            `<td>No indicado</td>`

        actions = `
            <button type="button" class="btn btn-primary btn-sm mb-1 customer-frame-trigger" data-customer-frame-url="${baseUrl}customers/editWindow/${data?.id}?embed=1">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <a href="${baseUrl}customers/deleteCustomer/${data?.id}" class="btn btn-danger btn-sm mb-1" data-id="${data?.id}">
                <i class="fa-solid fa-trash"></i>
            </a>
            `

        tr += `
            <tr id="customer-row-${data?.id}">
                <td>${data?.name}</td>
                <td>${data?.type_institution}</td>
                <td>${data?.dni}</td>
                ${phoneCell}
                <td>${data?.city}</td>
                ${emailCell}
                <td>${data?.quantity}</td>
                <td>${data?.offer ?? 0}%</td>
                <td>${actions}</td>
            </tr>
            `
    } else {
        console.error('El parámetro data no es un formato válido.');
        return;
    }

    customersDiv.innerHTML = tr

}
