<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['player', 'admin'])) {
  header("Location: ../../login.php");
  exit;
}

require '../../../db/conexion.php';
$db = new Database();
$con = $db->conectar();

$id_usuario = $_SESSION['id_usuario'] ?? null;
if (!$id_usuario) {
  header("Location: ../../login.php");
  exit;
}

$stmt = $con->prepare("
  SELECT 
    u.id_personaje
  FROM usuarios u
  WHERE u.id_usuario = :id_usuario
");
$stmt->execute([':id_usuario' => $id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

//OBTENER TODOS LOS PERSONAJES

$pj_stmt = $con->query("SELECT * FROM personajes");
$personajes = $pj_stmt->fetchAll(PDO::FETCH_ASSOC);

// GUARDAR PERSONAJE SELECCIONADO 

if (isset($_POST['seleccionar_personaje'])) {
  $id_personaje = intval($_POST['id_personaje']);
  $update = $con->prepare("UPDATE usuarios SET id_personaje = :id_personaje WHERE id_usuario = :id_usuario");
  $update->execute([
    ':id_personaje' => $id_personaje,
    ':id_usuario' => $id_usuario
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
  <title>Personajes - Call of Duty</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../../assets/css/style.css">
</head>

<body class="bg-black text-white">

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

  <!-- SECCIÓN DE PERSONAJES -->

  <section id="personajes" class="py-5 text-white text-center bg-black" style="margin-top: 80px;">
    <div class="container">
      <h2 class="mb-4 text-uppercase fw-bold text-warning">Selecciona tu personaje</h2>

      <div class="row g-4">
        <?php foreach ($personajes as $pj): ?>
          <div class="col-md-4">
            <div class="card bg-dark text-white h-100 border-warning">
              <img src="../../../<?= htmlspecialchars($pj['imagen']) ?>" class="card-img-top" alt="<?= htmlspecialchars($pj['nombre']) ?>">
              <div class="card-body">
                <h5 class="card-title text-warning"><?= htmlspecialchars($pj['nombre']) ?></h5>
                <p class="card-text"><?= htmlspecialchars($pj['descripcion']) ?></p>

                <!-- Botón para seleccionar -->
                <form method="POST" class="mt-3">
                  <input type="hidden" name="id_personaje" value="<?= $pj['id_personaje'] ?>">
                  <button type="submit" name="seleccionar_personaje" class="btn btn-warning w-100">
                    Seleccionar
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <footer class="text-center py-4 bg-black mt-5">
    <small class="text-secondary">
      © <?= date('Y') ?> Call of Duty | Zona de Personajes
    </small>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>