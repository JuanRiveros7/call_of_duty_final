<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../../login.php");
  exit();
}

$nombre = $_SESSION['nombre_usuario'] ?? 'Usuario desconocido';
$rol = $_SESSION['rol'] ?? '';
$id_admin = $_SESSION['id_usuario']; // ← lo usaremos para registrar acciones

if (isset($_POST['cerrar'])) {
  session_destroy();
  header("Location: ../../index.php");
  exit();
}

require '../../db/conexion.php';

$db = new Database();
$pdo = $db->conectar();

$stmt = $pdo->query("
    SELECT 
        u.id_usuario,
        u.nombre_usuario,
        u.email,
        e.nombre_estado
    FROM usuarios u
    INNER JOIN estados_usuario e ON u.id_estado = e.id_estado
");

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administrador</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark">
  <header class="bg-warning text-dark py-3 shadow">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between">
      <div class="d-flex align-items-center">
        <img src="../../assets/img/fondos/logo.png" alt="logo" class="me-3" style="width:200px; height:50px;">
        <h4 class="mb-0">
          Bienvenido, <?php echo htmlspecialchars($nombre); ?>
          <small class="text-dark">| Rol: <?php echo htmlspecialchars($rol); ?></small>
        </h4>
      </div>
      <form method="post" action="../../controller/cerrar_sesion.php" class="mt-3 mt-md-0">
        <button type="submit" name="cerrar" class="btn btn-outline-dark">
          <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
        </button>
      </form>
    </div>
  </header>
  <main class="container my-5">
    <div class="row g-4">

      <!-- Card administrar Usuarios -->
      <div class="col-md-6">
        <div class="card text-center shadow-sm bg-warning text-dark">
          <div class="card-body">
            <i class="bi bi-person-circle" style="font-size:3rem;"></i>
            <h5 class="card-title mt-3">Usuarios Pendientes</h5>
            <a href="administrar_usuarios.php" class="btn btn-dark">
              <i class="bi bi-people-fill"></i> Entrar
            </a>
          </div>
        </div>
      </div>

      <!-- Card registro -->
      <div class="col-md-6">
        <div class="card text-center shadow-sm bg-warning text-dark">
          <div class="card-body">
            <i class="bi bi-clipboard-minus-fill" style="font-size:3rem;"></i>
            <h5 class="card-title mt-3">Registro de Usuarios</h5>
            <a href="historial_admin.php" class="btn btn-dark">
              <i class="bi bi-eye"></i> Ver
            </a>
          </div>
        </div>
      </div>


      <!-- Card ir al index.php -->
      <div class="col-md-6">
        <div class="card text-center shadow-sm bg-warning text-dark">
          <div class="card-body">
            <i class="bi bi-laptop" style="font-size:3rem;"></i>
            <h5 class="card-title mt-3">Ir a la interfaz</h5>
            <a href="" class="btn btn-dark"
              onclick="window.open('../player/index_player.php', '', 'width=2000%,height=1000%,toolbar=NO'); return false;">
              <i class="bi bi-controller"></i> Administrar
            </a>
          </div>
        </div>
      </div>

      <!-- historial recuperaciones -->
      <div class="col-md-6">
        <div class="card text-center shadow-sm bg-warning text-dark">
          <div class="card-body">
            <i class="bi bi-clipboard-check-fill" style="font-size:3rem;"></i>
            <h5 class="card-title mt-3">Historial de Recuperaciones</h5>
            <a href="historial_recuperaciones.php" class="btn btn-dark">
              <i class="bi bi-eye"></i> Ver
            </a>
          </div>
        </div>
      </div>

      <!-- Añadir Nuevos personajes -->
      <div class="col-md-6">
        <div class="card text-center shadow-sm bg-warning text-dark">
          <div class="card-body">
            <i class="bi bi-person-add" style="font-size:3rem;"></i>
            <h5 class="card-title mt-3">Añadir Nuevos personajes</h5>
            <a href="actualizaciones/add_personaje.php" class="btn btn-dark">
              <i class="bi bi-eye"></i> Ver
            </a>
          </div>
        </div>
      </div>

      <!-- Añadir Nuevas Armas -->
      <div class="col-md-6">
        <div class="card text-center shadow-sm bg-warning text-dark">
          <div class="card-body">
            <i class="bi bi-download" style="font-size:3rem;"></i>
            <h5 class="card-title mt-3">Añadir Nuevas Armas</h5>
            <a href="actualizaciones/add_arma.php" class="btn btn-dark">
              <i class="bi bi-eye"></i> Ver
            </a>
          </div>
        </div>
      </div>

      <!-- Añadir Nuevas Avatares -->
      <div class="col-md-6">
        <div class="card text-center shadow-sm bg-warning text-dark">
          <div class="card-body">
            <i class="bi bi-person-circle" style="font-size:3rem;"></i>
            <h5 class="card-title mt-3">Añadir Nuevas Avatares</h5>
            <a href="actualizaciones/add_avatar.php" class="btn btn-dark">
              <i class="bi bi-eye"></i> Ver
            </a>
          </div>
        </div>
      </div>

      <!-- Añadir Nuevas Mapas -->
      <div class="col-md-6">
        <div class="card text-center shadow-sm bg-warning text-dark">
          <div class="card-body">
            <i class="bi bi-map-fill" style="font-size:3rem;"></i>
            <h5 class="card-title mt-3">Añadir Nuevas Mapas</h5>
            <a href="actualizaciones/add_mapa.php" class="btn btn-dark">
              <i class="bi bi-eye"></i> Ver
            </a>
          </div>
        </div>
      </div>

      <!-- Añadir Nuevas salas -->
      <div class="col-md-6">
        <div class="card text-center shadow-sm bg-warning text-dark">
          <div class="card-body">
            <i class="bi bi-house-add" style="font-size:3rem;"></i>
            <h5 class="card-title mt-3">Añadir Nuevas salas</h5>
            <a href="actualizaciones/add_salas.php" class="btn btn-dark">
              <i class="bi bi-eye"></i> Ver
            </a>
          </div>
        </div>
      </div>

      <!-- Ver partidas jugadas -->
      <div class="col-md-6">
        <div class="card text-center shadow-sm bg-warning text-dark">
          <div class="card-body">
            <i class="bi bi-controller" style="font-size:3rem;"></i>
            <h5 class="card-title mt-3">Ver partidas jugadas</h5>
            <a href="ver_partidas_jugadas.php" class="btn btn-dark">
              <i class="bi bi-eye"></i> Ver
            </a>
          </div>
        </div>
      </div>

    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>