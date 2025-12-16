document.addEventListener('DOMContentLoaded', function () {

    // --- HELPER FUNCTIONS ---
    function mostrarErrorInput(input, mensaje) {
        input.style.border = "2px solid red";

        let errorSpan = input.closest('.mb-3, .col-6').querySelector('.error-msg-inline');
        if (!errorSpan) {
            errorSpan = document.createElement('div');
            errorSpan.classList.add('error-msg-inline');
            errorSpan.style.color = "red";
            errorSpan.style.fontSize = "0.85rem";
            errorSpan.style.marginTop = "5px";
            // Insertar después del input
            input.parentNode.appendChild(errorSpan);
        }
        errorSpan.innerText = mensaje;
    }

    function limpiarErrorInput(input) {
        input.style.border = "";
        let errorSpan = input.closest('.mb-3, .col-6')?.querySelector('.error-msg-inline');
        if (errorSpan) {
            errorSpan.remove();
        }
    }


    // --- VALIDATION FUNCTIONS ---

    function checkCliente(input) {
        if (!input) return true;
        if (input.value === "") {
            mostrarErrorInput(input, "Debes seleccionar un cliente.");
            return false;
        }
        limpiarErrorInput(input);
        return true;
    }

    function checkFechaInicio(input, inputFin) {
        if (!input) return true;
        if (!input.value) {
            mostrarErrorInput(input, "Debes seleccionar una fecha de inicio.");
            return false;
        }

        // Date Logic vs End Date
        if (inputFin && inputFin.value) {
            if (new Date(input.value) >= new Date(inputFin.value)) {
                mostrarErrorInput(input, "La fecha de inicio debe ser anterior a la de fin.");
                return false;
            }
        }

        limpiarErrorInput(input);
        return true;
    }

    function checkComensales(input, maxVal) {
        if (!input) return true;
        const val = parseInt(input.value);
        const max = maxVal || 100;

        if (isNaN(val) || val < 1) {
            mostrarErrorInput(input, "Debes indicar al menos 1 comensal.");
            return false;
        } else if (val > max) {
            mostrarErrorInput(input, `Esta mesa solo admite hasta ${max} comensales.`);
            return false;
        }

        limpiarErrorInput(input);
        return true;
    }


    // --- GENERIC FORM HANDLER ---
    function setupValidation(formId, inputMaxId) {
        const form = document.getElementById(formId);
        if (!form) return;

        const cliente = form.querySelector('select[name="id_cliente"]');
        const fechaInicio = form.querySelector('input[name="fecha_inicio"]');
        const fechaFin = form.querySelector('input[name="fecha_fin"]');
        const comensales = form.querySelector('input[name="num_comensales"]');

        // Listeners
        if (cliente) {
            cliente.addEventListener('blur', () => checkCliente(cliente));
            cliente.addEventListener('change', () => checkCliente(cliente)); // Select change also triggers
        }

        if (fechaInicio) {
            fechaInicio.addEventListener('blur', () => checkFechaInicio(fechaInicio, fechaFin));
            fechaInicio.addEventListener('change', () => checkFechaInicio(fechaInicio, fechaFin));
        }

        // Fecha fin listeners if it's visible (modalReservar)
        if (fechaFin && fechaFin.type !== 'hidden') {
            fechaFin.addEventListener('blur', () => checkFechaInicio(fechaInicio, fechaFin)); // Re-check logic
            fechaFin.addEventListener('change', () => checkFechaInicio(fechaInicio, fechaFin));
        }

        if (comensales) {
            // Need to get dynamic max
            // We can assume max is set in the attribute already by JS in the view
            const getMax = () => parseInt(comensales.max) || 100;

            comensales.addEventListener('blur', () => checkComensales(comensales, getMax()));
        }

        form.addEventListener('submit', function (e) {
            const v1 = checkCliente(cliente);
            const v2 = checkFechaInicio(fechaInicio, fechaFin);
            const v3 = checkComensales(comensales, parseInt(comensales?.max) || 100);

            if (!v1 || !v2 || !v3) {
                e.preventDefault();
            }
        });
    }

    // Init
    setupValidation('formNuevaReserva', 'new_num_comensales');
    setupValidation('formReservarSala', 'reserva_comensales');

});
