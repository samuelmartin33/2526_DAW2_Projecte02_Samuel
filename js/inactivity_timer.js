(function () {
    let inactivityTimer;

    // 5 minutos en milisegundos
    const timeoutDuration = 2 * 60 * 1000;
    // const timeoutDuration = 10000; // --- Descomenta esta línea para probar (10 segundos)

    // 1. Determinar la ruta correcta al script de logout
    // (Según tu estructura de carpetas)
    // 1. Determinar la ruta correcta al script de logout
    let logoutPath = '../proc/logout.php'; // Default for files in view/
    const path = window.location.pathname;

    if (path.includes('/view/')) {
        logoutPath = '../proc/logout.php';
    } else if (path.includes('index.php')) {
        logoutPath = 'proc/logout.php';
    }



    /**
     * Muestra el popup de SweetAlert
     */
    function showInactivityPopup() {

        // si el usuario mueve el ratón para hacer clic.
        removeActivityListeners();

        Swal.fire({
            title: '¿Sigues ahí?',
            text: '',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '¡Sigo aquí!',
            cancelButtonText: 'Cerrar Sesión',
            reverseButtons: true,
            allowOutsideClick: false, // No permite cerrar clicando fuera
            allowEscapeKey: false, // No permite cerrar con la tecla ESC


            timer: 900000000,
            timerProgressBar: true,

        }).then((result) => {
            if (result.isConfirmed) {
                // Si el usuario hace clic en "Sigo aquí"
                // Se resetea el timer Y se vuelven a añadir los listeners.
                resetTimer();
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // Si hace clic en "Cerrar Sesión"
                window.location.href = logoutPath;
            }
        });
    }

    /**
     * Resetea el temporizador de inactividad
     */
    function resetTimer() {
        clearTimeout(inactivityTimer);
        // Oculta cualquier alerta que pudiera estar abierta
        // Swal.close();  <-- ESTA LÍNEA ES LA CULPABLE. ELIMINADA/COMENTADA.

        addActivityListeners();

        // Vuelve a empezar la cuenta de 5 minutos
        inactivityTimer = setTimeout(showInactivityPopup, timeoutDuration);
    }

    // --- NUEVO: Funciones para añadir y quitar listeners ---
    function addActivityListeners() {
        window.addEventListener('mousemove', resetTimer, { passive: true });
        window.addEventListener('keydown', resetTimer, { passive: true });
        window.addEventListener('click', resetTimer, { passive: true });
        window.addEventListener('scroll', resetTimer, { passive: true });
    }

    function removeActivityListeners() {
        window.removeEventListener('mousemove', resetTimer, { passive: true });
        window.removeEventListener('keydown', resetTimer, { passive: true });
        window.removeEventListener('click', resetTimer, { passive: true });
        window.removeEventListener('scroll', resetTimer, { passive: true });
    }

    // Iniciar el temporizador por primera vez (esto también añade los listeners)
    resetTimer();

})(); // Fin de la función autoejecutable