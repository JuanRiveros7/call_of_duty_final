<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (empty($_SESSION['rol']) || !in_array($_SESSION['rol'], ['player', 'admin'])) {
  header("Location: ../../login.php");
  exit;
}

require_once __DIR__ . '/../../../db/conexion.php';
$db = new Database();
$con = $db->conectar();

// OBTENER DATOS DEL USUARIO
$id_usuario = $_SESSION['id_usuario'] ?? null;
if (!$id_usuario) {
  header("Location: ../../login.php");
  exit;
}

// Nivel y rango actual del usuario
$stmt = $con->prepare("
  SELECT u.id_usuario, u.id_nivel, u.id_rango, u.id_arma,
         n.nombre_nivel, r.nombre_rango
  FROM usuarios u
  INNER JOIN niveles n ON u.id_nivel = n.id_nivel
  INNER JOIN rangos r ON u.id_rango = r.id_rango
  WHERE u.id_usuario = :id_usuario
");
$stmt->execute([':id_usuario' => $id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
  die('Usuario no encontrado.');
}

$id_nivel_usuario = $usuario['id_nivel'];

// OBTENER TODAS LAS ARMAS
$sql = "SELECT * FROM armas ORDER BY nivel_requerido ASC";
$stmt = $con->query($sql);
$armas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// GUARDAR ARMA SELECCIONADA
if (isset($_POST['seleccionar_arma'])) {
  $id_arma = intval($_POST['id_arma']);

  // Verificar si el usuario tiene nivel suficiente
  $stmtCheck = $con->prepare("SELECT nivel_requerido FROM armas WHERE id_arma = ?");
  $stmtCheck->execute([$id_arma]);
  $arma = $stmtCheck->fetch(PDO::FETCH_ASSOC);

  if (!$arma) {
    die("Arma no encontrada.");
  }

  if ($id_nivel_usuario < $arma['nivel_requerido']) {
    die("<div style='color:red; text-align:center;'> No tienes el nivel suficiente para usar esta arma.</div>");
  }

  // Solo actualiza el arma del usuario, sin tocar la tabla desbloqueos
  $update = $con->prepare("UPDATE usuarios SET id_arma = :arma WHERE id_usuario = :usuario");
  $update->execute([
    ':arma' => $id_arma,
    ':usuario' => $id_usuario
  ]);

  header("Location: ../lobby.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Armas - Call of Duty</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../../../assets/css/style.css">
</head>

<body class="bg-dark text-white">

  <!-- ==========================
      NAVBAR
  =========================== -->
  <nav class="navbar navbar-expand-lg navbar-dark fixed-top bg-black shadow">
    <div class="container">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="btn btn-warning ms-3" href="../lobby.php">Regresar al Lobby</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- ==========================
      SECCIÓN DE ARMAS
  =========================== -->
  <section id="armas" class="py-5 text-center bg-black" style="margin-top: 80px;">
    <div class="container">
      <h2 class="mb-4 text-uppercase fw-bold text-warning">Selecciona tu arma</h2>

      <div class="row g-4">
        <?php foreach ($armas as $arma):
          $nivel_req = $arma['nivel_requerido'];
          $desbloqueada = ($id_nivel_usuario >= $nivel_req);
        ?>
          <div class="col-md-4">
            <div class="card h-100 border-warning position-relative <?= $desbloqueada ? 'bg-dark' : 'bg-secondary bg-opacity-50' ?>">
              <img src="../../../<?= htmlspecialchars($arma['imagen']) ?>"
                class="card-img-top <?= !$desbloqueada ? 'opacity-50' : '' ?>"
                alt="<?= htmlspecialchars($arma['nombre']) ?>">

              <?php if (!$desbloqueada): ?>
                <!-- Candado central -->
                <div class="position-absolute top-50 start-50 translate-middle text-center">
                  <i class="bi bi-lock-fill" style="font-size: 3rem; color: #ff4444;"></i>
                  <p class="mt-2 fw-bold text-danger">Nivel <?= htmlspecialchars($nivel_req) ?> requerido</p>
                </div>
              <?php endif; ?>

              <div class="card-body text-center">
                <h5 class="card-title text-warning mb-3"><?= htmlspecialchars($arma['nombre']) ?></h5>

                <!-- Información requerida organizada -->
                <ul class="list-group list-group-flush mb-3">
                  <li class="list-group-item bg-transparent text-white border-warning rounded py-2">
                    <i class="bi bi-fire text-danger"></i> <strong>Daño:</strong> <?= htmlspecialchars($arma['dano']) ?>
                  </li>
                  <li class="list-group-item bg-transparent text-white border-warning rounded py-2">
                    <i class="bi bi-bullet text-warning"></i> <strong>Balas en cargador:</strong> <?= htmlspecialchars($arma['municion_max']) ?>
                  </li>
                </ul>
                <!-- Fin información requerida -->

                <p class="card-text small text-light mb-3"><?= htmlspecialchars($arma['descripcion']) ?></p>

                <?php if ($desbloqueada): ?>
                  <form method="POST" class="mt-3">
                    <input type="hidden" name="id_arma" value="<?= $arma['id_arma'] ?>">
                    <button type="submit" name="seleccionar_arma" class="btn btn-warning w-100 fw-bold">
                      <i class="bi bi-check-circle"></i> Seleccionar
                    </button>
                  </form>
                <?php else: ?>
                  <button class="btn btn-secondary w-100 mt-3" disabled>
                    <i class="bi bi-lock"></i> Bloqueada
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <footer class="text-center py-4 bg-black mt-5">
    <small class="text-secondary">
      © <?= date('Y') ?> Call of Duty | Zona de Armas
    </small>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>