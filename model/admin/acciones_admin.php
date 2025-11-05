<?php
session_start();
require_once("../../db/conexion.php");

header('Content-Type: application/json');

// Verificación de sesión y rol
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Acceso no autorizado."]);
    exit();
}

$db = new Database();
$con = $db->conectar();

$id_admin  = $_SESSION['id_usuario'];
$id_usuario = $_POST['id_usuario'] ?? null;
$accion     = $_POST['accion'] ?? null;

// Validar parámetros
if (!$id_usuario || !$accion) {
    echo json_encode(["status" => "error", "message" => "Faltan datos para ejecutar la acción."]);
    exit();
}

// Mapeo de acciones a estados
$mapa_estados = [
    "activar"   => 1,
    "inactivar" => 2,
    "bloquear"  => 3,
    "banear"    => 4,
    "pendiente" => 5
];

// Validar acción
if (!array_key_exists($accion, $mapa_estados)) {
    echo json_encode(["status" => "error", "message" => "Acción no válida."]);
    exit();
}

$id_estado_nuevo = $mapa_estados[$accion];

try {
    // Verificar que el usuario existe
    $check = $con->prepare("SELECT id_usuario, id_estado FROM usuarios WHERE id_usuario = ?");
    $check->execute([$id_usuario]);
    $usuario = $check->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo json_encode(["status" => "error", "message" => "El usuario no existe."]);
        exit();
    }

    // Si el estado actual ya es el mismo, no hacer nada
    if ((int)$usuario['id_estado'] === (int)$id_estado_nuevo) {
        echo json_encode(["status" => "error", "message" => "El usuario ya está en ese estado."]);
        exit();
    }

    // Actualizar estado del usuario
    $update = $con->prepare("UPDATE usuarios SET id_estado = ? WHERE id_usuario = ?");
    $update->execute([$id_estado_nuevo, $id_usuario]);

    // Registrar acción en historial
    $motivo = ucfirst($accion) . " al usuario";
    $insert = $con->prepare("
        INSERT INTO historial_acciones (id_admin, id_usuario, accion, motivo, fecha)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $insert->execute([$id_admin, $id_usuario, $accion, $motivo]);

    echo json_encode([
        "status" => "ok",
        "message" => " Acción '{$accion}' ejecutada correctamente y registrada en el historial."
    ]);
} catch (PDOException $e) {
    error_log("Error en acciones_admin.php: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Error interno al procesar la solicitud."]);
}
