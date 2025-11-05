<?php
require_once __DIR__ . '/../../db/conexion.php';

$db = new Database();
$con = $db->conectar();

$id_partida = $_GET['id_partida'] ?? 0;

if ($id_partida) {
    $stmt = $con->prepare("
        UPDATE partidas 
        SET estado = 'en_curso', fecha_inicio = NOW() 
        WHERE id_partida = :id
    ");
    $stmt->execute([':id' => $id_partida]);

    header("Location: partida.php?id_partida=$id_partida");
    exit;
}
echo "Error: partida no válida.";
