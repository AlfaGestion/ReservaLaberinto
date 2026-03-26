const selectUser = document.getElementById('selectUser')
const formUsers = document.getElementById('formUsers')
const formButtons = document.getElementById('formButtons')
const buttonEdit = document.getElementById('buttonEdit')


document.addEventListener('change', async (e) => {
    if (e.target) {
        if (e.target.id == 'selectUser') {
            formUsers.classList.add('d-none')
            formButtons.classList.remove('d-none')

            user = await getUser(selectUser.value)

            fillForm(user.data)
        }
    }
})

document.addEventListener('click', (e) => {
    if (e.target) {
        if (e.target.id == 'buttonEdit') {
            e.preventDefault
            const userEdit = document.getElementById('userEdit')
            const passwordEdit = document.getElementById('passwordEdit')
            const repeatPasswordEdit = document.getElementById('repeatPasswordEdit')
            const nameEdit = document.getElementById('nameEdit')
            const superadminRadio = document.getElementById('superadminRadioEdit')

            if(passwordEdit.value == '' || repeatPasswordEdit.value == ''){
                return alert('Debe completar todos los campos')
            }

            if(passwordEdit.value != repeatPasswordEdit.value){
                return alert('Las contraseñas no coinciden')
            }

            data = {
                id : user.data.id,
                user : userEdit.value,
                password : passwordEdit.value,
                name : nameEdit.value,
                superadmin : superadminRadio.checked
            }

           editUser(data)
        }
    }
})

function fillForm(user) {
    const formDiv = document.getElementById('formselectUser')
    let superadmin = user.superadmin == '1' ? 'checked' : ''
    let form = ''

    form = `
        <div class="form-group has-feedback mb-3 d-flex align-items-center justify-content-center">
            <input type="text" name="user" value="${user.user}" id="userEdit" class="form-control" placeholder="Usuario">
        </div>
        
        <div class="form-group has-feedback mb-3 d-flex align-items-center justify-content-center">
            <input type="password" name="password" id="passwordEdit" class="form-control" placeholder="Contraseña">
        </div>
        
        <div class="form-group has-feedback mb-3 d-flex align-items-center justify-content-center">
            <input type="password" name="repeat_password" id="repeatPasswordEdit" class="form-control" placeholder="Repetir contraseña">
        </div>
        
        <div class="form-group has-feedback mb-3 d-flex align-items-center justify-content-center">
            <input type="text" name="name" class="form-control" value="${user.name}" id="nameEdit" placeholder="Nombre">
        </div>
        
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="superadmin" id="superadminRadioEdit" ${superadmin}>
            <label class="form-check-label" for="superadmin">
                Superadmin
            </label>
        </div>
    
    `

    formDiv.innerHTML = form
}


async function getUser(id) {
    try {
        const response = await fetch(`${baseUrl}/getUser/${id}`);

        const responseData = await response.json();

        return responseData

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

async function editUser(data) {
    try {
        const response = await fetch(`${baseUrl}/editUser`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const responseData = await response.json();

        if(response.ok){
            alert('Usuario editado correctamente')
            location.reload()
        } else {
            alert('Las contraseñas no coinciden')
        }

    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}