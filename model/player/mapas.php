<?php
session_start();
require_once __DIR__ . '/../../db/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../../login.php");
    exit;
}

$db = new Database();
$con = $db->conectar();

$id_usuario = $_SESSION['id_usuario'];

// Obtener el id_nivel del usuario actual (campo en la tabla usuarios)
$stmtNivel = $con->prepare("SELECT id_nivel FROM usuarios WHERE id_usuario = :id");
$stmtNivel->bindParam(':id', $id_usuario, PDO::PARAM_INT);
$stmtNivel->execute();
$id_nivel_usuario = $stmtNivel->fetchColumn();
if (!$id_nivel_usuario) {
    $id_nivel_usuario = 1; // fallback si no tiene nivel asignado
}

// Verificar si la columna nivel_minimo existe en la tabla mapas
$checkCol = $con->prepare("
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mapas' AND COLUMN_NAME = 'nivel_minimo'
");
$checkCol->execute();
$colExists = (bool) $checkCol->fetchColumn();

// Obtener el modo seleccionado por el usuario (si no hay, mostrar todos)
$modoSeleccionado = $_GET['modo'] ?? '';

// Preparar la consulta según el modo y si existe la columna nivel_minimo
if ($modoSeleccionado) {
    if ($colExists) {
        $stmt = $con->prepare("SELECT id_mapa, nombre_mapa, modo_juego, descripcion, imagen_preview, nivel_minimo
                               FROM mapas
                               WHERE modo_juego = :modo
                               AND nivel_minimo <= :nivelUsuario
                               ORDER BY id_mapa ASC");
        $stmt->bindParam(':modo', $modoSeleccionado, PDO::PARAM_STR);
        $stmt->bindParam(':nivelUsuario', $id_nivel_usuario, PDO::PARAM_INT);
    } else {
        // Si no existe la columna nivel_minimo, no aplicamos filtro de nivel
        $stmt = $con->prepare("SELECT id_mapa, nombre_mapa, modo_juego, descripcion, imagen_preview
                               FROM mapas
                               WHERE modo_juego = :modo
                               ORDER BY id_mapa ASC");
        $stmt->bindParam(':modo', $modoSeleccionado, PDO::PARAM_STR);
    }
} else {
    if ($colExists) {
        $stmt = $con->prepare("SELECT id_mapa, nombre_mapa, modo_juego, descripcion, imagen_preview, nivel_minimo
                               FROM mapas
                               WHERE nivel_minimo <= :nivelUsuario
                               ORDER BY id_mapa ASC");
        $stmt->bindParam(':nivelUsuario', $id_nivel_usuario, PDO::PARAM_INT);
    } else {
        // Si no existe la columna nivel_minimo, no aplicamos filtro de nivel
        $stmt = $con->prepare("SELECT id_mapa, nombre_mapa, modo_juego, descripcion, imagen_preview
                               FROM mapas
                               ORDER BY id_mapa ASC");
    }
}

$stmt->execute();
$mapas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Variable para mostrar alerta si no existe nivel_minimo
$needs_migration = !$colExists;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mapas - Call of Duty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('../../assets/img/fondos/fondo_mapas.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: 'Segoe UI', sans-serif;
            color: #f8f9fa;
        }

        h1,
        h2 {
            color: #ffc107;
            font-weight: 800;
            text-align: center;
            letter-spacing: 1.5px;
            text-shadow: 0 0 25px rgba(255, 193, 7, 0.9);
            margin-bottom: 10px;
        }

        p.text-secondary {
            text-align: center;
            font-size: 1.1rem;
            color: #ddd !important;
            margin-bottom: 30px;
        }

        .btn-outline-light,
        .btn-warning {
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

        .btn-warning {
            background: linear-gradient(90deg, #ffca2c, #ffc107);
            border: none;
            color: #000;
            box-shadow: 0 0 15px rgba(255, 193, 7, 0.5);
        }

        .btn-warning:hover {
            background: linear-gradient(90deg, #ffd84d, #ffca2c);
            box-shadow: 0 0 30px rgba(255, 193, 7, 0.9);
            transform: scale(1.08);
        }

        .card {
            background: rgba(30, 30, 30, 0.7);
            border: 2px solid rgba(255, 193, 7, 0.4);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 0 25px rgba(255, 193, 7, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 0 35px rgba(255, 193, 7, 0.5);
            border-color: #ffc107;
        }

        .card img {
            border-bottom: 3px solid rgba(255, 193, 7, 0.7);
        }

        .card-body {
            padding: 25px;
        }

        .card h5 {
            color: #ffc107;
            font-weight: 700;
            margin-bottom: 10px;
            text-align: center;
            text-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
        }

        .card p {
            font-size: 0.95rem;
            color: #e5e5e5;
            text-align: justify;
            margin-bottom: 10px;
        }

        /* Encabezado */
        .titulo-mapa {
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(8px);
            padding: 30px 10px;
            border-radius: 15px;
            margin-bottom: 40px;
            box-shadow: 0 0 25px rgba(255, 193, 7, 0.2);
        }

        /* Filtros de modo */
        .btn-group .btn {
            border-radius: 8px !important;
            font-weight: bold;
        }

        .btn-group .btn:hover {
            box-shadow: 0 0 15px rgba(255, 193, 7, 0.8);
        }

        /* Animación de brillo en el título */
        @keyframes goldPulse {
            0% {
                text-shadow: 0 0 10px #ffc107, 0 0 20px #ffda6a;
            }

            50% {
                text-shadow: 0 0 25px #ffd84d, 0 0 40px #ffe37d;
            }

            100% {
                text-shadow: 0 0 10px #ffc107, 0 0 20px #ffda6a;
            }
        }

        h1,
        h2 {
            animation: goldPulse 3s infinite ease-in-out;
        }

        /* pequeña alerta en top para el admin (no intrusiva) */
        .migration-alert {
            max-width: 900px;
            margin: 0 auto 20px auto;
            background: rgba(255, 193, 7, 0.12);
            border-left: 4px solid #ffc107;
            color: #fff9e6;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.95rem;
        }
    </style>

</head>

<body>

    <div class="overlay">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h1 class="text-warning fw-bold">SELECCIONA UN MAPA</h1>
                <p class="text-secondary">Elige el escenario donde se desarrollará la batalla</p>
                <a href="lobby.php" class="btn btn-outline-light btn-sm mt-2">⬅ Volver al Lobby</a>

                <!-- 🔹 Botones para filtrar por modo -->
                <div class="mt-3">
                    <a href="?modo=BR" class="btn btn-warning <?= $modoSeleccionado === 'BR' ? 'active' : '' ?>">BR</a>
                    <a href="?modo=DE" class="btn btn-warning <?= $modoSeleccionado === 'DE' ? 'active' : '' ?>">DE</a>
                    <a href="?" class="btn btn-outline-light <?= $modoSeleccionado === '' ? 'active' : '' ?>">Todos</a>
                </div>
            </div>

            <?php if ($needs_migration): ?>
                <div class="migration-alert text-center">
                    Nota: Tu tabla <strong>mapas</strong> no tiene la columna <code>nivel_minimo</code>.
                    Si deseas restringir mapas por nivel, agrega esta columna:
                    <code>ALTER TABLE mapas ADD COLUMN nivel_minimo INT DEFAULT 1;</code>
                </div>
            <?php endif; ?>

            <div class="row g-4 justify-content-center">
                <?php if (empty($mapas)): ?>
                    <div class="col-12 text-center text-light">No hay mapas registrados para este modo.</div>
                <?php else: ?>
                    <?php foreach ($mapas as $mapa): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="card border border-2 border-warning h-100 text-center">
                                <img src="../../<?= htmlspecialchars($mapa['imagen_preview'] ?? 'assets/img/default_map.jpg') ?>"
                                    alt="Mapa <?= htmlspecialchars($mapa['nombre_mapa']) ?>"
                                    class="card-img-top"
                                    style="height:200px;object-fit:cover;">
                                <div class="card-body d-flex flex-column">
                                    <h4 class="text-warning"><?= htmlspecialchars($mapa['nombre_mapa']) ?></h4>
                                    <p class="text-light flex-grow-1"><?= htmlspecialchars($mapa['descripcion']) ?></p>
                                    <p>
                                        <span class="badge bg-warning text-dark">Modo: <?= htmlspecialchars($mapa['modo_juego']) ?></span>
                                        <?php if (isset($mapa['nivel_minimo'])): ?>
                                            <span class="badge bg-secondary text-light ms-1">Nivel mínimo: <?= (int)$mapa['nivel_minimo'] ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <a href="salas.php?id_mapa=<?= $mapa['id_mapa'] ?>" class="btn btn-warning fw-bold mt-auto">Ver Salas</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>

</html>