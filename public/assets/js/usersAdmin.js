const userModalElement = document.getElementById('userModal')
const userModal = userModalElement ? new bootstrap.Modal(userModalElement) : null
const usersTableBody = document.getElementById('usersTableBody')
const userIdField = document.getElementById('userIdField')
const userUsername = document.getElementById('userUsername')
const userDisplayName = document.getElementById('userDisplayName')
const userPassword = document.getElementById('userPassword')
const userRepeatPassword = document.getElementById('userRepeatPassword')
const userRepeatPasswordGroup = document.getElementById('userRepeatPasswordGroup')
const userSuperadmin = document.getElementById('userSuperadmin')
const userActive = document.getElementById('userActive')
const saveUserButton = document.getElementById('saveUserButton')

function resetUserForm() {
    if (!userIdField) {
        return
    }

    userIdField.value = ''
    userUsername.value = ''
    userDisplayName.value = ''
    userPassword.value = ''
    userRepeatPassword.value = ''
    userSuperadmin.checked = false
    userActive.checked = true
    if (userRepeatPasswordGroup) {
        userRepeatPasswordGroup.classList.remove('d-none')
    }
}

function renderUserRow(item) {
    const row = document.createElement('tr')
    row.id = `user-row-${item.id}`
    row.innerHTML = `
        <td>${item?.user ?? 'No indicado'}</td>
        <td>${item?.name ?? 'No indicado'}</td>
        <td>${item?.superadmin == 1 ? 'Si' : 'No'}</td>
        <td>${item?.active == 1 ? 'Activo' : 'Inactivo'}</td>
        <td>
            <button
                type="button"
                class="btn btn-primary btn-sm mb-1 user-edit-trigger"
                data-id="${item.id}"
                data-user="${item.user ?? ''}"
                data-name="${item.name ?? ''}"
                data-superadmin="${item.superadmin == 1 ? 1 : 0}"
                data-active="${item.active == 1 ? 1 : 0}">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <button
                type="button"
                class="btn btn-danger btn-sm mb-1 user-delete-trigger"
                data-id="${item.id}">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
    `

    return row
}

function upsertUserRow(item, action = 'updated') {
    if (!usersTableBody || !item) {
        return
    }

    const newRow = renderUserRow(item)
    const existingRow = document.getElementById(`user-row-${item.id}`)

    if (existingRow) {
        existingRow.replaceWith(newRow)
        return
    }

    if (action === 'created') {
        usersTableBody.prepend(newRow)
    } else {
        usersTableBody.appendChild(newRow)
    }
}

document.addEventListener('click', async (e) => {
    const createButton = e.target.closest('#buttonCreateUser')
    if (createButton) {
        resetUserForm()
        userModal?.show()
        return
    }

    const editButton = e.target.closest('.user-edit-trigger')
    if (editButton) {
        userIdField.value = editButton.dataset.id || ''
        userUsername.value = editButton.dataset.user || ''
        userDisplayName.value = editButton.dataset.name || ''
        userPassword.value = ''
        userRepeatPassword.value = ''
        userSuperadmin.checked = editButton.dataset.superadmin === '1'
        userActive.checked = editButton.dataset.active !== '0'
        if (userRepeatPasswordGroup) {
            userRepeatPasswordGroup.classList.add('d-none')
        }
        userModal?.show()
        return
    }

    const deleteButton = e.target.closest('.user-delete-trigger')
    if (deleteButton) {
        if (!confirm('Desea desactivar este usuario?')) {
            return
        }

        try {
            const response = await fetch(`${baseUrl}disableUser/${deleteButton.dataset.id}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })

            const result = await response.json()

            if (!response.ok || result.error) {
                showAdminNotice(result.message || 'No se pudo desactivar el usuario', 'error')
                return
            }

            const row = document.getElementById(`user-row-${deleteButton.dataset.id}`)
            if (row) {
                row.remove()
            }
            showAdminNotice(result.message || 'Usuario desactivado correctamente')
        } catch (error) {
            console.error('Error:', error)
            showAdminNotice('No se pudo desactivar el usuario', 'error')
        }
        return
    }

    const saveButton = e.target.closest('#saveUserButton')
    if (!saveButton) {
        return
    }

    const isEdit = userIdField.value !== ''
    const payload = {
        id: userIdField.value,
        user: userUsername.value.trim(),
        name: userDisplayName.value.trim(),
        password: userPassword.value,
        repeat_password: userRepeatPassword.value,
        superadmin: userSuperadmin.checked ? 1 : 0,
        active: userActive.checked ? 1 : 0,
    }

    if (!payload.user || !payload.name) {
        showAdminNotice('Debe completar usuario y nombre', 'error')
        return
    }

    if (!isEdit) {
        if (!payload.password || !payload.repeat_password) {
            showAdminNotice('Debe completar las contrasenas', 'error')
            return
        }

        if (payload.password !== payload.repeat_password) {
            showAdminNotice('Las contrasenas no coinciden', 'error')
            return
        }
    }

    if (isEdit && payload.password !== '' && payload.password !== payload.repeat_password) {
        showAdminNotice('Las contrasenas no coinciden', 'error')
        return
    }

    saveButton.disabled = true
    saveButton.innerText = 'Guardando...'

    try {
        const response = await fetch(`${baseUrl}${isEdit ? 'editUser' : 'saveUser'}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })

        const result = await response.json()

        if (!response.ok || result.error) {
            showAdminNotice(result.message || 'No se pudo guardar el usuario', 'error')
            return
        }

        upsertUserRow(result.item, isEdit ? 'updated' : 'created')
        userModal?.hide()
        showAdminNotice(result.message || 'Usuario guardado correctamente')
    } catch (error) {
        console.error('Error:', error)
        showAdminNotice('No se pudo guardar el usuario', 'error')
    } finally {
        saveButton.disabled = false
        saveButton.innerText = 'Guardar'
    }
})
