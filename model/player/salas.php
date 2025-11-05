<?php
session_start();
require_once __DIR__ . '/../../db/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../../login.php");
    exit;
}

$id_mapa = $_GET['id_mapa'] ?? 0;
if (!$id_mapa) {
    die("Mapa no válido.");
}

$db = new Database();
$con = $db->conectar();

// --- Reiniciar partidas finalizadas o en curso ---
function resetPartidas($con)
{
    $query = "SELECT id_partida FROM partidas WHERE LOWER(estado) IN ('finalizada', 'en_curso')";
    $stmt = $con->query($query);
    $partidas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($partidas as $id_partida) {
        $delete = $con->prepare("DELETE FROM partida_jugadores WHERE id_partida = :id");
        $delete->execute([':id' => $id_partida]);

        $update = $con->prepare("
            UPDATE partidas
            SET estado = 'esperando',
                fecha_inicio = NULL,
                fecha_fin = NULL,
                nivel_minimo = NULL,
                nivel_maximo = NULL,
                rango_minimo = NULL,
                rango_maximo = NULL
            WHERE id_partida = :id
        ");
        $update->execute([':id' => $id_partida]);
    }

    return $partidas ?? [];
}

$salasReiniciadas = resetPartidas($con);

// --- Obtener información del mapa ---
$stmt = $con->prepare("SELECT nombre_mapa, modo_juego, descripcion FROM mapas WHERE id_mapa = :id");
$stmt->execute([':id' => $id_mapa]);
$mapa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mapa) {
    die("Mapa no encontrado.");
}

// --- Obtener las salas de este mapa ---
$stmt = $con->prepare("
    SELECT p.id_partida, p.estado, p.jugadores_max,
           COUNT(pj.id_usuario) AS jugadores_actuales
    FROM partidas p
    LEFT JOIN partida_jugadores pj ON pj.id_partida = p.id_partida
    WHERE p.id_mapa = :id_mapa
    GROUP BY p.id_partida
    ORDER BY p.id_partida ASC
");
$stmt->execute([':id_mapa' => $id_mapa]);
$salas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Salas - <?= htmlspecialchars($mapa['nombre_mapa']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">
    <div class="overlay">
        <div class="container py-5">

            <div class="text-center mb-5">
                <h2 class="text-warning fw-bold">Salas en <?= htmlspecialchars($mapa['nombre_mapa']) ?></h2>
                <p class="text-secondary">Modo: <?= htmlspecialchars($mapa['modo_juego']) ?></p>
                <a href="mapas.php" class="btn btn-outline-light fw-bold">⬅ Volver a Mapas</a>
            </div>

            <?php if (!empty($salasReiniciadas)): ?>
                <div class="alert alert-success text-center">
                    💡 Se reiniciaron <?= count($salasReiniciadas) ?> sala<?= count($salasReiniciadas) > 1 ? 's' : '' ?> y se limpiaron sus jugadores.
                </div>
            <?php endif; ?>

            <div class="row g-4 justify-content-center">
                <?php foreach ($salas as $sala): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card text-center border-warning shadow-lg h-100">
                            <div class="card-body">
                                <h5 class="text-warning mb-3">Sala #<?= $sala['id_partida'] ?></h5>
                                <p class="mb-1">👥 Jugadores: <?= $sala['jugadores_actuales'] ?>/<?= $sala['jugadores_max'] ?></p>
                                <p class="mb-3">⚔️ Estado: <span class="text-capitalize"><?= htmlspecialchars($sala['estado']) ?></span></p>
                                <a href="sala.php?id_partida=<?= $sala['id_partida'] ?>" class="btn btn-warning fw-bold px-4">Entrar</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <style>
        body {
            background-image: url('../../assets/img/fondos/fondo_salas.jpeg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: 'Segoe UI', sans-serif;
            color: #f8f9fa;
        }

        .overlay {
            background: rgba(0, 0, 0, 0.75);
            min-height: 100vh;
            padding: 60px 0;
            backdrop-filter: blur(5px);
        }

        h2.text-warning {
            font-weight: 800;
            letter-spacing: 1.5px;
            text-shadow: 0 0 25px rgba(255, 193, 7, 0.8);
            color: #ffc107;
        }

        .text-secondary {
            font-size: 1.1rem;
            color: #ddd !important;
        }

        .card {
            background: rgba(40, 40, 40, 0.55);
            border: 1px solid rgba(255, 193, 7, 0.5);
            border-radius: 14px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 0 25px rgba(255, 193, 7, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 0 35px rgba(255, 193, 7, 0.5);
            border-color: #ffc107;
        }

        .card-body {
            padding: 25px;
        }

        .card h5 {
            color: #ffc107;
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }

        .card p {
            margin: 5px 0;
            color: #f1f1f1;
            font-size: 1rem;
        }

        .btn-warning {
            background: linear-gradient(90deg, #ffca2c, #ffc107);
            color: #000;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
        }

        .btn-warning:hover {
            background: linear-gradient(90deg, #ffd84d, #ffca2c);
            box-shadow: 0 0 25px rgba(255, 193, 7, 0.9);
            transform: scale(1.08);
        }

        .btn-outline-light {
            border: 2px solid #fff;
            border-radius: 10px;
            font-weight: 600;
            padding: 8px 18px;
            transition: all 0.3s ease;
        }

        .btn-outline-light:hover {
            background-color: #fff;
            color: #000;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.6);
        }
    </style>
</body>

</html>