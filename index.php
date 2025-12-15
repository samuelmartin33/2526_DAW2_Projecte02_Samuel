<?php
include_once 'proc/index_proc.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - Casa GMS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/panel_principal.css">
    <link rel="icon" type="image/png" href="img/icono.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body>
    
    <nav class="main-header">
        <div class="header-logo">
            <img src="img/basic_logo_blanco.png" alt="Logo GMS">
            <div class="logo-text">
                <span class="gms-title">CASA GMS</span>
            </div>
        </div>

        <div class="header-greeting">
            <?= $saludo ?> <span class="username-tag"><?= $username ?></span>
        </div>

        <div class="header-menu">
            <a href="./index.php" class="nav-link">
                <i class="fa-solid fa-house"></i> Inicio
            </a>
            <a href="./view/historico.php" class="nav-link">
                <i class="fa-solid fa-chart-bar"></i> Histórico
            </a>
            <?php if ($rol == 2): ?>
                <a href="./view/admin_panel.php" class="nav-link">
                    <i class="fa-solid fa-gear"></i> Admin
                </a>
            <?php endif; ?>
        </div>

        <form method="post" action="proc/logout.php">
            <button type="submit" class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
            </button>
        </form>
    </nav>

    <div class="container">
        
        <h1 class="dashboard-title">Resumen de Ocupación Hoy</h1>

        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-value"><?= $stats['mesas_libres'] ?> / <?= $stats['total_mesas'] ?></div>
                <div class="stat-label">Mesas Disponibles</div>
                <i class="stat-icon fa-solid fa-check-circle"></i>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-value"><?= $stats['mesas_ocupadas'] ?></div>
                <div class="stat-label">Mesas Ocupadas</div>
                <i class="stat-icon fa-solid fa-users"></i>
            </div>

            <div class="stat-card success">
                <div class="stat-value"><?= $stats['sillas_ocupadas'] ?> / <?= $stats['total_sillas'] ?></div>
                <div class="stat-label">Sillas Ocupadas (Total)</div>
                <i class="stat-icon fa-solid fa-user-group"></i>
            </div>
        </div>
        
        <h2 class="section-title">Salas del Restaurante (Click para ver mesas)</h2>
        
        <div class="salas-grid">
            <?php foreach ($ocupacion_salas as $sala): ?>
                <?php
                    // Lógica de colores para la barra de progreso
                    $bar_color = '#27ae60'; // Verde
                    if ($sala['ocupacion_pct'] >= 75) {
                        $bar_color = '#e74c3c'; // Rojo
                    } elseif ($sala['ocupacion_pct'] > 0) {
                        $bar_color = '#f39c12'; // Naranja
                    }
                ?>
                
                <a href="./view/ver_sala.php?id_sala=<?= $sala['id'] ?>" class="sala-card-link">
                    
                    <div class="sala-card" 
                         style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('<?= $sala['imagen'] ?>?v=<?= time() ?>');">
                        
                        <h3 class="sala-name"><?= htmlspecialchars($sala['sala']) ?></h3>
                        
                        <div class="sala-occupancy">
                            <?php if ($sala['mesas_ocupadas'] == 0): ?>
                                TODAS LIBRES (<?= $sala['total_mesas'] ?> Mesas)
                            <?php else: ?>
                                <?= $sala['mesas_ocupadas'] ?> / <?= $sala['total_mesas'] ?> Mesas Ocupadas
                            <?php endif; ?>
                        </div>
                        
                        <div class="progress-bar-container">
                            <div class="progress-bar" 
                                 style="width: <?= $sala['ocupacion_pct'] ?>%; 
                                        background-color: <?= $bar_color ?>;">
                            </div>
                        </div>
                        
                        <div class="percentage"><?= $sala['ocupacion_pct'] ?>% Ocupación</div>
                    
                    </div> </a> <?php endforeach; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/mensaje_inicio.js"></script>
    <script src="js/inactivity_timer.js"></script>

</body>
</html>