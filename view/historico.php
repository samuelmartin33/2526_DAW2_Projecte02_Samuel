<?php
// Fíjate que ahora coincide con el nombre real de tu archivo en la carpeta 'proc'
require_once '../proc/historico_proc.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico y Estadísticas - GMS</title>
    
    <link rel="stylesheet" href="../css/historico.css"> 
    <link rel="icon" type="image/png" href="../img/icono.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
</head>
<body>

<nav class="main-header">
    <div class="header-logo">
        <a href="../index.php">
            <img src="../img/basic_logo_blanco.png" alt="Logo GMS">
        </a>
        <div class="logo-text">
            <span class="gms-title">CASA GMS</span>
        </div>
    </div>

    <div class="header-greeting">
        <?= $saludo ?> <span class="username-tag"><?= htmlspecialchars($username) ?></span>
    </div>

    <div class="header-menu">
        <a href="../index.php" class="nav-link">
            <i class="fa-solid fa-house"></i> Inicio
        </a>
        <?php if ($rol == 2): ?>
            <a href="admin_panel.php" class="nav-link">
                <i class="fa-solid fa-gear"></i> Admin
            </a>
        <?php endif; ?>
    </div>

    <form method="post" action="../proc/logout.php">
        <button type="submit" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
        </button>
    </form>
</nav>

<div class="main-content p-4">

    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="metric-card">
                <i class="fas fa-bookmark metric-icon"></i>
                <h2 class="metric-number"><?= $stats_general['total_ocupaciones'] ?></h2>
                <p class="metric-label">Ocupaciones Totales</p>
                <div class="metric-trend">
                    <?php if ($tendencia_porcentaje > 0): ?>
                        <i class="fas fa-arrow-up"></i>
                        <span>+<?= abs($tendencia_porcentaje) ?>%</span>
                    <?php elseif ($tendencia_porcentaje < 0): ?>
                        <i class="fas fa-arrow-down"></i>
                        <span><?= $tendencia_porcentaje ?>%</span>
                    <?php else: ?>
                        <i class="fas fa-minus"></i>
                        <span>0%</span>
                    <?php endif; ?>
                    <span class="ms-1">vs mes ant.</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="metric-card success">
                <i class="fas fa-users metric-icon"></i>
                <h2 class="metric-number"><?= $stats_general['total_comensales'] ?></h2>
                <p class="metric-label">Comensales Totales</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="metric-card danger">
                <i class="fas fa-calendar-day metric-icon"></i>
                <h2 class="metric-number"><?= $stats_general['ocupaciones_hoy'] ?? 0 ?></h2>
                <p class="metric-label">Ocupaciones Hoy</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="metric-card info">
                <i class="fas fa-clock metric-icon"></i>
                <h2 class="metric-number"><?= $avg_minutos ?></h2>
                <p class="metric-label">Minutos Promedio</p>
            </div>
        </div>
    </div>
<div class="row mb-4">
        <div class="col-12">
            <div class="glass-card p-4">
                <div class="section-header">
                    <h5 class="section-title mb-0">
                        <i class="fas fa-history text-primary"></i>
                        Histórico de Ocupaciones
                    </h5>
                </div>
                
                <form method="get" action="historico.php" class="filter-form-inline">
                    <fieldset>
                        <legend class="visually-hidden">Filtros de Búsqueda</legend>
                        <div class="row g-2"> <div class="col-md-3 col-6">
                                <label for="sala" class="form-label-sm">Sala</label>
                                <select name="sala" id="sala" class="form-select form-select-sm">
                                    <option value="">Todas</option>
                                    <?php foreach ($salas as $s): ?>
                                        <option value="<?= $s['id'] ?>" <?= $filtro_sala == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 col-6">
                                <label for="camarero" class="form-label-sm">Camarero</label>
                                <select name="camarero" id="camarero" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <?php foreach ($camareros_filtro as $u): ?>
                                        <option value="<?= $u['id'] ?>" <?= $filtro_camarero == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['username']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 col-4">
                                <label for="ano" class="form-label-sm">Año</label>
                                <select name="ano" id="ano" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <?php foreach ($anos as $a): ?>
                                        <option value="<?= $a['ano'] ?>" <?= $filtro_ano == $a['ano'] ? 'selected' : '' ?>><?= $a['ano'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 col-4">
                                <label for="mes" class="form-label-sm">Mes</label>
                                <select name="mes" id="mes" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <?php foreach ($meses as $num => $nombre): ?>
                                        <option value="<?= $num ?>" <?= $filtro_mes == $num ? 'selected' : '' ?>><?= $nombre ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1 col-4">
                                <label for="dia" class="form-label-sm">Día</label>
                                <select name="dia" id="dia" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <?php for ($d = 1; $d <= 31; $d++): ?>
                                        <option value="<?= $d ?>" <?= $filtro_dia == $d ? 'selected' : '' ?>><?= $d ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-1 col-12 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Filtrar</button>
                            </div>
                        </div>
                    </fieldset>
                </form>
                
                <div class="table-responsive mt-3" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-dark" style="position: sticky; top: 0;">
                            <tr>
                            
                                <th>Camarero</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ocupaciones_tabla)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No se encontraron registros con esos filtros.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ocupaciones_tabla as $o): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($o['camarero']) ?></td>
                                    </tr>
                                <?php endforeach; // Fin del bucle de resultados ?>
                            <?php endif; // Fin del if (empty...) ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
    <div class="row mb-4">
        
        <div class="col-lg-6 mb-4">
            <div class="glass-card p-4">
                <h5 class="section-title mb-0"><i class="fas fa-medal text-warning"></i> Top Camareros</h5>
                <div class="bar-chart-container">
                    <?php foreach ($top_camareros as $item): ?>
                        <?php $percent = ($item['total_mesas'] / $max_camareros) * 100; ?>
                        <div class="bar-row">
                            <span class="bar-label"><?= htmlspecialchars($item['username']) ?></span>
                            <div class="bar-wrap">
                                <div class="bar" style="width: <?= $percent ?>%;"></div>
                            </div>
                            <span class="bar-value"><?= $item['total_mesas'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="glass-card p-4">
                <h5 class="section-title mb-0"><i class="fas fa-door-open text-danger"></i> Salas más Ocupadas</h5>
                <div class="bar-chart-container">
                    <?php foreach ($top_salas as $item): ?>
                        <?php $percent = ($item['total_ocupaciones'] / $max_salas) * 100; ?>
                        <div class="bar-row">
                            <span class="bar-label"><?= htmlspecialchars($item['nombre']) ?></span>
                            <div class="bar-wrap">
                                <div class="bar" style="width: <?= $percent ?>%;"></div>
                            </div>
                            <span class="bar-value"><?= $item['total_ocupaciones'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="glass-card p-4">
                <h5 class="section-title mb-0"><i class="fas fa-clock text-info"></i> Ocupaciones por Hora</h5>
                <div class="bar-chart-container-scroll">
                    <?php for ($i = 0; $i < 24; $i++): ?>
                        <?php $percent = ($horas_data[$i] / $max_horas) * 100; ?>
                        <div class="bar-row">
                            <span class="bar-label"><?= $i ?>:00h</span> <div class="bar-wrap">
                                <div class="bar" style="width: <?= $percent ?>%;"></div>
                            </div>
                            <span class="bar-value"><?= $horas_data[$i] ?></span> </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="glass-card p-4">
                <h5 class="section-title mb-0"><i class="fas fa-calendar-week text-success"></i> Ocupaciones por Día</h5>
                <div class="bar-chart-container">
                    <?php foreach ($dias_labels as $index => $label): ?>
                        <?php $percent = ($dias_data[$index] / $max_dias) * 100; ?>
                        <div class="bar-row">
                            <span class="bar-label"><?= $label ?></span> <div class="bar-wrap">
                                <div class="bar" style="width: <?= $percent ?>%;"></div>
                            </div>
                            <span class="bar-value"><?= $dias_data[$index] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

    
</div>

</body>
</html>