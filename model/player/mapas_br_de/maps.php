<?php
session_start();
require_once __DIR__ . '/../../db/conexion.php';

// Conexión a la base de datos
$db = new Database();
$con = $db->conectar();

// Validar modo de juego recibido
$modo = $_GET['modo'] ?? 'BR';
$modo = in_array($modo, ['BR', 'DE']) ? $modo : 'BR';

// Consulta de mapas desde la base de datos
$sql = "SELECT nombre_mapa, imagen_preview, descripcion 
        FROM mapas 
        WHERE modo_juego = :modo";

$stmt = $con->prepare($sql);
$stmt->bindParam(':modo', $modo);
$stmt->execute();
$mapas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Selección de Mapa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">

  <div class="container py-5 text-center">

    <!-- Título -->
    <h2 class="fw-bold text-warning mb-4">
      SELECCIONA UN MAPA -
      <span class="text-light">
        <?= ($modo === 'BR') ? 'BATTLE ROYALE' : 'MULTIPLAYER'; ?>
      </span>
    </h2>

    <!-- Lista de Mapas -->
    <div class="row justify-content-center">
      <?php if (!empty($mapas)): ?>
        <?php foreach ($mapas as $mapa): ?>
          <div class="col-md-3 m-3">
            <div class="card bg-secondary text-light border border-warning h-100 shadow-lg">
              <div class="card-body d-flex flex-column align-items-center">
                <img src="<?= htmlspecialchars($mapa['imagen_preview']); ?>"
                  alt="<?= htmlspecialchars($mapa['nombre_mapa']); ?>"
                  width="100" class="mb-3 rounded border border-warning">
                <h5 class="card-title fw-bold"><?= htmlspecialchars($mapa['nombre_mapa']); ?></h5>
                <p class="card-text small text-light mb-3"><?= htmlspecialchars($mapa['descripcion']); ?></p>

                <form action="ver_partidas.php" method="GET">
                  <input type="hidden" name="mapa" value="<?= htmlspecialchars($mapa['nombre_mapa']); ?>">
                  <input type="hidden" name="modo" value="<?= htmlspecialchars($modo); ?>">
                  <button type="submit" class="btn btn-warning fw-bold w-100">
                    Mirar partidas disponibles
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-light">No hay mapas disponibles para este modo.</p>
      <?php endif; ?>
    </div>

    <a href="../lobby.php" class="btn btn-outline-light mt-4">← Volver</a>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>