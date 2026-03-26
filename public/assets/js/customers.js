// const checkCustomersWithOffer = document.getElementById('checkCustomersWithOffer')

// checkCustomersWithOffer.addEventListener('change', async (e) => {
//     if (checkCustomersWithOffer.checked) {
//         await getCustomersWithOffer()
//     }
// })

document.addEventListener('click', async (e) => {
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
    const customersDiv = document.getElementById('customersDiv')
    let tr = ''
    let actions = ''

    if (Array.isArray(data)) {
        const phoneCell = customer?.complete_phone ?
            `<td><a href="https://wa.me/+54${customer.complete_phone}" target="_blank">${customer.complete_phone}</a></td>` :
            `<td>No indicado</td>`;

        const emailCell = customer?.email ?
            `<td><a href="mailto:${customer.email}" target="_blank">${customer.email}</a></td>` :
            `<td>No indicado</td>`;

        data.forEach(customer => {
            let offer = ''
            customer?.offer == 1 ? offer = 'Si' : offer = 'No'

            actions = `
            <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                <button type="button" class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    Acciones
                </button>
                <ul class="dropdown-menu">
                    <li><a type="button" href="${baseUrl}customers/editWindow/${customer?.id})" class="btn btn-primary dropdown-item" id="" data-id="${customer?.id}">Editar cliente</a></li>
                    <li><a type="button" href="${baseUrl}customers/deleteCustomer/${customer?.id}})" class="btn btn-primary dropdown-item" id="" data-id="${customer?.id}">Eliminar cliente</a></li>
                </ul>
            </div>
            `

            tr += `
            <tr>
                <td>${customer?.name}</td>
                <td>${customer?.type_institution}</td>
                <td>${customer?.dni}</td>
                ${phoneCell}
                <td>${customer?.city}</td>
                ${emailCell}
                <td>${customer?.quantity}</td>
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
            `<td>No indicado</td>`;

        let offer = '';
        data?.offer == 1 ? offer = 'Si' : offer = 'No';

        actions = `
            <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                <button type="button" class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    Acciones
                </button>
                <ul class="dropdown-menu">
                    <li><a type="button" href="${baseUrl}customers/editWindow/${data?.id})" class="btn btn-primary dropdown-item" id="" data-id="${data?.id}">Editar cliente</a></li>
                    <li><a type="button" href="${baseUrl}customers/deleteCustomer/${data?.id}})" class="btn btn-primary dropdown-item" id="" data-id="${data?.id}">Eliminar cliente</a></li>
                </ul>
            </div>
            `

        tr += `
            <tr>
                <td>${data?.name}</td>
                <td>${data?.type_institution}</td>
                <td>${data?.dni}</td>
                ${phoneCell}
                <td>${data?.city}</td>
                ${emailCell}
                <td>${data?.quantity}</td>
                <td>${actions}</td>
            </tr>
            `
    } else {
        console.error('El parámetro data no es un formato válido.');
        return;
    }

    customersDiv.innerHTML = tr

}