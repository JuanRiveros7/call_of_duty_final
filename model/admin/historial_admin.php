<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../../login.php");
  exit();
}

require_once("../../db/conexion.php");

$db = new Database();
$con = $db->conectar();

// --- Parámetros de filtro y paginación ---
$filtro_accion = $_GET['accion'] ?? '';
$busqueda = $_GET['buscar'] ?? '';
$fecha_desde = $_GET['desde'] ?? '';
$fecha_hasta = $_GET['hasta'] ?? '';
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registros_por_pagina = 10;
$offset = ($pagina - 1) * $registros_por_pagina;

// --- Consulta principal con filtros dinámicos ---
$sql = "
  SELECT 
      h.id_accion,
      a.nombre_usuario AS admin_nombre,
      u.nombre_usuario AS usuario_nombre,
      h.accion,
      h.motivo,
      h.fecha
  FROM historial_acciones h
  INNER JOIN usuarios a ON h.id_admin = a.id_usuario
  INNER JOIN usuarios u ON h.id_usuario = u.id_usuario
  WHERE 1=1
";

$params = [];

// Filtro de acción
if (!empty($filtro_accion)) {
  $sql .= " AND LOWER(h.accion) = LOWER(:accion)";
  $params[':accion'] = trim($filtro_accion);
}

// Filtro de búsqueda general
if (!empty($busqueda)) {
  $sql .= " AND (a.nombre_usuario LIKE :buscar OR u.nombre_usuario LIKE :buscar)";
  $params[':buscar'] = "%$busqueda%";
}

// Filtro por rango de fechas
if (!empty($fecha_desde)) {
  $sql .= " AND DATE(h.fecha) >= :desde";
  $params[':desde'] = $fecha_desde;
}
if (!empty($fecha_hasta)) {
  $sql .= " AND DATE(h.fecha) <= :hasta";
  $params[':hasta'] = $fecha_hasta;
}

$sql .= " ORDER BY h.fecha DESC LIMIT :offset, :limit";
$query = $con->prepare($sql);

foreach ($params as $k => $v) {
  $query->bindValue($k, $v, PDO::PARAM_STR);
}
$query->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$query->bindValue(':limit', (int)$registros_por_pagina, PDO::PARAM_INT);

$query->execute();
$historial = $query->fetchAll(PDO::FETCH_ASSOC);

// --- Calcular total de registros ---
$sql_count = "
  SELECT COUNT(*) 
  FROM historial_acciones h
  INNER JOIN usuarios a ON h.id_admin = a.id_usuario
  INNER JOIN usuarios u ON h.id_usuario = u.id_usuario
  WHERE 1=1
";

if (!empty($filtro_accion)) {
  $sql_count .= " AND LOWER(h.accion) = LOWER(" . $con->quote($filtro_accion) . ")";
}
if (!empty($busqueda)) {
  $sql_count .= " AND (a.nombre_usuario LIKE " . $con->quote("%$busqueda%") . " OR u.nombre_usuario LIKE " . $con->quote("%$busqueda%") . ")";
}
if (!empty($fecha_desde)) {
  $sql_count .= " AND DATE(h.fecha) >= " . $con->quote($fecha_desde);
}
if (!empty($fecha_hasta)) {
  $sql_count .= " AND DATE(h.fecha) <= " . $con->quote($fecha_hasta);
}

$total_registros = $con->query($sql_count)->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Historial de Acciones - Admin COD</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">
  <div class="container py-5">
    <h1 class="text-warning text-center mb-4">Historial de Acciones del Administrador</h1>

    <!-- FILTROS -->
    <form method="get" class="row mb-4 g-2">
      <div class="col-md-3">
        <select name="accion" class="form-select">
          <option value="">Todas las acciones</option>
          <option value="activar" <?= strtolower($filtro_accion) == 'activar' ? 'selected' : '' ?>>Activar</option>
          <option value="bloquear" <?= strtolower($filtro_accion) == 'bloquear' ? 'selected' : '' ?>>Bloquear</option>
          <option value="banear" <?= strtolower($filtro_accion) == 'banear' ? 'selected' : '' ?>>Banear</option>
          <option value="inactivar" <?= strtolower($filtro_accion) == 'inactivar' ? 'selected' : '' ?>>Inactivar</option>
          <option value="pendiente" <?= strtolower($filtro_accion) == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
        </select>
      </div>

      <div class="col-md-3">
        <input type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>" class="form-control" placeholder="Buscar usuario o admin...">
      </div>

      <div class="col-md-2">
        <input type="date" name="desde" value="<?= htmlspecialchars($fecha_desde) ?>" class="form-control">
      </div>

      <div class="col-md-2">
        <input type="date" name="hasta" value="<?= htmlspecialchars($fecha_hasta) ?>" class="form-control">
      </div>

      <div class="col-md-2">
        <button type="submit" class="btn btn-warning w-100">Filtrar</button>
      </div>
    </form>

    <!-- TABLA -->
    <table class="table table-dark table-striped text-center align-middle">
      <thead class="table-warning text-dark">
        <tr>
          <th>ID</th>
          <th>Administrador</th>
          <th>Usuario</th>
          <th>Acción</th>
          <th>Motivo</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($historial)): ?>
          <tr>
            <td colspan="6" class="text-muted">No se encontraron registros.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($historial as $h): ?>
            <tr>
              <td><?= $h['id_accion'] ?></td>
              <td><?= htmlspecialchars($h['admin_nombre']) ?></td>
              <td><?= htmlspecialchars($h['usuario_nombre']) ?></td>
              <td><?= ucfirst(htmlspecialchars($h['accion'])) ?></td>
              <td><?= htmlspecialchars($h['motivo']) ?></td>
              <td><?= $h['fecha'] ?></td>
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
            <a class="page-link" href="?pagina=1&accion=<?= urlencode($filtro_accion) ?>&buscar=<?= urlencode($busqueda) ?>&desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>">Primero</a>
          </li>
          <li class="page-item <?= ($pagina == 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&accion=<?= urlencode($filtro_accion) ?>&buscar=<?= urlencode($busqueda) ?>&desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>">Anterior</a>
          </li>
          <?php
          $rango = 3;
          $inicio = max(1, $pagina - $rango);
          $fin = min($total_paginas, $pagina + $rango);
          for ($i = $inicio; $i <= $fin; $i++): ?>
            <li class="page-item <?= ($i == $pagina) ? 'active' : '' ?>">
              <a class="page-link" href="?pagina=<?= $i ?>&accion=<?= urlencode($filtro_accion) ?>&buscar=<?= urlencode($busqueda) ?>&desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&accion=<?= urlencode($filtro_accion) ?>&buscar=<?= urlencode($busqueda) ?>&desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>">Siguiente</a>
          </li>
          <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=<?= $total_paginas ?>&accion=<?= urlencode($filtro_accion) ?>&buscar=<?= urlencode($busqueda) ?>&desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>">Último</a>
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