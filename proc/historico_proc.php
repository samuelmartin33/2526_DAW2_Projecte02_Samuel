<?php
// Inicia o reanuda la sesión del usuario. Es necesario para acceder a $_SESSION.
session_start();

// Requiere el archivo de conexión a la BBDD. 
// __DIR__ . '/../CONEXION/conexion.php' es una ruta absoluta que significa:
// "Desde el directorio de ESTE fichero (historico.php), sube un nivel y entra en /CONEXION/ y carga conexion.php".
require_once __DIR__ . '/../BBDD/conexion.php';
// Establece la zona horaria por defecto a "Europa/Madrid".
// Esto es CRÍTICO para que funciones como CURDATE() y NOW() en SQL usen la hora correcta.
date_default_timezone_set('Europe/Madrid');

// --- CONTROL DE SESIÓN ---
// Comprueba si la variable de sesión 'loginok' NO existe O NO es estrictamente true.
if (!isset($_SESSION['loginok']) || $_SESSION['loginok'] !== true) {
    // Si el usuario no está logueado, lo redirige a la página de login.
    header("Location: login.php"); 
    // Detiene la ejecución del script para que no se cargue nada más.
    exit(); 
}

// --- RECUPERACIÓN DE DATOS DE SESIÓN ---
// Guarda el username del usuario logueado.
$username = $_SESSION['username'];
// Guarda el rol. Usa el "operador de fusión de null" (??): si $_SESSION['rol'] existe, usa ese valor; si no, usa 1 (camarero) por defecto.
$rol = $_SESSION['rol'] ?? 1; 

// --- LÓGICA DEL HEADER (Saludo dinámico) ---
// Obtiene la hora actual en formato 24h (ej: "08", "14", "22").
$hora = date('H');
// Define un saludo personalizado según la franja horaria.
if ($hora >= 6 && $hora < 12) {
    $saludo = "Buenos días";
} elseif ($hora >= 12 && $hora < 20) {
    $saludo = "Buenas tardes";
} else {
    $saludo = "Buenas noches";
}
// --- FIN LÓGICA HEADER ---


// --- VARIABLES DE FILTRO (Para la tabla) ---
// Recoge los valores de la URL (método GET). 
// Si el parámetro (ej: ?sala=3) existe, usa su valor. Si no, usa un string vacío ''.
$filtro_sala = $_GET['sala'] ?? '';
$filtro_mesa = $_GET['mesa'] ?? ''; // (Nota: Este filtro no se usa en la consulta SQL de abajo)
$filtro_camarero = $_GET['camarero'] ?? ''; 
$filtro_mes = $_GET['mes'] ?? '';           
$filtro_dia = $_GET['dia'] ?? '';           
$filtro_ano = $_GET['ano'] ?? '';

// --- BLOQUE PRINCIPAL DE CONSULTAS A LA BBDD ---
// Se usa un bloque try-catch para capturar cualquier error de SQL (PDOException).
try {
    
    // --- 1. KPIs GENERALES (Tarjetas superiores) ---
    // Esta consulta calcula las 4 estadísticas principales en una sola llamada.
    $sql_general = "SELECT 
        COUNT(*) AS total_ocupaciones, /* Cuenta el total de registros de ocupaciones */
        SUM(num_comensales) AS total_comensales, /* Suma todos los comensales históricos */
        AVG(duracion_segundos) AS avg_duracion_segundos, /* Calcula la media de duración en segundos */
        
        /* Una subconsulta para contar solo las ocupaciones de HOY (CURDATE() = Fecha actual) */
        (SELECT COUNT(*) FROM ocupaciones WHERE DATE(inicio_ocupacion) = CURDATE()) AS ocupaciones_hoy
        FROM ocupaciones
        /* IMPORTANTE: Solo cuenta ocupaciones que ya han terminado (tienen fecha de fin) */
        WHERE final_ocupacion IS NOT NULL";
    
    // Ejecuta la consulta y obtiene la única fila de resultados.
    $stats_general = $conn->query($sql_general)->fetch(PDO::FETCH_ASSOC);

    // Convierte la duración promedio de segundos a minutos, redondeado a 1 decimal.
    // Comprueba si es > 0 para evitar errores si no hay datos.
    $avg_minutos = ($stats_general['avg_duracion_segundos'] > 0) ? round($stats_general['avg_duracion_segundos'] / 60, 1) : 0;
    
    // --- 2. Comparativa Mes Actual vs Anterior (para la tarjeta de "Tendencia") ---
    // Consulta para comparar el rendimiento del mes actual con el mes anterior.
    $sql_comparativa = "SELECT 
        /* Cuenta 1 por cada registro de ESTE mes y ESTE año */
        SUM(CASE WHEN YEAR(inicio_ocupacion) = YEAR(CURDATE()) AND MONTH(inicio_ocupacion) = MONTH(CURDATE()) THEN 1 ELSE 0 END) AS mes_actual,
        
        /* Cuenta 1 por cada registro del MES ANTERIOR (CURDATE() - INTERVAL 1 MONTH) */
        SUM(CASE WHEN YEAR(inicio_ocupacion) = YEAR(CURDATE() - INTERVAL 1 MONTH) AND MONTH(inicio_ocupacion) = MONTH(CURDATE() - INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS mes_anterior
        FROM ocupaciones";
    $comparativa = $conn->query($sql_comparativa)->fetch(PDO::FETCH_ASSOC);

    // Calcula el porcentaje de tendencia.
    $tendencia_porcentaje = 0;
    // IMPORTANTE: Comprueba si 'mes_anterior' > 0 para evitar un error de "División por cero".
    if ($comparativa['mes_anterior'] > 0) {
        $tendencia_porcentaje = round((($comparativa['mes_actual'] - $comparativa['mes_anterior']) / $comparativa['mes_anterior']) * 100, 1);
    }

    // --- 3. DATOS GRÁFICO: Top 5 Camareros ---
    // Obtiene los 5 camareros con más mesas asignadas.
    $sql_top_camareros = "SELECT u.username, COUNT(o.id) AS total_mesas
        FROM ocupaciones o
        /* Une con la tabla 'users' para obtener el nombre (username) a partir del 'id_camarero' */
        JOIN users u ON o.id_camarero = u.id 
        GROUP BY o.id_camarero /* Agrupa las cuentas por camarero */
        ORDER BY total_mesas DESC /* Ordena de mayor a menor */
        LIMIT 5"; // Coge solo los 5 primeros
    $top_camareros = $conn->query($sql_top_camareros)->fetchAll(PDO::FETCH_ASSOC);
    
    // Para el gráfico de barras HTML, necesitamos saber cuál es el valor MÁXIMO (el 100%).
    // array_column() saca solo los números (ej: [50, 45, 30, 20, 10])
    // max() obtiene el más alto (ej: 50).
    // Si el array está vacío, usa 1 para evitar división por cero.
    $max_camareros = !empty($top_camareros) ? max(array_column($top_camareros, 'total_mesas')) : 1;
    
    // --- 4. DATOS GRÁFICO: Top 5 Salas ---
    // Misma lógica que el Top Camareros, pero agrupando por sala.
    $sql_top_salas = "SELECT s.nombre, COUNT(o.id) AS total_ocupaciones
        FROM ocupaciones o
        JOIN salas s ON o.id_sala = s.id /* Une con 'salas' para obtener el nombre */
        GROUP BY o.id_sala
        ORDER BY total_ocupaciones DESC
        LIMIT 5";
    $top_salas = $conn->query($sql_top_salas)->fetchAll(PDO::FETCH_ASSOC);
    // Saca el valor máximo de ocupaciones de sala.
    $max_salas = !empty($top_salas) ? max(array_column($top_salas, 'total_ocupaciones')) : 1;

    // --- 5. DATOS GRÁFICO: Horas Pico ---
    // Cuenta cuántas ocupaciones se iniciaron en cada hora del día (0-23).
    $sql_horas_pico = "SELECT HOUR(inicio_ocupacion) AS hora, COUNT(*) AS ocupaciones
        FROM ocupaciones
        GROUP BY HOUR(inicio_ocupacion) /* Agrupa por la HORA */
        ORDER BY hora"; // Ordena por hora
    $horas_pico = $conn->query($sql_horas_pico)->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepara un array "plantilla" con 24 horas, todas a 0.
    // Ej: [0 => 0, 1 => 0, 2 => 0, ..., 23 => 0]
    $horas_data = array_fill(0, 24, 0); 
    // Rellena el array plantilla con los datos de la BBDD.
    // Si hubo 10 ocupaciones a las 14h, $horas_data[14] pasará a valer 10.
    foreach ($horas_pico as $hora) {
        $horas_data[$hora['hora']] = $hora['ocupaciones']; 
    }
    // Saca el valor máximo de la hora pico (el 100% de la barra).
    $max_horas = !empty($horas_data) ? max($horas_data) : 1;
    
    // --- 6. DATOS GRÁFICO: Días de la Semana ---
    // Misma lógica que las horas, pero con días.
    // WEEKDAY() en MySQL devuelve 0=Lunes, 1=Martes, ..., 6=Domingo.
    $sql_dias_semana = "SELECT WEEKDAY(inicio_ocupacion) AS dia_num, COUNT(*) AS ocupaciones
        FROM ocupaciones
        GROUP BY dia_num 
        ORDER BY dia_num";
    $dias_semana = $conn->query($sql_dias_semana)->fetchAll(PDO::FETCH_ASSOC);
    
    // Array para las etiquetas del gráfico.
    $dias_labels = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    // Array plantilla con 7 días, todos a 0.
    $dias_data = array_fill(0, 7, 0);
    // Rellena el array plantilla con los datos de la BBDD.
    foreach ($dias_semana as $dia) {
        $dias_data[$dia['dia_num']] = $dia['ocupaciones'];
    }
    // Saca el valor máximo del día más ocupado.
    $max_dias = !empty($dias_data) ? max($dias_data) : 1;


    // --- 7. DATOS PARA LA TABLA DE HISTÓRICO ---
    
    // Primero, obtenemos los listados para rellenar los <select> del formulario de filtros.
    $camareros_filtro = $conn->query("SELECT id, username FROM users WHERE rol = 1 ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
    // Obtiene solo los años distintos que existen en la tabla (ej: [2025, 2024]).
    // Un array PHP para los nombres de los meses.
    // --- CONSTRUCCIÓN DE LA CONSULTA DINÁMICA PARA LA TABLA ---
    // Esta es la consulta base. Obtiene todos los datos y los nombres de las tablas relacionadas.
    $sql_tabla = "
        SELECT o.*, s.nombre AS sala_nombre, m.nombre AS mesa_nombre, u.username AS camarero
        FROM ocupaciones o
        JOIN salas s ON o.id_sala = s.id
        JOIN mesas m ON o.id_mesa = m.id
        JOIN users u ON o.id_camarero = u.id
        WHERE 1=1"; // "WHERE 1=1" permite añadir siempre "AND" sin preocuparse de si es el primer filtro.
    
    // Array para los parámetros de la consulta preparada (evita Inyección SQL).
    $params_tabla = []; 

    // --- Añadir filtros dinámicamente ---
    // Comprueba si la variable de filtro (de la URL) NO está vacía.
    if ($filtro_sala !== '') {
        $sql_tabla .= " AND o.id_sala = :sala"; // Añade la condición SQL (con un marcador :sala).
        $params_tabla[':sala'] = $filtro_sala; // Añade el valor al array de parámetros.
    }
    // Repite la lógica para los demás filtros.
    if ($filtro_camarero !== '') {
        $sql_tabla .= " AND o.id_camarero = :camarero";
        $params_tabla[':camarero'] = $filtro_camarero;
    }
    if ($filtro_ano !== '') {
        $sql_tabla .= " AND YEAR(o.inicio_ocupacion) = :ano";
        $params_tabla[':ano'] = $filtro_ano;
    }
    if ($filtro_mes !== '') {
        $sql_tabla .= " AND MONTH(o.inicio_ocupacion) = :mes";
        $params_tabla[':mes'] = $filtro_mes;
    }
    if ($filtro_dia !== '') {
        $sql_tabla .= " AND DAY(o.inicio_ocupacion) = :dia";
        $params_tabla[':dia'] = $filtro_dia;
    }
    
    // Añade el orden y un límite (para no sobrecargar la página).
    $sql_tabla .= " ORDER BY o.inicio_ocupacion DESC LIMIT 200"; 
    
    // Prepara la consulta SQL (ya construida dinámicamente).
    $stmt_tabla = $conn->prepare($sql_tabla); 
    // Ejecuta la consulta, pasando el array de parámetros. PDO se encarga de la seguridad.
    $stmt_tabla->execute($params_tabla); 
    // Obtiene todos los resultados filtrados.
    $ocupaciones_tabla = $stmt_tabla->fetchAll(PDO::FETCH_ASSOC); 


} catch(PDOException $e) { // Si cualquier consulta del bloque 'try' falla...
    // ...detiene el script y muestra el error de la BBDD.
    die("Error de conexión o consulta: " . $e->getMessage());
}
