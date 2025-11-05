<?php
session_start();
require_once __DIR__ . '../../../db/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../../../login.php");
    exit;
}

$db = new Database();
$con = $db->conectar();

$id_usuario = $_SESSION['id_usuario'];

// Datos del usuario, personaje y arma
$sql = "
    SELECT 
        u.nombre_usuario,
        u.puntos_totales,
        n.nombre_nivel,
        u.id_personaje,
        u.id_arma,
        a.url_imagen,
        p.nombre AS nombre_personaje,
        p.imagen AS imagen_personaje,
        ar.nombre AS nombre_arma,
        ar.imagen AS imagen_arma
    FROM usuarios u
    INNER JOIN niveles n ON u.id_nivel = n.id_nivel
    LEFT JOIN avatar a ON u.id_avatar = a.id_avatar
    LEFT JOIN personajes p ON u.id_personaje = p.id_personaje
    LEFT JOIN armas ar ON u.id_arma = ar.id_arma
    WHERE u.id_usuario = :id_usuario";

$stmt = $con->prepare($sql);
$stmt->execute([':id_usuario' => $id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby - Call of Duty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">

    <div class="container-fluid vh-100 d-flex flex-column flex-md-row p-0">

        <!-- PANEL IZQUIERDO -->
        <aside class="col-12 col-md-2 custom-aside p-3">
            <div class="d-flex flex-column h-100 justify-content-between">
                <div>
                    <a href="index_player.php" class="text-decoration-none">
                        <h5 class="fw-bold text-warning text-center text-md-start mb-4">CALL OF DUTY</h5>
                    </a>
                    <div class="d-grid gap-3">
                        <a href="archivo_personajes/personajes.php" class="btn btn-outline-warning">Personajes</a>
                        <a href="archivo_armas/armero.php" class="btn btn-outline-warning">Armero</a>
                        <a href="registro_partidas.php" class="btn btn-outline-warning">Registro de partidas</a>
                    </div>
                </div>
            </div>
        </aside>

        <!-- CONTENIDO CENTRAL -->
        <main class="flex-grow-1 d-flex align-items-center justify-content-center text-center position-relative p-3"
            style="background: url('../../assets/img/fondo_inde.jpg') no-repeat center center / cover;">

            <!-- Capa oscura encima del fondo -->
            <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-50"></div>

            <!-- Contenido sobre la capa -->
            <div class="position-relative z-1">

                <h3 class="text-warning fw-bold mb-3">RANKED MULTIPLAYER</h3>

                <!-- Info Usuario -->
                <div class="mb-4">
                    <img src="../../<?= htmlspecialchars($usuario['url_imagen'] ?? 'assets/img/avatar/default.png'); ?>"
                        alt="Avatar" width="80" height="80"
                        class="rounded-circle border border-warning mb-2 img-fluid">
                    <p class="mb-0">Usuario: <strong><?= htmlspecialchars($usuario['nombre_usuario']); ?></strong></p>
                    <p class="mb-0">Nivel: <strong><?= htmlspecialchars($usuario['nombre_nivel']); ?></strong></p>
                    <p>Puntos: <strong class="text-info"><?= number_format($usuario['puntos_totales']); ?></strong></p>
                </div>

                <!-- Personaje y Arma -->
                <div class="d-flex flex-wrap justify-content-center align-items-start gap-4 mb-4">
                    <div>
                        <img src="../../<?= htmlspecialchars($usuario['imagen_personaje'] ?? 'assets/img/pj/default.png'); ?>"
                            class="img-fluid rounded border border-warning"
                            alt="Personaje" style="max-width: 220px;">
                        <h6 class="mt-2"><?= htmlspecialchars($usuario['nombre_personaje'] ?? 'Personaje actual'); ?></h6>
                    </div>
                    <div>
                        <img src="../../<?= htmlspecialchars($usuario['imagen_arma'] ?? 'assets/img/armas/puño/puño1.png'); ?>"
                            class="img-fluid rounded border border-warning"
                            alt="Arma" style="max-width: 180px;">
                        <h6 class="mt-2"><?= htmlspecialchars($usuario['nombre_arma'] ?? 'Arma actual'); ?></h6>
                    </div>
                </div>

                <form action="mapas_br_de/maps.php" method="GET" onsubmit="return seleccionarModo()">
                    <input type="hidden" id="modoSeleccionado" name="modo">
                    <a href="mapas.php" class="btn btn-warning btn-lg">START</a>
                </form>

            </div>
        </main>

    </div>

    <script>
        function seleccionarModo() {
            document.getElementById("modoSeleccionado").value =
                document.querySelector('input[name="modo"]:checked').value;
            return true;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ESTILOS VISUALES MEJORADOS -->
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #000;
            overflow: hidden;
        }

        /* PANEL IZQUIERDO MEJORADO */
        .custom-aside {
            background: linear-gradient(180deg, rgba(15, 15, 15, 0.95), rgba(10, 10, 10, 0.85));
            backdrop-filter: blur(6px);
            border-right: 2px solid rgba(255, 193, 7, 0.8);
            box-shadow: 5px 0 25px rgba(255, 193, 7, 0.15);
            color: #f8f9fa;
            transition: background 0.3s ease;
        }

        .custom-aside:hover {
            background: linear-gradient(180deg, rgba(20, 20, 20, 0.97), rgba(5, 5, 5, 0.9));
            box-shadow: 6px 0 30px rgba(255, 193, 7, 0.25);
        }

        .custom-aside h5 {
            text-shadow: 0 0 15px rgba(255, 193, 7, 0.8);
            letter-spacing: 1px;
            animation: glowTitle 3s infinite ease-in-out;
        }

        .btn-outline-warning {
            border: 1.5px solid #ffc107;
            color: #ffc107;
            font-weight: 600;
            background: rgba(255, 193, 7, 0.05);
            border-radius: 10px;
            transition: all 0.3s ease-in-out;
        }

        .btn-outline-warning:hover {
            background: linear-gradient(90deg, #ffd84d, #ffc107);
            color: #000;
            transform: scale(1.05);
            box-shadow: 0 0 25px rgba(255, 193, 7, 0.8);
        }

        /* 🔸 TÍTULO ANIMADO */
        @keyframes glowTitle {
            0% {
                text-shadow: 0 0 10px #ffc107, 0 0 20px #ffd84d;
            }

            50% {
                text-shadow: 0 0 25px #ffe678, 0 0 35px #fff3b0;
            }

            100% {
                text-shadow: 0 0 10px #ffc107, 0 0 20px #ffd84d;
            }
        }

        /* CONTENIDO CENTRAL */
        main {
            background-size: cover !important;
            background-position: center !important;
            box-shadow: inset 0 0 100px rgba(0, 0, 0, 0.9);
        }

        main .bg-dark.opacity-50 {
            backdrop-filter: blur(3px);
        }

        main .position-relative.z-1 {
            background: rgba(0, 0, 0, 0.65);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 0 40px rgba(255, 193, 7, 0.15);
        }

        .btn-warning.btn-lg {
            background: linear-gradient(90deg, #ffd84d, #ffc107);
            border: none;
            font-weight: 700;
            padding: 12px 40px;
            border-radius: 50px;
            box-shadow: 0 0 25px rgba(255, 193, 7, 0.4);
            transition: all 0.3s ease-in-out;
            text-transform: uppercase;
        }

        .btn-warning.btn-lg:hover {
            background: linear-gradient(90deg, #ffe678, #ffd84d);
            color: #000;
            box-shadow: 0 0 45px rgba(255, 193, 7, 0.8);
            transform: scale(1.1);
        }

        /* Habilita scroll solo en pantallas pequeñas */
        @media (max-width: 768px) {
            body {
                overflow-y: auto !important;
            }
        }
    </style>

</body>

</html>