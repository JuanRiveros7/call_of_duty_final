<?php
require_once __DIR__ . '/../../db/conexion.php';
$db = new Database();
$con = $db->conectar();

$id_partida = $_GET['id_partida'] ?? 0;

$stmt = $con->prepare("
    SELECT u.nombre_usuario, u.avatar 
    FROM partida_jugadores pj
    JOIN usuarios u ON pj.id_usuario = u.id
    WHERE pj.id_partida = ?
");
$stmt->execute([$id_partida]);
$jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$html = '';
foreach ($jugadores as $j) {
    $html .= '
    <div class="col-md-3 mb-3">
        <div class="card bg-secondary text-center border border-warning">
            <img src="../../' . htmlspecialchars($j['avatar'] ?? 'img/default_avatar.png') . '" 
                 class="rounded-circle mt-3" width="80" height="80">
            <div class="card-body">
                <h5 class="text-warning">' . htmlspecialchars($j['nombre_usuario']) . '</h5>
            </div>
        </div>
    </div>';
}

$estado = $con->prepare("SELECT estado FROM partidas WHERE id_partida = ?");
$estado->execute([$id_partida]);
$estado_partida = $estado->fetchColumn();

echo json_encode([
    "html" => $html,
    "count" => count($jugadores),
    "estado" => $estado_partida
]);
