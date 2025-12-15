document.addEventListener('DOMContentLoaded', function () {

    // --- Guardamos elementos del DOM ---
    const form = document.getElementById("asignar-mesa-form");
    const inputComensales = document.getElementById("num-comensales");
    const botonAsignar = document.getElementById("btn-asignar");

    // Obtenemos el div de error que (ahora) está en el HTML
    const errorDiv = document.querySelector(".error");

    if (!form || !inputComensales || !botonAsignar || !errorDiv) {
        // Si falta algo retornamos para evitar errores en consola
        return;
    }

    // --- Función para mostrar error ---
    function mostrarError(mensaje) {
        errorDiv.innerHTML = mensaje;
        errorDiv.style.display = "block";
        // No deshabilitamos el botón permanentemente, solo mostramos el error
    }

    // --- Función para limpiar error ---
    function limpiarError() {
        errorDiv.innerHTML = "";
        errorDiv.style.display = "none";
    }

    // --- Función de validación ---
    function validarCampo() {
        limpiarError();

        const valor = inputComensales.value.trim();
        const maxInput = document.getElementById("max-sillas");
        const maxSillas = maxInput ? parseInt(maxInput.value) : 100;

        // Campo vacío
        if (!valor) {
            mostrarError("Debes indicar el número de comensales.");
            return false;
        }

        // No es número
        if (isNaN(valor)) {
            mostrarError("Introduce un número válido.");
            return false;
        }

        const num = parseInt(valor);

        // Menor que 1
        if (num < 1) {
            mostrarError("El mínimo permitido es 1 comensal.");
            return false;
        }

        // Mayor que sillas disponibles
        if (num > maxSillas) {
            mostrarError("La mesa no admite más de " + maxSillas + " comensales.");
            return false;
        }

        return true;
    }

    // Eventos para validar "en vivo" 
    inputComensales.addEventListener('blur', validarCampo);
    inputComensales.addEventListener('input', limpiarError);

    // --- IMPORTANTE: Usamos el evento SUBMIT del formulario ---
    // Esto captura tanto el clic en el botón como la tecla ENTER
    form.addEventListener('submit', function (e) {

        // 1. SIEMPRE prevenimos el envío automático por defecto
        e.preventDefault();

        // 2. Ejecutamos validación
        if (validarCampo()) {
            const numComensales = inputComensales.value;

            // 3. Mostramos SweetAlert
            const swalWithBootstrapButtons = Swal.mixin({
                customClass: {
                    confirmButton: "btn btn-success ms-2",
                    cancelButton: "btn btn-secondary"
                },
                buttonsStyling: false
            });

            swalWithBootstrapButtons.fire({
                title: "¿Confirmar asignación?",
                text: `¿Deseas asignar esta mesa para ${numComensales} comensales?`,
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Sí, asignar",
                cancelButtonText: "Cancelar",
                reverseButtons: true,
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // 4. Si el usuario confirma, enviamos MANUALMENTE el formulario
                    // form.submit() invoca el envío nativo del DOM, saltando este listener
                    form.submit();
                }
            });
        }
        // Si no valida, no hacemos nada (el preventDefault ya paró el envío)
    });

});
