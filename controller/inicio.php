<?php
session_start();
require_once("../db/conexion.php");
$db = Database::conectar();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (empty($_POST['usuario']) || empty($_POST['password'])) {
        header("Location: ../errorlog.php?error=campos_vacios");
        exit;
    }

    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];

    try {
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
        $stmt->bindParam(":usuario", $usuario, PDO::PARAM_STR);
        $stmt->execute();
        $resultado = $stmt->fetch();

        if ($resultado && password_verify($password, $resultado['password'])) {

            $_SESSION['id_usuario'] = $resultado['id'];
            $_SESSION['usuario'] = $resultado['usuario'];
            $_SESSION['rol'] = $resultado['rol'];

            switch ($resultado['rol']) {
                case 'admin':
                    header("Location: ../model/admin/index_admin.php");
                    break;
                case 'player':
                    header("Location: ../model/player/index_player.php");
                    break;
                default:
                    header("Location: ../errorlog.php?error=rol_desconocido");
                    break;
            }
            exit;
        } else {
            header("Location: ../errorlog.php?error=credenciales_invalidas");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error al iniciar sesión: " . $e->getMessage());
        header("Location: ../errorlog.php?error=conexion");
        exit;
    }
}
