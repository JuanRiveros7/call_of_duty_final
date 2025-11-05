<?php
session_start();
require_once("db/conexion.php");

$db = new Database();
$con = $db->conectar();

$mensaje = "";
$tipo_alerta = "danger";

// Verificar token en URL

$token = $_GET['token'] ?? '';

if (empty($token)) {
  $mensaje = "Token no válido o ausente.";
} else {
  // Convertir a hash (como se guardó)
  $tokenHash = hash('sha256', $token);

  // Buscar token en la base de datos
  $stmt = $con->prepare("
        SELECT prt.id, prt.id_usuario, prt.expires_at, prt.used, u.nombre_usuario 
        FROM password_reset_tokens prt
        INNER JOIN usuarios u ON prt.id_usuario = u.id_usuario
        WHERE prt.token_hash = ?
    ");
  $stmt->execute([$tokenHash]);
  $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$tokenData) {
    $mensaje = "El enlace no es válido o ya ha sido usado.";
  } elseif ($tokenData['used']) {
    $mensaje = "Este enlace ya fue utilizado para cambiar la contraseña.";
  } elseif (strtotime($tokenData['expires_at']) < time()) {
    $mensaje = "El enlace ha expirado. Solicita nuevamente el restablecimiento.";
  } else {
    // Token válido, verificar si se envió el formulario
    if (isset($_POST['restablecer'])) {
      $nueva = trim($_POST['nueva_contrasena']);
      $confirmar = trim($_POST['confirmar_contrasena']);
      $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

      if (empty($nueva) || empty($confirmar)) {
        $mensaje = "Por favor, completa todos los campos.";
      } elseif ($nueva !== $confirmar) {
        $mensaje = "Las contraseñas no coinciden.";
      } elseif (strlen($nueva) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
      } else {
        // actualizar contraseña

        $hash = password_hash($nueva, PASSWORD_DEFAULT);

        $update = $con->prepare("UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?");
        $update->execute([$hash, $tokenData['id_usuario']]);

        // Marcar token como usado

        $markUsed = $con->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
        $markUsed->execute([$tokenData['id']]);

        // Registrar log del restablecimiento

        $log = $con->prepare("
                    INSERT INTO password_reset_logs (email, id_usuario, ip, success, fecha)
                    VALUES (
                        (SELECT email FROM usuarios WHERE id_usuario = ?),
                        ?, ?, 1, NOW()
                    )
                ");
        $log->execute([$tokenData['id_usuario'], $tokenData['id_usuario'], $ip]);

        $mensaje = "Tu contraseña ha sido cambiada exitosamente.";
        $tipo_alerta = "success";
      }
    } else {
      // Token válido y formulario aún no enviado
      $mensaje = "Introduce tu nueva contraseña, " . htmlspecialchars($tokenData['nombre_usuario']) . ".";
      $tipo_alerta = "info";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restablecer contraseña - Call of Duty</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>
  <div class="container">
    <div class="login-box">
      <a class="navbar-brand" href="index.html">CALL OF DUTY</a>
      <h2>Restablecer tu contraseña</h2>

      <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($tipo_alerta); ?> alert-dismissible fade show mt-3" role="alert">
          <?php echo htmlspecialchars($mensaje); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if ($tipo_alerta !== "success"): ?>
        <form action="" method="POST" class="mt-3">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">

          <div class="mb-3">
            <label for="nueva_contrasena" class="form-label fw-semibold">
              <i class="bi bi-lock-fill me-1"></i>Nueva contraseña
            </label>
            <input
              type="password"
              name="nueva_contrasena"
              id="nueva_contrasena"
              placeholder="Ingresa tu nueva contraseña"
              class="form-control rounded-pill"
              required>
          </div>

          <div class="mb-3">
            <label for="confirmar_contrasena" class="form-label fw-semibold">
              <i class="bi bi-lock-fill me-1"></i>Confirmar contraseña
            </label>
            <input
              type="password"
              name="confirmar_contrasena"
              id="confirmar_contrasena"
              placeholder="Confirma tu nueva contraseña"
              class="form-control rounded-pill"
              required>
          </div>

          <button type="submit" name="restablecer" class="btn-orange w-100 rounded-pill py-2 fw-semibold">
            Cambiar contraseña
          </button>
        </form>
      <?php endif; ?>

      <div class="options mt-4 text-center">
        <p><a href="login.php">¿Ya recuerdas tu contraseña? Inicia sesión</a></p>
        <p>O</p>
        <div class="d-flex justify-content-center gap-3 fs-3">
          <a href="https://store.steampowered.com/?l=spanish" class="text-light"><i class="bi bi-steam"></i></a>
          <a href="https://www.xbox.com/es-ES" class="text-success"><i class="bi bi-xbox"></i></a>
          <a href="https://www.playstation.com/es-es/" class="text-primary"><i class="bi bi-playstation"></i></a>
          <a href="https://www.battle.net/" class="text-info"><i class="bi bi-globe2"></i> Battle.net</a>
        </div>
        <p class="mt-3">¿Necesitas ayuda? <a href="views/soporte/ayuda_recuperar.php">Centro de soporte</a></p>
      </div>
    </div>

    <div class="bg-img"></div>
  </div>

  <footer class="footer text-center">
    <a href="views/legal/legal.php">LEGAL</a> /
    <a href="views/legal/terminos_uso.php">TÉRMINOS DE USO</a> /
    <a href="views/legal/politicas_privacidad.php">POLÍTICA DE PRIVACIDAD</a> /
    <a href="views/legal/politica_cookies.php">POLÍTICA DE COOKIES</a> /
    <a href="views/legal/configuracion_cookies.php">CONFIGURACIÓN DE COOKIES</a> /
    <a href="views/legal/seguridad_linea.php">SEGURIDAD EN LÍNEA</a> /
    <a href="views/legal/apoyo.php">APOYO</a>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>