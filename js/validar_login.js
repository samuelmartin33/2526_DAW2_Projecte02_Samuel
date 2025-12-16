window.onload = function () {
    const form = document.getElementById("loginForm");
    const username = document.getElementById("username");
    const password = document.getElementById("password");

    // Helper functions
    function mostrarErrorInput(input, mensaje) {
        const wrapper = input.closest('.input-group');
        if (wrapper) wrapper.style.border = "2px solid red";
        else input.style.border = "2px solid red";

        let errorSpan = wrapper ? wrapper.nextElementSibling : input.nextElementSibling;

        if (!errorSpan || !errorSpan.classList.contains('error-msg-inline')) {
            errorSpan = document.createElement('div');
            errorSpan.classList.add('error-msg-inline');
            errorSpan.style.color = "red";
            errorSpan.style.fontSize = "0.85rem";
            errorSpan.style.marginBottom = "10px";
            errorSpan.style.textAlign = "center";

            if (wrapper) wrapper.parentNode.insertBefore(errorSpan, wrapper.nextSibling);
            else input.parentNode.insertBefore(errorSpan, input.nextSibling);
        }
        errorSpan.innerText = mensaje;
    }

    function limpiarErrorInput(input) {
        const wrapper = input.closest('.input-group');
        if (wrapper) wrapper.style.border = "";
        else input.style.border = "";

        let errorSpan = wrapper ? wrapper.nextElementSibling : input.nextElementSibling;
        if (errorSpan && errorSpan.classList.contains('error-msg-inline')) {
            errorSpan.remove();
        }

        const generalError = document.querySelector(".error");
        if (generalError) generalError.style.display = 'none';
    }

    // Individual Validation Functions
    function checkUsername() {
        if (!username.value) {
            mostrarErrorInput(username, "Selecciona un usuario.");
            return false;
        }
        limpiarErrorInput(username);
        return true;
    }

    function checkPassword() {
        if (!password.value) {
            mostrarErrorInput(password, "Introduce tu contraseña.");
            return false;
        } else if (password.value.length < 6) {
            mostrarErrorInput(password, "La contraseña debe tener al menos 6 caracteres.");
            return false;
        }
        limpiarErrorInput(password);
        return true;
    }

    // Attach Blur Listeners
    if (username) {
        username.onblur = checkUsername;
        // Also clear if they change it
        username.onchange = checkUsername;
    }

    if (password) {
        password.onblur = checkPassword;
    }

    if (form) {
        form.onsubmit = function (e) {
            const v1 = checkUsername();
            const v2 = checkPassword();

            if (!v1 || !v2) {
                e.preventDefault();
            }
        };
    }
};