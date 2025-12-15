<?php
// proc/index_proc.php
session_start();

// Conexión usando __DIR__ para que sea a prueba de fallos de ruta
require_once __DIR__ . '/../BBDD/conexion.php';

// Verificación de sesión
if (!isset($_SESSION['loginok']) || $_SESSION['loginok'] !== true) {
    // Redirige al login (ruta desde la raíz)
    header("Location: ./view/login.php");
    exit();
}

$username = htmlspecialchars($_SESSION['username']);
$nombre = htmlspecialchars($_SESSION['nombre'] ?? $username);
$rol = $_SESSION['rol'] ?? 1;

// Saludo dinámico
$hora = date('H');
if ($hora >= 6 && $hora < 12) { $saludo = "Buenos días"; }
elseif ($hora >= 12 && $hora < 20) { $saludo = "Buenas tardes"; }
else { $saludo = "Buenas noches"; }

// Gestión del mensaje de bienvenida (SweetAlert)
if (isset($_SESSION['show_welcome_message']) && $_SESSION['show_welcome_message'] === true) {
    $welcome_data_flag = "true";
    $welcome_data_name = $nombre;
    unset($_SESSION['show_welcome_message']);
}

// --- CONSULTAS ---
try {
    // 1. Obtener salas y ocupación de mesas
    $sql = "
        SELECT 
            s.id AS id_sala,
            s.nombre AS sala_nombre,
            s.imagen,
            COUNT(m.id) AS total_mesas,
            SUM(CASE WHEN m.estado = 2 THEN 1 ELSE 0 END) AS mesas_ocupadas
        FROM salas s
        LEFT JOIN mesas m ON s.id = m.id_sala
        GROUP BY s.id
        ORDER BY s.nombre ASC
    ";
    $stmt = $conn->query($sql);
    $salas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ocupacion_salas = [];
    $total_mesas = 0;
    $mesas_ocupadas = 0;
    $total_sillas = 0;
    $sillas_ocupadas = 0;

    foreach ($salas as $s) {
        $total_mesas += $s['total_mesas'];
        $mesas_ocupadas += $s['mesas_ocupadas'];
        
        $ocupacion_pct = $s['total_mesas'] > 0 ? round(($s['mesas_ocupadas'] / $s['total_mesas']) * 100) : 0;

        // 2. Obtener datos de sillas
        $querySillas = $conn->prepare("
            SELECT 
                SUM(sillas) AS total_sillas,
                SUM(CASE WHEN estado = 2 THEN sillas ELSE 0 END) AS sillas_ocupadas
            FROM mesas WHERE id_sala = :id
        ");
        $querySillas->execute([':id' => $s['id_sala']]);
        $sillas = $querySillas->fetch(PDO::FETCH_ASSOC);

        $total_sillas += intval($sillas['total_sillas']);
        $sillas_ocupadas += intval($sillas['sillas_ocupadas']);

        // --- GESTIÓN DE LA IMAGEN ---
        if (!empty($s['imagen'])) {
            // Si tiene imagen asignada en BBDD, la usamos directamente (asumimos que existe)
            $ruta_imagen = $s['imagen'];
        } else {
            // Fallback: Lógica antigua (por nombre) o default
            $nombre_simple = strtolower(str_replace(' ', '', $s['sala_nombre']));
            $ruta_posible = "img/" . $nombre_simple . ".png";
            
            if (file_exists(__DIR__ . '/../' . $ruta_posible)) {
                $ruta_imagen = $ruta_posible;
            } else {
                $ruta_imagen = "img/fondo_panel_principal.png"; // Default general
            }
        }

        // Guardamos todo en el array
        $ocupacion_salas[] = [
            'id' => $s['id_sala'],
            'sala' => $s['sala_nombre'],
            'imagen' => $ruta_imagen, 
            'ocupacion_pct' => $ocupacion_pct,
            'mesas_ocupadas' => $s['mesas_ocupadas'],
            'total_mesas' => $s['total_mesas']
        ];
    }

    $stats = [
        'total_mesas' => $total_mesas,
        'mesas_ocupadas' => $mesas_ocupadas,
        'mesas_libres' => $total_mesas - $mesas_ocupadas,
        'total_sillas' => $total_sillas,
        'sillas_ocupadas' => $sillas_ocupadas,
        'sillas_libres' => $total_sillas - $sillas_ocupadas,
    ];

    // Ordenar por % de ocupación
    usort($ocupacion_salas, fn($a, $b) => $b['ocupacion_pct'] <=> $a['ocupacion_pct']);

} catch (PDOException $e) {
    die("Error en BBDD: " . $e->getMessage());
}
?>