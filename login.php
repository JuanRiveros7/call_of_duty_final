<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0"); // evita cacheo en algunos navegadores

require_once("db/conexion.php");

$db = new Database();
$con = $db->conectar();

// Si el usuario ya está logueado, redirigir según su rol
if (isset($_SESSION['id_usuario'])) {
  if ($_SESSION['rol'] === 'admin') {
    header("Location: model/admin/index_admin.php");
    exit();
  } elseif ($_SESSION['rol'] === 'player') {
    header("Location: model/player/index_player.php");
    exit();
  } else {
    session_destroy();
    header("Location: login.php");
    exit();
  }
}

// Si se envía el formulario
if (isset($_POST['inicio'])) {
  $usuario    = htmlspecialchars(trim($_POST["usuario"] ?? ''), ENT_QUOTES, 'UTF-8');
  $contrasena = trim($_POST["contrasena"] ?? '');
  $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

  // Validar campos vacíos
  if ($usuario === "" || $contrasena === "") {
    echo '<script>alert("Por favor ingrese nombre de usuario y contraseña.");</script>';
    echo '<script>window.location="login.php"</script>';
    exit();
  }

  // Validar reCAPTCHA
  $secretKey = "6LdKlQIsAAAAAGEUG3Hkj5mqrcW9F9qi4YR9xV1O";
  $verifyUrl = "https://www.google.com/recaptcha/api/siteverify";
  $response = @file_get_contents("$verifyUrl?secret=$secretKey&response=$recaptcha_response");
  $responseKeys = json_decode($response, true);

  if (empty($responseKeys) || !$responseKeys["success"]) {
    echo '<script>alert(" Verifica el reCAPTCHA antes de continuar.");</script>';
    echo '<script>window.location="login.php"</script>';
    exit();
  }

  // Buscar el usuario y su rol
  $sql = $con->prepare("
        SELECT u.*, r.nombre_rol
        FROM usuarios u
        INNER JOIN roles r ON u.id_rol = r.id_rol
        WHERE u.nombre_usuario = ?
    ");
  $sql->execute([$usuario]);
  $fila = $sql->fetch(PDO::FETCH_ASSOC);

  if (!$fila) {
    echo '<script>alert("Usuario no encontrado.");</script>';
    echo '<script>window.location="login.php"</script>';
    exit();
  }

  // --------------------
  // Validación de inactividad 10 días solo para players
  // --------------------
  if ($fila['nombre_rol'] === 'player' && !empty($fila['ultimo_inicio_sesion'])) {
    $ultimo = new DateTime($fila['ultimo_inicio_sesion']);
    $ahora = new DateTime();
    $diferencia = $ahora->diff($ultimo)->days;

    if ($diferencia > 10) {
      // Bloquear al usuario
      $updateEstado = $con->prepare("UPDATE usuarios SET id_estado = 2 WHERE id_usuario = ?");
      $updateEstado->execute([$fila['id_usuario']]);

      echo '<script>alert("Tu cuenta ha sido bloqueada por inactividad. Contacta al administrador.");</script>';
      echo '<script>window.location="login.php"</script>';
      exit();
    }
  }

  // Validar estado con id_estado (1=activo, 2=bloqueado, etc.)
  if ($fila['id_estado'] == 2) {
    echo '<script>alert("Tu cuenta ha sido bloqueada. Contacta al administrador.");</script>';
    echo '<script>window.location="login.php"</script>';
    exit();
  }

  // Verificar contraseña
  if (!password_verify($contrasena, $fila['contrasena'])) {
    echo '<script>alert("Contraseña incorrecta.");</script>';
    echo '<script>window.location="login.php"</script>';
    exit();
  }

  // Crear sesión
  $_SESSION['id_usuario']     = $fila['id_usuario'];
  $_SESSION['nombre_usuario'] = $fila['nombre_usuario'];
  $_SESSION['email']          = $fila['email'];
  $_SESSION['id_avatar']      = $fila['id_avatar'];
  $_SESSION['id_personaje']   = $fila['id_personaje'];
  $_SESSION['id_nivel']       = $fila['id_nivel'];
  $_SESSION['id_rol']         = $fila['id_rol'];
  $_SESSION['rol']            = $fila['nombre_rol'];
  $_SESSION['puntos']         = $fila['puntos_totales'];
  $_SESSION['estado']         = $fila['id_estado'];

  // Obtener y guardar el avatar del usuario
  $stmtAvatar = $con->prepare("
    SELECT a.url_imagen 
    FROM avatar a 
    INNER JOIN usuarios u ON a.id_avatar = u.id_avatar 
    WHERE u.id_usuario = :id");
  $stmtAvatar->execute([':id' => $fila['id_usuario']]);
  $avatarData = $stmtAvatar->fetch(PDO::FETCH_ASSOC);

  // Guardar en sesión
  $_SESSION['avatar'] = $avatarData['url_imagen'] ?? 'assets/img/avatar/avatar.jpg';

  // Actualizar último inicio de sesión
  $update = $con->prepare("UPDATE usuarios SET ultimo_inicio_sesion = NOW() WHERE id_usuario = ?");
  $update->execute([$fila['id_usuario']]);

  // Redirigir según el rol
  if ($_SESSION['rol'] === 'admin') {
    header("Location: model/admin/index_admin.php");
  } elseif ($_SESSION['rol'] === 'player') {
    header("Location: https://call_of_duty/model/player/index_player.php");
  } else {
    header("Location: index.php");
  }
  exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Call of Duty</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <!-- Script de Google reCAPTCHA -->
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body>
  <div class="container">
    <div class="login-box">
      <a class="navbar-brand" href="index.html">CALL OF DUTY</a>
      <h2>Inicia sesión en tu cuenta de Activision</h2>

      <form action="login.php" method="POST">
        <div class="mb-3">
          <label for="usuario" class="form-label fw-semibold">
            <i class="bi bi-person-fill me-1"></i> Usuario
          </label>
          <input type="text" name="usuario" id="usuario" placeholder="Digite Usuario" class="form-control rounded-pill" required>
        </div>

        <div class="mb-3">
          <label for="contrasena" class="form-label fw-semibold">
            <i class="bi bi-lock-fill me-1"></i> Contraseña
          </label>
          <input type="password" name="contrasena" id="contrasena" placeholder="Digite Contraseña" class="form-control rounded-pill" required>
        </div>

        <!-- reCAPTCHA -->
        <div class="g-recaptcha text-center mb-3" data-sitekey="6LdKlQIsAAAAABS3CuqPQgjQzMTBlBC3_QWckssf"></div>

        <button type="submit" name="inicio" class="btn-orange w-100 rounded-pill py-2 fw-semibold">Iniciar sesión</button>
      </form>

      <div class="options mt-4 text-center">
        <p><a href="views/soporte/ayuda_login.php">¿Necesitas ayuda?</a></p>
        <p><a href="recordar_contrasena.php">¿Olvidaste tu contraseña?</a></p>
        <p>O</p>
        <div class="d-flex justify-content-center gap-3 fs-3">
          <a href="https://store.steampowered.com/?l=spanish" class="text-light"><i class="bi bi-steam"></i></a>
          <a href="https://www.xbox.com/es-ES" class="text-success"><i class="bi bi-xbox"></i></a>
          <a href="https://www.playstation.com/es-es/" class="text-primary"><i class="bi bi-playstation"></i></a>
          <a href="https://www.battle.net/" class="text-info"><i class="bi bi-globe2"></i> Battle.net</a>
        </div>
        <p class="mt-3">¿Eres nuevo en Call of Duty? <a href="registro.php">Regístrate</a></p>
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
    <a href="views/seguridad/seguridad_linea.php">SEGURIDAD EN LÍNEA</a> /
    <a href="views/soporte/apoyo_ayuda.php">APOYO</a>
  </footer>
</body>

</html>