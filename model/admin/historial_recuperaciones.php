<?php
session_start();
require_once("../../db/conexion.php");

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../../login.php");
  exit();
}

$db = new Database();
$con = $db->conectar();

// VARIABLES DE FILTRO

$filtro_email = $_GET['buscar'] ?? '';
$fecha_desde = $_GET['desde'] ?? '';
$fecha_hasta = $_GET['hasta'] ?? '';
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$registros_por_pagina = 10;

// CONDICIONES DINÁMICAS

$where = [];
$params = [];

if (!empty($filtro_email)) {
  $where[] = "(u.email LIKE ? OR u.nombre_usuario LIKE ?)";
  $params[] = "%$filtro_email%";
  $params[] = "%$filtro_email%";
}
if (!empty($fecha_desde)) {
  $where[] = "DATE(prl.fecha) >= ?";
  $params[] = $fecha_desde;
}
if (!empty($fecha_hasta)) {
  $where[] = "DATE(prl.fecha) <= ?";
  $params[] = $fecha_hasta;
}

$condiciones = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// PAGINACIÓN

$total_query = $con->prepare("
  SELECT COUNT(*) FROM password_reset_logs prl
  INNER JOIN usuarios u ON prl.id_usuario = u.id_usuario
  $condiciones
");
$total_query->execute($params);
$total_registros = $total_query->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);
$offset = ($pagina - 1) * $registros_por_pagina;

// CONSULTA PRINCIPAL

$query = $con->prepare("
  SELECT 
    prl.id,
    u.nombre_usuario AS usuario,
    u.email,
    prl.ip,
    prl.ubicacion,
    prl.success,
    prl.fecha,
    prt.user_agent
  FROM password_reset_logs prl
  LEFT JOIN usuarios u ON prl.id_usuario = u.id_usuario
  LEFT JOIN password_reset_tokens prt ON prt.id_usuario = u.id_usuario
  $condiciones
  ORDER BY prl.fecha DESC
  LIMIT $registros_por_pagina OFFSET $offset
");
$query->execute($params);
$historial = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Historial de Recuperaciones - Admin COD</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">
  <div class="container py-5">
    <h1 class="text-warning text-center mb-4">Historial de Recuperación de Contraseñas</h1>

    <!-- FILTROS -->
    <form method="get" class="row mb-4 g-2">
      <div class="col-md-3">
        <input type="text" name="buscar" value="<?= htmlspecialchars($filtro_email) ?>" class="form-control" placeholder="Buscar por email o usuario...">
      </div>

      <div class="col-md-3">
        <input type="date" name="desde" value="<?= htmlspecialchars($fecha_desde) ?>" class="form-control">
      </div>

      <div class="col-md-3">
        <input type="date" name="hasta" value="<?= htmlspecialchars($fecha_hasta) ?>" class="form-control">
      </div>

      <div class="col-md-3">
        <button type="submit" class="btn btn-warning w-100">Filtrar</button>
      </div>
    </form>

    <!-- TABLA -->
    <table class="table table-dark table-striped text-center align-middle">
      <thead class="table-warning text-dark">
        <tr>
          <th>ID</th>
          <th>Usuario</th>
          <th>Email</th>
          <th>IP</th>
          <th>Ubicación</th>
          <th>Navegador</th>
          <th>Resultado</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($historial)): ?>
          <tr>
            <td colspan="8" class="text-muted">No se encontraron registros.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($historial as $h): ?>
            <tr>
              <td><?= htmlspecialchars($h['id']) ?></td>
              <td><?= htmlspecialchars($h['usuario']) ?></td>
              <td><?= htmlspecialchars($h['email']) ?></td>
              <td><?= htmlspecialchars($h['ip']) ?></td>
              <td><?= htmlspecialchars($h['ubicacion']) ?></td>
              <td><?= htmlspecialchars(substr($h['user_agent'], 0, 30)) ?>...</td>
              <td>
                <?php if ($h['success']): ?>
                  <span class="badge bg-success">Éxito</span>
                <?php else: ?>
                  <span class="badge bg-danger">Fallido</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($h['fecha']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- PAGINACIÓN -->
    <?php if ($total_paginas > 1): ?>
      <nav aria-label="Paginación" class="mt-4">
        <ul class="pagination justify-content-center">
          <li class="page-item <?= ($pagina == 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=1&buscar=<?= urlencode($filtro_email) ?>&desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>">Primero</a>
          </li>
          <li class="page-item <?= ($pagina == 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&buscar=<?= urlencode($filtro_email) ?>&desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>">Anterior</a>
          </li>
          <?php
          $rango = 3;
          $inicio = max(1, $pagina - $rango);
          $fin = min($total_paginas, $pagina + $rango);
          for ($i = $inicio; $i <= $fin; $i++): ?>
            <li class="page-item <?= ($i == $pagina) ? 'active' : '' ?>">
              <a class="page-link" href="?pagina=<?= $i ?>&buscar=<?= urlencode($filtro_email) ?>&desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&buscar=<?= urlencode($filtro_email) ?>&desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>">Siguiente</a>
          </li>
          <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=<?= $total_paginas ?>&buscar=<?= urlencode($filtro_email) ?>&desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>">Último</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>

    <div class="text-center mt-3">
      <a href="index_admin.php" class="btn btn-warning">← Volver al Panel</a>
    </div>
  </div>
</body>

</html>