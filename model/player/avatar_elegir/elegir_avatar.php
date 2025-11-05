<?php
session_start();
require_once '../../../db/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
  header("Location: ../../../login.php");
  exit;
}

$id_usuario = $_SESSION['id_usuario'];

$db = new Database();
$con = $db->conectar();

// Obtener los avatares desde la BD
$sqlAvatares = "SELECT * FROM avatar";
$stmt = $con->query($sqlAvatares);
$avatares = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si el usuario selecciona un avatar
if (isset($_GET['id_avatar'])) {
  $id_avatar = (int) $_GET['id_avatar'];

  // Validar que el avatar exista
  $sqlValidar = "SELECT * FROM avatar WHERE id_avatar = :id";
  $stmtValidar = $con->prepare($sqlValidar);
  $stmtValidar->execute([':id' => $id_avatar]);
  $avatar = $stmtValidar->fetch(PDO::FETCH_ASSOC);

  if ($avatar) {
    // Actualizar el avatar del usuario
    $sqlUpdate = "UPDATE usuarios SET id_avatar = :id_avatar WHERE id_usuario = :id_usuario";
    $stmtUpdate = $con->prepare($sqlUpdate);
    $stmtUpdate->execute([
      ':id_avatar' => $id_avatar,
      ':id_usuario' => $id_usuario
    ]);

    // Actualizar la sesión con la nueva imagen
    $_SESSION['avatar'] = $avatar['url_imagen'];

    header("Location: ../index_player.php");
    exit;
  } else {
    echo "<div class='alert alert-danger text-center'> Avatar no válido.</div>";
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Elegir Avatar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-white text-center py-5">
  <div class="container">
    <h1 class="mb-4 text-warning">Elige tu Avatar</h1>
    <p class="mb-5">Haz clic en uno para seleccionarlo</p>

    <div class="row justify-content-center g-4">
      <?php foreach ($avatares as $a): ?>
        <div class="col-md-3">
          <a href="elegir_avatar.php?id_avatar=<?php echo $a['id_avatar']; ?>">
            <img src="../../../<?php echo htmlspecialchars($a['url_imagen']); ?>"
              class="rounded-circle border border-light shadow"
              style="width:150px; height:150px; object-fit:cover;">
          </a>
          <p class="mt-2"><?php echo htmlspecialchars($a['nombre']); ?></p>
        </div>
      <?php endforeach; ?>
    </div>

    <a href="../index_player.php" class="btn btn-outline-light mt-5">Volver</a>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>