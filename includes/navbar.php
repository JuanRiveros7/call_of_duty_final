<?php
$avatar_actual = $_SESSION['avatar'] ?? '/call_of_duty/assets/img/avatar/avatar.jpg';
$nombre = $_SESSION['nombre_usuario'] ?? 'Usuario desconocido';
$rol = $_SESSION['rol'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? null;

// Obtener datos adicionales del usuario
if ($id_usuario && isset($con)) {
    $stmt = $con->prepare("
      SELECT u.puntos_totales, n.nombre_nivel
      FROM usuarios u
      LEFT JOIN niveles n ON u.id_nivel = n.id_nivel
      WHERE u.id_usuario = :id
  ");
    $stmt->execute([':id' => $id_usuario]);
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top bg-dark shadow">
    <div class="container">
        <div class="navbar-user d-flex align-items-center">
            <div class="avatar-container me-2">
                <a href="/model/player/avatar_elegir/elegir_avatar.php">
                    <img src="<?php echo '../../' . htmlspecialchars($avatar_actual); ?>"
                        alt="Avatar del jugador"
                        class="avatar rounded-circle"
                        style="width:50px; height:50px; object-fit:cover; cursor:pointer;">
                </a>
            </div>
            <div class="user-info text-white">
                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($nombre); ?></h6>
                <small class="text-warning">Nivel <?php echo htmlspecialchars($datos['nombre_nivel'] ?? '0'); ?></small>
                <small class="text-danger">Puntos <?php echo htmlspecialchars($datos['puntos_totales'] ?? '0'); ?></small>
            </div>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="#home">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="#personajes">Personajes</a></li>
                <li class="nav-item"><a class="nav-link" href="#armas">Armas</a></li>
                <li class="nav-item">
                    <form method="post" class="d-inline">
                        <button type="submit" name="cerrar" class="btn btn-outline-light ms-3">Cerrar sesión</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>