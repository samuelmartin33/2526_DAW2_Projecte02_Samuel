document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById("asignar-mesa-form");
    const inputComensales = document.getElementById("num-comensales");

    if (!form || !inputComensales) return;

    // Functions
    function mostrarErrorInput(input, mensaje) {
        input.style.border = "2px solid red";

        let errorSpan = input.parentNode.querySelector('.error-msg-inline');
        if (!errorSpan) {
            errorSpan = document.createElement('div');
            errorSpan.classList.add('error-msg-inline');
            errorSpan.style.color = "red";
            errorSpan.style.fontSize = "0.85rem";
            errorSpan.style.marginTop = "5px";
            input.parentNode.appendChild(errorSpan);
        }
        errorSpan.innerText = mensaje;
    }

    function limpiarErrorInput(input) {
        input.style.border = "";
        const errorSpan = input.parentNode.querySelector('.error-msg-inline');
        if (errorSpan) errorSpan.remove();
    }

    function validateComensales() {
        const valor = inputComensales.value.trim();
        const maxInput = document.getElementById("max-sillas");
        const maxSillas = maxInput ? parseInt(maxInput.value) : 100;

        // Campo vacío
        if (!valor) {
            mostrarErrorInput(inputComensales, "Debes indicar el número de comensales.");
            return false;
        }

        // No es número
        if (isNaN(valor)) {
            mostrarErrorInput(inputComensales, "Introduce un número válido.");
            return false;
        }

        const num = parseInt(valor);

        if (num < 1) {
            mostrarErrorInput(inputComensales, "El mínimo permitido es 1 comensal.");
            return false;
        }

        if (num > maxSillas) {
            mostrarErrorInput(inputComensales, "La mesa no admite más de " + maxSillas + " comensales.");
            return false;
        }

        limpiarErrorInput(inputComensales);
        return true;
    }

    // Listener On Blur
    inputComensales.addEventListener('blur', validateComensales);

    // On Submit
    form.addEventListener('submit', function (e) {
        if (!validateComensales()) {
            e.preventDefault();
        }
    });

});
