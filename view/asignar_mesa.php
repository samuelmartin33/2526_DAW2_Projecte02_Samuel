<?php
session_start();
require_once '../bbdd/conexion.php';

if (!isset($_SESSION['loginok'])) {
    header("Location: login.php");
    exit();
}

$id_mesa = $_GET['id_mesa'] ?? null;
$id_sala = $_GET['id_sala'] ?? null;

if (!$id_mesa || !$id_sala) {
    header("Location: ver_sala.php");
    exit();
}

// Obtener información de la mesa
$stmt = $conn->prepare("SELECT * FROM mesas WHERE id = ?");
$stmt->execute([$id_mesa]);
$mesa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mesa || $mesa['estado'] != 1) {
    // Si no existe o no está libre, volver
    header("Location: ver_sala.php?id_sala=" . $id_sala . "&error=no_disponible");
    exit();
}

// Obtener info de la sala para el título
$stmt_sala = $conn->prepare("SELECT nombre FROM salas WHERE id = ?");
$stmt_sala->execute([$id_sala]);
$sala = $stmt_sala->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar <?= htmlspecialchars($mesa['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/panel_principal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .card-asignar {
            width: 100%;
            max-width: 500px;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            background: white;
            border-top: 5px solid #0d6efd;
        }
    </style>
</head>
<body>

    <?php require_once '../header.php'; ?>

    <div class="main-container">
        <div class="card-asignar">
            <h2 class="text-center mb-4"><i class="fa-solid fa-chair text-primary"></i> Asignar Mesa</h2>
            
            <div class="mb-3 text-center">
                <h4><?= htmlspecialchars($mesa['nombre']) ?> <small class="text-muted">(<?= htmlspecialchars($sala['nombre']) ?>)</small></h4>
                <p>Capacidad máxima: <strong><?= $mesa['sillas'] ?> personas</strong></p>
            </div>

            <form action="../proc/ocupar_mesa.php" method="POST">
                <input type="hidden" name="id_mesa" value="<?= $id_mesa ?>">
                <input type="hidden" name="id_sala" value="<?= $id_sala ?>">

                <div class="mb-4">
                    <label for="num_comensales" class="form-label">Número de comensales:</label>
                    <input type="number" 
                           class="form-control form-control-lg text-center" 
                           id="num_comensales" 
                           name="num_comensales" 
                           min="1" 
                           max="<?= $mesa['sillas'] ?>" 
                           required 
                           autofocus>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-check"></i> Confirmar Ocupación
                    </button>
                    <a href="ver_sala.php?id_sala=<?= $id_sala ?>" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
