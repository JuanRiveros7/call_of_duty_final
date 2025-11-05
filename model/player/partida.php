<?php
session_start();
require_once __DIR__ . '/../../db/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../../login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$id_partida = isset($_GET['id_partida']) ? (int)$_GET['id_partida'] : 0;

$db = new Database();
$con = $db->conectar();

// ======= RESPONDER AJAX =======
if (isset($_GET['action']) && $_GET['action'] === 'estado_jugadores') {
    $stmt = $con->prepare("
        SELECT pj.id_usuario, u.nombre_usuario, pj.salud_actual, pj.estado
        FROM partida_jugadores pj
        INNER JOIN usuarios u ON pj.id_usuario = u.id_usuario
        WHERE pj.id_partida = :id_partida
    ");
    $stmt->execute([':id_partida' => $id_partida]);
    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($jugadores);
    exit;
}

// ======= FIN AJAX =======

// Verificar si la partida existe
$stmt = $con->prepare("SELECT * FROM partidas WHERE id_partida = :id");
$stmt->execute([':id' => $id_partida]);
$partida = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partida) {
    die("Partida no encontrada.");
}

// Función para obtener jugadores y su estado actualizado
function obtenerJugadores($con, $id_partida)
{
    $stmt = $con->prepare("
        SELECT 
            u.id_usuario,
            u.nombre_usuario,
            pj.salud_actual,
            pj.estado,
            a.url_imagen AS avatar
        FROM partida_jugadores pj
        INNER JOIN usuarios u ON pj.id_usuario = u.id_usuario
        LEFT JOIN avatar a ON u.id_avatar = a.id_avatar
        WHERE pj.id_partida = :id_partida
    ");
    $stmt->execute([':id_partida' => $id_partida]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$jugadores = obtenerJugadores($con, $id_partida);

// Obtener jugador actual
$jugadorActual = array_filter($jugadores, fn($j) => $j['id_usuario'] == $id_usuario);
$jugadorActual = array_values($jugadorActual)[0] ?? null;

// Obtener armas disponibles según el nivel
$stmt = $con->prepare("
    SELECT id_arma, nombre, dano 
    FROM armas 
    WHERE nivel_requerido <= (SELECT id_nivel FROM usuarios WHERE id_usuario = :id_usuario)
");
$stmt->execute([':id_usuario' => $id_usuario]);
$armas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Zonas de daño (de la tabla zonas_dano)
$stmt = $con->query("SELECT id_zona, nombre, multiplicador FROM zonas_dano");
$zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Combate - Partida #<?= $id_partida ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-warning" style="background-image: url('../../assets/img/fpvp.png'); background-size: cover; background-position: center; background-repeat: no-repeat;">

    <div class="container mt-4">
        <h2 class="text-warning mb-4 text-center">🔥 Partida #<?= $id_partida ?> - Combate 🔥</h2>

        <!-- Jugadores en la partida -->
        <div class="row mb-4" id="jugadores-container">
            <h4 class="text-center text-light">Jugadores en la partida</h4>
            <?php foreach ($jugadores as $j): ?>
                <div class="col-md-3 mb-3" data-id="<?= $j['id_usuario'] ?>">
                    <div class="card bg-dark bg-opacity-50 text-center border-light shadow">
                        <div class="card-body text-light">
                            <?php if (!empty($j['avatar'])): ?>
                                <img src="../../<?= htmlspecialchars($j['avatar']) ?>" alt="Avatar" class="rounded-circle mb-2" width="80" height="80">
                            <?php endif; ?>
                            <h5 class="text-warning"><?= htmlspecialchars($j['nombre_usuario']) ?></h5>
                            <p>💚 Vida: <span class="vida"><?= htmlspecialchars($j['salud_actual']) ?></span>%</p>
                            <?php if ($j['estado'] == 'eliminado'): ?>
                                <span class="badge bg-danger estado-badge">Eliminado</span>
                            <?php else: ?>
                                <span class="badge bg-success estado-badge">Vivo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <hr class="border-warning">

        <!-- Formulario de disparo -->
        <h4 class="text-warning mb-3">🎯 Realizar disparo</h4>
        <form method="POST" action="procesar_disparo.php" id="form-disparo">
            <input type="hidden" name="id_partida" value="<?= $id_partida ?>">

            <div class="row opacity-75">
                <!-- Objetivo -->
                <div class="col-md-4 mb-3">
                    <label class="form-label text-light">Seleccionar objetivo</label>
                    <select class="form-select" name="objetivo" required id="select-objetivo">
                        <option value="">-- Selecciona un jugador --</option>
                        <?php foreach ($jugadores as $j): ?>
                            <?php if ($j['id_usuario'] != $id_usuario && $j['estado'] != 'eliminado'): ?>
                                <option value="<?= $j['id_usuario'] ?>"><?= htmlspecialchars($j['nombre_usuario']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Zona de impacto -->
                <div class="col-md-4 mb-3">
                    <label class="form-label text-light">Zona de impacto</label>
                    <select class="form-select" name="zona" required>
                        <?php foreach ($zonas as $z): ?>
                            <option value="<?= $z['id_zona'] ?>"><?= htmlspecialchars($z['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Arma -->
                <div class="col-md-4 mb-3">
                    <label class="form-label text-light">Arma</label>
                    <select class="form-select" name="arma" required>
                        <?php foreach ($armas as $a): ?>
                            <option value="<?= $a['id_arma'] ?>"><?= htmlspecialchars($a['nombre']) ?> (Daño: <?= $a['dano'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-danger btn-lg mt-3" id="btn-disparo">💥 Disparar</button>
            </div>
        </form>

        <div class="mt-4 text-center">
            <a href="lobby.php" class="btn btn-outline-light"> Salir de la Partida</a>
        </div>
    </div>

    <script>
        const id_partida = <?= $id_partida ?>;
        const id_usuario = <?= $id_usuario ?>;
        const btnDisparo = document.getElementById('btn-disparo');
        const selectObjetivo = document.getElementById('select-objetivo');

        function actualizarEstadoJugadores() {
            fetch(`partida.php?id_partida=${id_partida}&action=estado_jugadores`)
                .then(res => res.json())
                .then(data => {
                    let jugadorVivo = true;
                    selectObjetivo.innerHTML = '<option value="">-- Selecciona un jugador --</option>';
                    data.forEach(j => {
                        const card = document.querySelector('[data-id="' + j.id_usuario + '"]');
                        if (card) {
                            card.querySelector('.vida').textContent = j.salud_actual;
                            const badge = card.querySelector('.estado-badge');
                            if (j.estado === 'eliminado') {
                                badge.textContent = 'Eliminado';
                                badge.className = 'badge bg-danger estado-badge';
                            } else {
                                badge.textContent = 'Vivo';
                                badge.className = 'badge bg-success estado-badge';
                            }
                        }
                        if (j.id_usuario === id_usuario && j.estado === 'eliminado') {
                            jugadorVivo = false;
                        }
                        if (j.id_usuario !== id_usuario && j.estado !== 'eliminado') {
                            selectObjetivo.innerHTML += `<option value="${j.id_usuario}">${j.nombre_usuario}</option>`;
                        }
                    });
                    btnDisparo.disabled = !jugadorVivo;
                    btnDisparo.style.opacity = jugadorVivo ? 1 : 0.5;
                })
                .catch(err => console.error(err));
        }

        // Actualizar cada 2 segundos
        setInterval(actualizarEstadoJugadores, 2000);

        document.getElementById('form-disparo').addEventListener('submit', e => {
            if (btnDisparo.disabled) {
                e.preventDefault();
                alert("⚠️ No puedes disparar, estás eliminado.");
            }
        });
    </script>
</body>

</html>