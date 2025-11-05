<?php
session_start();
require_once("db/conexion.php");
$db = new Database;
$con = $db->conectar();

// Habilitar modo de errores de PDO (mejor manejo de excepciones)
$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Variables para mostrar alertas dentro del HTML
$mensaje = "";
$tipo_alerta = "";

if (isset($_POST['registro'])) {
  // Sanitización de entradas
  $usuario    = htmlspecialchars(trim($_POST['nombre_usuario'] ?? ''), ENT_QUOTES, 'UTF-8');
  $email      = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
  $contrasena = trim($_POST['contrasena'] ?? '');
  $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

  // Validar campos vacíos
  if (empty($usuario) || empty($email) || empty($contrasena)) {
    $mensaje = " Por favor, complete todos los campos.";
    $tipo_alerta = "warning";
  }

  // Validar formato del nombre de usuario
  elseif (!preg_match("/^[a-zA-Z0-9_]{4,20}$/", $usuario)) {
    $mensaje = "El nombre de usuario debe tener entre 4 y 20 caracteres alfanuméricos.";
    $tipo_alerta = "warning";
  }

  // Validar formato del correo electrónico
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $mensaje = " Por favor, ingrese un correo electrónico válido.";
    $tipo_alerta = "warning";
  }

  // Validar longitud mínima de la contraseña
  elseif (strlen($contrasena) < 8) {
    $mensaje = " La contraseña debe tener al menos 8 caracteres.";
    $tipo_alerta = "warning";
  }

  // Validar reCAPTCHA
  else {
    // Recomendado: guardar esta clave en un archivo .env
    $secretKey = "6LdKlQIsAAAAAGEUG3Hkj5mqrcW9F9qi4YR9xV1O";
    $verifyUrl = "https://www.google.com/recaptcha/api/siteverify";

    $response = @file_get_contents("$verifyUrl?secret=$secretKey&response=$recaptcha_response");
    $responseKeys = json_decode($response, true);

    if (empty($responseKeys) || !$responseKeys["success"]) {
      $mensaje = " Verifica el reCAPTCHA antes de continuar.";
      $tipo_alerta = "danger";
    } else {
      try {
        // Verificar si el usuario o correo ya existen
        $sql = $con->prepare("SELECT id_usuario FROM usuarios WHERE nombre_usuario = ? OR email = ?");
        $sql->execute([$usuario, $email]);

        if ($sql->fetch()) {
          $mensaje = " El usuario o el correo ya están registrados.";
          $tipo_alerta = "danger";
        } else {
          // Encriptar la contraseña
          $hash = password_hash($contrasena, PASSWORD_DEFAULT);

          // Asignar valores por defecto
          $id_rol = 2;        // Usuario normal
          $id_estado = 2;     // Estado: bloqueado o pendiente

          // Insertar nuevo usuario
          $insert = $con->prepare("INSERT INTO usuarios 
            (nombre_usuario, email, contrasena, id_rol, id_estado)
            VALUES (?, ?, ?, ?, ?)");

          $ok = $insert->execute([$usuario, $email, $hash, $id_rol, $id_estado]);

          if ($ok) {
            $_SESSION['mensaje'] = " Registro exitoso. Tu cuenta está bloqueada hasta que un administrador la active.";
            $_SESSION['tipo_alerta'] = "success";

            // Limpieza de datos POST y redirección segura
            unset($_POST);
            header("Location: login.php");
            exit;
          } else {
            $mensaje = " Error al registrar el usuario. Inténtalo de nuevo más tarde.";
            $tipo_alerta = "danger";
          }
        }
      } catch (PDOException $e) {
        // Se registra el error en el log del servidor sin mostrarlo al usuario
        error_log("Error en registro.php: " . $e->getMessage());
        $mensaje = " Error interno. Por favor, inténtelo más tarde.";
        $tipo_alerta = "danger";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro - Call of Duty</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body>
  <div class="container">
    <div class="login-box">
      <a class="navbar-brand" href="index.html">CALL OF DUTY</a>
      <h2>Regístrate para crear tu cuenta de Activision</h2>

      <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($tipo_alerta); ?> alert-dismissible fade show mt-3" role="alert">
          <?php echo htmlspecialchars($mensaje); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <form action="registro.php" method="POST" class="mt-3">

        <div class="mb-3">
          <label for="nombre_usuario" class="form-label fw-semibold">
            <i class="bi bi-person-fill me-1"></i>Nombre de usuario
          </label>
          <input
            type="text"
            name="nombre_usuario"
            id="nombre_usuario"
            placeholder="Digita tu nombre de usuario"
            class="form-control rounded-pill"
            value="<?= htmlspecialchars($usuario ?? '') ?>"
            required>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label fw-semibold">
            <i class="bi bi-envelope-fill me-1"></i>Email
          </label>
          <input
            type="email"
            name="email"
            id="email"
            placeholder="Ejemplo: call@gmail.com"
            class="form-control rounded-pill"
            value="<?= htmlspecialchars($email ?? '') ?>"
            required>
        </div>

        <div class="mb-3">
          <label for="contrasena" class="form-label fw-semibold">
            <i class="bi bi-lock-fill me-1"></i>Contraseña
          </label>
          <input
            type="password"
            name="contrasena"
            id="contrasena"
            placeholder="Digita una contraseña segura"
            class="form-control rounded-pill"
            required>
        </div>

        <!-- reCAPTCHA -->
        <div class="g-recaptcha text-center mb-3" data-sitekey="6LdKlQIsAAAAABS3CuqPQgjQzMTBlBC3_QWckssf"></div>

        <button type="submit" name="registro" class="btn-orange w-100 rounded-pill py-2 fw-semibold">Registrarse</button>
      </form>

      <div class="options mt-4 text-center">
        <p><a href="views/soporte/ayuda_registro.php">¿Necesitas ayuda para registrarte?</a></p>
        <p>O</p>
        <div class="d-flex justify-content-center gap-3 fs-3">
          <a href="https://store.steampowered.com/?l=spanish" class="text-light"><i class="bi bi-steam"></i></a>
          <a href="https://www.xbox.com/es-ES" class="text-success"><i class="bi bi-xbox"></i></a>
          <a href="https://www.playstation.com/es-es/" class="text-primary"><i class="bi bi-playstation"></i></a>
          <a href="https://www.battle.net/" class="text-info"><i class="bi bi-globe2"></i> Battle.net</a>
        </div>
        <p class="mt-3">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
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