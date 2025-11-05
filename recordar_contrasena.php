<?php
session_start();
require_once("db/conexion.php");
$db = new Database;
$con = $db->conectar();

if (isset($_POST['validar'])) {
    $usuario = trim($_POST["usuario"]);
    $email = trim($_POST["email"]);
    $recaptcha = $_POST['g-recaptcha-response'] ?? '';

    if (empty($usuario) || empty($email)) {
        echo '<script>alert("Por favor, completa todos los campos.");</script>';
    } elseif (empty($recaptcha)) {
        echo '<script>alert("Por favor, completa la verificación reCAPTCHA.");</script>';
    } else {
        // Verificación reCAPTCHA (lado del servidor)
        $secretKey = "6LdKlQIsAAAAAGEUG3Hkj5mqrcW9F9qi4YR9xV1O";
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptcha");
        $responseKeys = json_decode($response, true);

        if (!$responseKeys["success"]) {
            echo '<script>alert("Error en la verificación del reCAPTCHA.");</script>';
            exit;
        }

        // Buscar usuario por nombre y correo
        $sql = $con->prepare("SELECT id_usuario, nombre_usuario, email FROM usuarios WHERE nombre_usuario = ? AND email = ?");
        $sql->execute([$usuario, $email]);
        $fila = $sql->fetch(PDO::FETCH_ASSOC);

        // Obtener IP y ubicación
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'desconocido';
        $ubicacion = 'Desconocida';

        try {
            $respuesta = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city");
            if ($respuesta) {
                $data = json_decode($respuesta, true);
                if ($data["status"] === "success") {
                    $ubicacion = "{$data['country']}, {$data['regionName']}, {$data['city']}";
                }
            }
        } catch (Exception $e) {
            $ubicacion = 'Desconocida';
        }

        if ($fila) {
            // Generar token y datos
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));
            $creado = date("Y-m-d H:i:s");

            // Insertar token nuevo
            $insert = $con->prepare("
                INSERT INTO password_reset_tokens (id_usuario, token_hash, created_at, expires_at, used, ip_request, user_agent)
                VALUES (?, ?, ?, ?, 0, ?, ?)
            ");
            $insert->execute([$fila['id_usuario'], $tokenHash, $creado, $expira, $ip, $userAgent]);

            // Registrar log exitoso con ubicación
            $log = $con->prepare("
                INSERT INTO password_reset_logs (email, id_usuario, ip, ubicacion, success, fecha)
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $log->execute([$email, $fila['id_usuario'], $ip, $ubicacion]);

            // Enlace de recuperación (automático según host)
            $host = $_SERVER['HTTP_HOST'];
            $path = dirname($_SERVER['PHP_SELF']);
            $link = "http://$host$path/nueva_contrasena.php?token=$token";

            // Enviar correo
            $to = $email;
            $subject = "Recuperar contraseña - Call of Duty";
            $headers = "From: no-reply@tusitio.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            $message = "
                <html>
                <body>
                    <p>Hola <b>{$fila['nombre_usuario']}</b>,</p>
                    <p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p>
                    <p><a href='$link'>$link</a></p>
                    <p>Este enlace expira en 1 hora.</p>
                    <hr>
                    <p><small>Solicitud realizada desde: $ubicacion ($ip)</small></p>
                </body>
                </html>
            ";

            if (mail($to, $subject, $message, $headers)) {
                echo "<script>alert('Se ha enviado un correo con instrucciones para recuperar la contraseña. Revisa tu bandeja de entrada o spam.');</script>";
            } else {
                echo "<script>alert('Error al enviar el correo. Verifica la configuración del servidor.');</script>";
            }
        } else {
            // Usuario no encontrado (también registra ubicación)
            $log = $con->prepare("
                INSERT INTO password_reset_logs (email, ip, ubicacion, success, fecha)
                VALUES (?, ?, ?, 0, NOW())
            ");
            $log->execute([$email, $ip, $ubicacion]);
            echo '<script>alert("Usuario o correo no encontrados.");</script>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - Call of Duty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body>
    <div class="container">
        <div class="login-box">
            <a class="navbar-brand" href="index.html">CALL OF DUTY</a>
            <h2>Recuperar contraseña de tu cuenta Activision</h2>

            <form action="" method="POST">
                <div class="mb-3">
                    <label for="usuario" class="form-label fw-semibold">
                        <i class="bi bi-person-fill me-1"></i>Nombre de usuario
                    </label>
                    <input type="text" name="usuario" id="usuario" placeholder="Digite Usuario" class="form-control rounded-pill">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">
                        <i class="bi bi-envelope-fill me-1"></i>Correo electrónico
                    </label>
                    <input type="email" name="email" id="email" placeholder="Ej: usuario@gmail.com" class="form-control rounded-pill">
                </div>

                <div class="g-recaptcha text-center mb-3" data-sitekey="6LdKlQIsAAAAABS3CuqPQgjQzMTBlBC3_QWckssf"></div>

                <button type="submit" name="validar" class="btn-orange w-100 rounded-pill py-2 fw-semibold">Validar</button>

                <div class="options mt-3 text-center">
                    <p><a href="#">¿Necesitas ayuda?</a></p>
                    <p>O</p>
                    <p>¿Volver a iniciar sesión? <a href="login.php">Loguearse</a></p>
                </div>
        </div>

        <div class="bg-imagen3"></div>
    </div>
</body>

</html>