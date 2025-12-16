document.addEventListener('DOMContentLoaded', function () {

    // --- HELPER FUNCTIONS ---
    function mostrarErrorInput(input, mensaje) {
        input.style.border = "2px solid red";
        // Eliminar border-color normal si lo hubiera

        let errorSpan = input.parentNode.querySelector('.error-msg-inline');
        if (!errorSpan) {
            errorSpan = document.createElement('div');
            errorSpan.classList.add('error-msg-inline');
            errorSpan.style.color = "red";
            errorSpan.style.fontSize = "0.85rem";
            errorSpan.style.marginTop = "5px";
            input.parentNode.insertBefore(errorSpan, input.nextSibling);
        }
        errorSpan.innerText = mensaje;
    }

    function limpiarErrorInput(input) {
        input.style.border = ""; // Reset to default
        let errorSpan = input.parentNode.querySelector('.error-msg-inline');
        if (errorSpan) {
            errorSpan.remove();
        }
    }

    // --- VALIDATION LOGIC FUNCTIONS ---

    // Usuario: Min 3 chars
    function checkUsername(input) {
        if (input.value.trim().length < 3) {
            mostrarErrorInput(input, 'El nombre de usuario debe tener al menos 3 caracteres.');
            return false;
        }
        limpiarErrorInput(input);
        return true;
    }

    // Required Text (Nombre, Nombre Sala)
    function checkRequired(input, fieldName) {
        if (input.value.trim() === '') {
            mostrarErrorInput(input, `El ${fieldName} es obligatorio.`);
            return false;
        }
        limpiarErrorInput(input);
        return true;
    }

    // Email
    function checkEmail(input) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(input.value.trim())) {
            mostrarErrorInput(input, 'Por favor, introduce un email válido.');
            return false;
        }
        limpiarErrorInput(input);
        return true;
    }

    // Password (Optional, but if filled min 6)
    function checkPassword(input) {
        // Si está vacío es válido (porque es opcional editarla)
        if (input.value.length > 0 && input.value.length < 6) {
            mostrarErrorInput(input, 'Si cambias la contraseña, debe tener al menos 6 caracteres.');
            return false;
        }
        limpiarErrorInput(input);
        return true;
    }

    // File (Required, Type)
    function checkFile(input) {
        if (input.files.length === 0) {
            mostrarErrorInput(input, 'Debes seleccionar una imagen.');
            return false;
        }
        const file = input.files[0];
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            mostrarErrorInput(input, 'El archivo debe ser una imagen (JPG, PNG, GIF, WEBP).');
            return false;
        }
        limpiarErrorInput(input);
        return true;
    }

    // File Optional (Edit Sala) - Check type only if file selected
    function checkFileOptional(input) {
        if (input.files.length > 0) {
            const file = input.files[0];
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                mostrarErrorInput(input, 'El archivo debe ser una imagen (JPG, PNG, GIF, WEBP).');
                return false;
            }
        }
        limpiarErrorInput(input);
        return true;
    }


    // --- ATTACH LISTENERS ---

    // 1. EDIT USER
    const formEditUser = document.getElementById('formEditUser');
    if (formEditUser) {
        const usernameInput = document.getElementById('edit_user_username');
        const nombreInput = document.getElementById('edit_user_nombre');
        const apellidoInput = document.getElementById('edit_user_apellido'); // NEW
        const emailInput = document.getElementById('edit_user_email');
        const passwordInput = document.getElementById('edit_user_password');

        // On Blur
        usernameInput.addEventListener('blur', () => checkUsername(usernameInput));
        nombreInput.addEventListener('blur', () => checkRequired(nombreInput, 'nombre'));
        apellidoInput.addEventListener('blur', () => checkRequired(apellidoInput, 'apellido')); // NEW
        emailInput.addEventListener('blur', () => checkEmail(emailInput));
        passwordInput.addEventListener('blur', () => checkPassword(passwordInput));

        // On Submit
        formEditUser.addEventListener('submit', function (e) {
            const v1 = checkUsername(usernameInput);
            const v2 = checkRequired(nombreInput, 'nombre');
            const v3 = checkRequired(apellidoInput, 'apellido'); // NEW
            const v4 = checkEmail(emailInput);
            const v5 = checkPassword(passwordInput);

            if (!v1 || !v2 || !v3 || !v4 || !v5) {
                e.preventDefault();
            }
        });
    }

    // 2. EDIT SALA
    const formEditSala = document.getElementById('formEditSala');
    if (formEditSala) {
        const nombreInput = document.getElementById('edit_sala_nombre');
        const fileInput = document.getElementById('edit_sala_file');

        nombreInput.addEventListener('blur', () => checkRequired(nombreInput, 'nombre de la sala'));
        fileInput.addEventListener('change', () => checkFileOptional(fileInput)); // File uses change

        formEditSala.addEventListener('submit', function (e) {
            const v1 = checkRequired(nombreInput, 'nombre de la sala');
            const v2 = checkFileOptional(fileInput);

            if (!v1 || !v2) e.preventDefault();
        });
    }

    // 3. ADD SALA
    const formAddSala = document.getElementById('formAddSala');
    if (formAddSala) {
        const nombreInput = formAddSala.querySelector('input[name="nombre"]');
        const fileInput = formAddSala.querySelector('input[name="imagen_file"]');

        nombreInput.addEventListener('blur', () => checkRequired(nombreInput, 'nombre de la sala'));
        fileInput.addEventListener('change', () => checkFile(fileInput)); // Change for file

        formAddSala.addEventListener('submit', function (e) {
            const v1 = checkRequired(nombreInput, 'nombre de la sala');
            const v2 = checkFile(fileInput);

            if (!v1 || !v2) e.preventDefault();
        });
    }

});
