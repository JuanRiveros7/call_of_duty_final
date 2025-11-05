<?php
session_start();
require_once __DIR__ . '/../../db/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../../login.php");
    exit;
}

$id_partida = $_GET['id_partida'] ?? 0;
$id_usuario = $_SESSION['id_usuario'];

$db = new Database();
$con = $db->conectar();

// Verificar que la partida exista
$stmt = $con->prepare("SELECT * FROM partidas WHERE id_partida = :id");
$stmt->execute([':id' => $id_partida]);
$partida = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$partida) {
    die("Partida no encontrada.");
}

// Unir jugador si no está ya dentro
$stmt = $con->prepare("
    SELECT 1 FROM partida_jugadores WHERE id_partida = :id_partida AND id_usuario = :id_usuario
");
$stmt->execute([':id_partida' => $id_partida, ':id_usuario' => $id_usuario]);
if ($stmt->rowCount() === 0) {
    $stmt = $con->prepare("
        INSERT INTO partida_jugadores (id_partida, id_usuario, id_personaje, id_arma, salud_actual, estado)
        VALUES (:id_partida, :id_usuario, 1, 1, 100, 'vivo')
    ");
    $stmt->execute([':id_partida' => $id_partida, ':id_usuario' => $id_usuario]);
}

// Contar jugadores en la partida
$stmt = $con->prepare("
    SELECT COUNT(*) AS total FROM partida_jugadores WHERE id_partida = :id_partida
");
$stmt->execute([':id_partida' => $id_partida]);
$total_jugadores = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// --- Lógica de cuenta regresiva ---
$tiempoRestante = 0;
if ($total_jugadores >= 2) {
    if (!$partida['fecha_inicio']) {
        $fecha_inicio = date('Y-m-d H:i:s', time() + 60);
        $stmt = $con->prepare("
            UPDATE partidas SET fecha_inicio = :fecha_inicio, estado = 'iniciando' WHERE id_partida = :id_partida
        ");
        $stmt->execute(['fecha_inicio' => $fecha_inicio, 'id_partida' => $id_partida]);
        $partida['fecha_inicio'] = $fecha_inicio;
    }
    $tiempoRestante = strtotime($partida['fecha_inicio']) - time();
    if ($tiempoRestante < 0) $tiempoRestante = 0;
}

// --- Endpoint AJAX para verificar total de jugadores ---
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $stmt = $con->prepare("SELECT COUNT(*) AS total FROM partida_jugadores WHERE id_partida = :id_partida");
    $stmt->execute(['id_partida' => $id_partida]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Sala #<?= $id_partida ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        let tiempoRestante = <?= $tiempoRestante ?>;
        let totalJugadores = <?= $total_jugadores ?>;
        let intervaloContador;

        function actualizarContador() {
            if (tiempoRestante > 0) {
                document.getElementById('contador').innerText = tiempoRestante;
                tiempoRestante--;
            } else if (totalJugadores >= 2) {
                clearInterval(intervaloContador);
                window.location.href = "iniciar_partida.php?id_partida=<?= $id_partida ?>";
            }
        }

        function revisarJugadores() {
            fetch('sala.php?id_partida=<?= $id_partida ?>&ajax=1')
                .then(res => res.json())
                .then(data => {
                    totalJugadores = data.total;
                    if (totalJugadores >= 2 && tiempoRestante === 0) {
                        location.reload();
                    }
                });
        }

        intervaloContador = setInterval(actualizarContador, 1000);
        setInterval(revisarJugadores, 1000);
        window.onload = actualizarContador;
    </script>
</head>

<body class="bg-dark text-light" style="background-image: url('../../assets/img/fondo_sala.php.png'); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <div class="container py-5 d-flex justify-content-center align-items-center min-vh-100">
        <div class="card bg-secondary bg-opacity-25 border-light text-center shadow-lg" style="max-width: 500px; width: 100%;">
            <div class="card-body">
                <h2 class="card-title text-warning mb-3">🎮 Sala #<?= $id_partida ?></h2>
                <p class="fs-5">Jugadores actuales: <strong><?= $total_jugadores ?>/<?= $partida['jugadores_max'] ?></strong></p>

                <?php if ($total_jugadores >= 2 && $tiempoRestante > 0): ?>
                    <div class="alert alert-info mt-3">
                        La partida comenzará en <span id="contador" class="fw-bold"><?= $tiempoRestante ?></span> segundos...
                    </div>

                <?php else: ?>
                    <div class="alert alert-secondary mt-3">
                        Esperando más jugadores...
                    </div>

                    <!-- Spinner de carga -->
                    <div class="d-flex justify-content-center mt-3">
                        <div class="spinner-border text-light" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="progress mt-4" style="height: 20px;">
                    <div class="progress-bar bg-success" role="progressbar"
                        style="width: <?= ($total_jugadores / $partida['jugadores_max']) * 100 ?>%;"
                        aria-valuenow="<?= $total_jugadores ?>" aria-valuemin="0" aria-valuemax="<?= $partida['jugadores_max'] ?>">
                        <?= $total_jugadores ?>/<?= $partida['jugadores_max'] ?>
                    </div>
                </div>

                <a href="salas.php?id_mapa=<?= $partida['id_mapa'] ?>" class="btn btn-outline-light mt-4">⬅ Volver</a>
            </div>
        </div>
    </div>
</body>

</html>