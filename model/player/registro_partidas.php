<?php
session_start();
require_once("../../db/conexion.php");

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../../login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$db = new Database();
$con = $db->conectar();

// Obtener las partidas registradas del jugador
$stmt = $con->prepare("
    SELECT 
        rp.id_reporte,
        rp.id_partida,
        rp.id_mapa,
        m.nombre_mapa,
        rp.modo_juego,
        rp.resultado,
        rp.kills,
        rp.dano_causado,
        rp.dano_recibido,
        rp.puntos_obtenidos,
        rp.nivel_al_jugar,
        rp.duracion_segundos,
        rp.fecha_partida
    FROM reporte_partidas rp
    INNER JOIN mapas m ON rp.id_mapa = m.id_mapa
    WHERE rp.id_usuario = :id_usuario
    ORDER BY rp.fecha_partida DESC
");
$stmt->execute([':id_usuario' => $id_usuario]);
$partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro de Partidas - Call of Duty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">
    <div class="container mt-5">
        <h2 class="text-warning text-center mb-4">📜 Registro de Partidas</h2>

        <?php if (empty($partidas)): ?>
            <div class="alert alert-warning text-center">Aún no tienes partidas registradas.</div>
        <?php else: ?>
            <table class="table table-dark table-striped text-center align-middle">
                <thead class="table-warning text-dark">
                    <tr>
                        <th>ID</th>
                        <th>Mapa</th>
                        <th>Modo</th>
                        <th>Resultado</th>
                        <th>Kills</th>
                        <th>Daño Causado</th>
                        <th>Daño Recibido</th>
                        <th>Puntos</th>
                        <th>Nivel</th>
                        <th>Duración</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($partidas as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['id_partida']) ?></td>
                            <td><?= htmlspecialchars($p['nombre_mapa']) ?></td>
                            <td><?= htmlspecialchars($p['modo_juego']) ?></td>
                            <td><?= htmlspecialchars($p['resultado']) ?></td>
                            <td><?= htmlspecialchars($p['kills']) ?></td>
                            <td><?= htmlspecialchars($p['dano_causado']) ?></td>
                            <td><?= htmlspecialchars($p['dano_recibido']) ?></td>
                            <td><?= htmlspecialchars($p['puntos_obtenidos']) ?></td>
                            <td><?= htmlspecialchars($p['nivel_al_jugar']) ?></td>
                            <td><?= htmlspecialchars($p['duracion_segundos']) ?>s</td>
                            <td><?= date("d/m/Y H:i", strtotime($p['fecha_partida'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="lobby.php" class="btn btn-outline-warning">⬅️ Volver al Lobby</a>
        </div>
    </div>
</body>

</html>