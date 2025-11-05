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
$busqueda = $_GET['buscar'] ?? '';
$modo_juego = $_GET['modo'] ?? '';
$fecha_desde = $_GET['desde'] ?? '';
$fecha_hasta = $_GET['hasta'] ?? '';
$pagina = (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) ? (int)$_GET['pagina'] : 1;
$registros_por_pagina = 10;
$offset = ($pagina - 1) * $registros_por_pagina;

// --- Construcción de la consulta principal ---
$sql = "
    SELECT 
        rp.id_reporte,
        rp.id_partida,
        u.nombre_usuario,
        m.nombre_mapa,
        rp.modo_juego,
        rp.resultado,
        rp.kills,
        rp.dano_causado,
        rp.dano_recibido,
        rp.puntos_obtenidos,
        rp.nivel_al_jugar,
        rp.duracion_segundos,
        rp.fecha_partida
    FROM reporte_partidas rp
    INNER JOIN usuarios u ON rp.id_usuario = u.id_usuario
    INNER JOIN mapas m ON rp.id_mapa = m.id_mapa
    WHERE 1=1
";

$params = [];

// --- Filtro búsqueda ---
if (!empty($busqueda)) {
  $sql .= " AND (u.nombre_usuario LIKE :buscar1 OR m.nombre_mapa LIKE :buscar2)";
  $params[':buscar1'] = "%$busqueda%";
  $params[':buscar2'] = "%$busqueda%";
}

// --- Filtro modo de juego (BR / DE) ---
if (!empty($modo_juego)) {
  $sql .= " AND rp.modo_juego = :modo";
  $params[':modo'] = $modo_juego;
}

// --- Filtro rango de fechas ---
if (!empty($fecha_desde)) {
  $sql .= " AND DATE(rp.fecha_partida) >= :desde";
  $params[':desde'] = $fecha_desde;
}
if (!empty($fecha_hasta)) {
  $sql .= " AND DATE(rp.fecha_partida) <= :hasta";
  $params[':hasta'] = $fecha_hasta;
}

// ⚠️ LIMIT sin parámetros (MySQL no acepta bind en LIMIT)
$sql .= " ORDER BY rp.fecha_partida DESC LIMIT $offset, $registros_por_pagina";

$query = $con->prepare($sql);

// --- Vincular parámetros ---
foreach ($params as $key => $value) {
  $query->bindValue($key, $value, PDO::PARAM_STR);
}

// --- Ejecutar consulta ---
$query->execute();
$partidas = $query->fetchAll(PDO::FETCH_ASSOC);

// --- Contar total de registros ---
$sql_count = "
    SELECT COUNT(*)
    FROM reporte_partidas rp
    INNER JOIN usuarios u ON rp.id_usuario = u.id_usuario
    INNER JOIN mapas m ON rp.id_mapa = m.id_mapa
    WHERE 1=1
";

if (!empty($busqueda)) {
  $sql_count .= " AND (u.nombre_usuario LIKE " . $con->quote("%$busqueda%") . " OR m.nombre_mapa LIKE " . $con->quote("%$busqueda%") . ")";
}
if (!empty($modo_juego)) {
  $sql_count .= " AND rp.modo_juego = " . $con->quote($modo_juego);
}
if (!empty($fecha_desde)) {
  $sql_count .= " AND DATE(rp.fecha_partida) >= " . $con->quote($fecha_desde);
}
if (!empty($fecha_hasta)) {
  $sql_count .= " AND DATE(rp.fecha_partida) <= " . $con->quote($fecha_hasta);
}

$total_registros = $con->query($sql_count)->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Reporte de Partidas Jugadas - Admin COD</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">
  <div class="container py-5">
    <h1 class="text-warning text-center mb-4">Reporte de Partidas Jugadas</h1>

    <!-- FILTROS -->
    <form method="get" class="row mb-4 g-2">
      <div class="col-md-3">
        <input type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>" class="form-control" placeholder="Buscar usuario o mapa...">
      </div>

      <div class="col-md-2">
        <select name="modo" class="form-select">
          <option value="">Todos los modos</option>
          <option value="BR" <?= $modo_juego == 'BR' ? 'selected' : '' ?>>BR</option>
          <option value="DE" <?= $modo_juego == 'DE' ? 'selected' : '' ?>>DE</option>
        </select>
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
          <th>ID Partida</th>
          <th>Jugador</th>
          <th>Mapa</th>
          <th>Modo</th>
          <th>Resultado</th>
          <th>Kills</th>
          <th>Daño Causado</th>
          <th>Daño Recibido</th>
          <th>Puntos</th>
          <th>Nivel</th>
          <th>Duración</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($partidas)): ?>
          <tr>
            <td colspan="13" class="text-muted">No se encontraron partidas registradas.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($partidas as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['id_partida']) ?></td>
              <td><?= htmlspecialchars($p['nombre_usuario']) ?></td>
              <td><?= htmlspecialchars($p['nombre_mapa']) ?></td>
              <td><?= htmlspecialchars($p['modo_juego']) ?></td>
              <td><?= htmlspecialchars($p['resultado']) ?></td>
              <td><?= htmlspecialchars($p['kills']) ?></td>
              <td><?= htmlspecialchars($p['dano_causado']) ?></td>
              <td><?= htmlspecialchars($p['dano_recibido']) ?></td>
              <td><?= htmlspecialchars($p['puntos_obtenidos']) ?></td>
              <td><?= htmlspecialchars($p['nivel_al_jugar']) ?></td>
              <td><?= htmlspecialchars($p['duracion_segundos']) ?>s</td>
              <td><?= date("d/m/Y H:i", strtotime($p['fecha_partida'])) ?></td>
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
            <a class="page-link" href="?pagina=1&buscar=<?= urlencode($busqueda) ?>&modo=<?= urlencode($modo_juego) ?>&desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>">Primero</a>
          </li>
          <li class="page-item <?= ($pagina == 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&buscar=<?= urlencode($busqueda) ?>&modo=<?= urlencode($modo_juego) ?>&desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>">Anterior</a>
          </li>
          <?php
          $rango = 3;
          $inicio = max(1, $pagina - $rango);
          $fin = min($total_paginas, $pagina + $rango);
          for ($i = $inicio; $i <= $fin; $i++): ?>
            <li class="page-item <?= ($i == $pagina) ? 'active' : '' ?>">
              <a class="page-link" href="?pagina=<?= $i ?>&buscar=<?= urlencode($busqueda) ?>&modo=<?= urlencode($modo_juego) ?>&desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&buscar=<?= urlencode($busqueda) ?>&modo=<?= urlencode($modo_juego) ?>&desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>">Siguiente</a>
          </li>
          <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=<?= $total_paginas ?>&buscar=<?= urlencode($busqueda) ?>&modo=<?= urlencode($modo_juego) ?>&desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>">Último</a>
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