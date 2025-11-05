<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

require_once("../../../db/conexion.php");
$db = new Database();
$con = $db->conectar();

$mensaje = '';
$alert_type = 'warning';

// --- Procesar agregar o editar partida ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_editar = $_POST['id_editar'] ?? null;
    $id_mapa = $_POST['id_mapa'] ?? null;
    $modo_juego = $_POST['modo_juego'] ?? null;
    $estado = $_POST['estado'] ?? 'esperando';
    $jugadores_max = $_POST['jugadores_max'] ?? null;
    $nivel_minimo = $_POST['nivel_minimo'] ?? null;
    $nivel_maximo = $_POST['nivel_maximo'] ?? null;
    $rango_minimo = $_POST['rango_minimo'] ?? null;
    $rango_maximo = $_POST['rango_maximo'] ?? null;
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;

    // --- Validaciones básicas ---
    if (!$id_mapa || !$modo_juego) {
        $mensaje = "Debe seleccionar un mapa y un modo.";
        $alert_type = 'danger';
    } elseif ($jugadores_max < 1) {
        $mensaje = "El número de jugadores debe ser mayor a 0.";
        $alert_type = 'danger';
    } elseif ($nivel_minimo > $nivel_maximo) {
        $mensaje = "El nivel mínimo no puede ser mayor que el máximo.";
        $alert_type = 'danger';
    } elseif ($rango_minimo > $rango_maximo) {
        $mensaje = "El rango mínimo no puede ser mayor que el máximo.";
        $alert_type = 'danger';
    } else {
        if ($id_editar) {
            // --- Editar partida ---
            $sql = "UPDATE partidas SET 
                        id_mapa=:id_mapa,
                        modo_juego=:modo_juego,
                        estado=:estado,
                        jugadores_max=:jugadores_max,
                        nivel_minimo=:nivel_minimo,
                        nivel_maximo=:nivel_maximo,
                        rango_minimo=:rango_minimo,
                        rango_maximo=:rango_maximo,
                        fecha_inicio=:fecha_inicio,
                        fecha_fin=:fecha_fin
                    WHERE id_partida=:id";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':id', $id_editar);
        } else {
            // --- Insertar nueva partida ---
            $sql = "INSERT INTO partidas 
                    (id_mapa, modo_juego, estado, jugadores_max, nivel_minimo, nivel_maximo, rango_minimo, rango_maximo, fecha_inicio, fecha_fin)
                    VALUES 
                    (:id_mapa, :modo_juego, :estado, :jugadores_max, :nivel_minimo, :nivel_maximo, :rango_minimo, :rango_maximo, :fecha_inicio, :fecha_fin)";
            $stmt = $con->prepare($sql);
        }

        // Bind comunes
        $stmt->bindParam(':id_mapa', $id_mapa);
        $stmt->bindParam(':modo_juego', $modo_juego);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':jugadores_max', $jugadores_max);
        $stmt->bindParam(':nivel_minimo', $nivel_minimo);
        $stmt->bindParam(':nivel_maximo', $nivel_maximo);
        $stmt->bindParam(':rango_minimo', $rango_minimo);
        $stmt->bindParam(':rango_maximo', $rango_maximo);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin', $fecha_fin);

        if ($stmt->execute()) {
            $mensaje = $id_editar ? "Partida actualizada correctamente." : "Partida agregada correctamente.";
            $alert_type = 'success';
        } else {
            $mensaje = "Error al guardar la partida.";
            $alert_type = 'danger';
        }
    }
}

// --- Eliminar partida ---
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $sql = $con->prepare("DELETE FROM partidas WHERE id_partida=:id");
    $sql->bindParam(':id', $id);
    if ($sql->execute()) {
        $mensaje = "Partida eliminada correctamente.";
        $alert_type = 'success';
    } else {
        $mensaje = "Error al eliminar la partida.";
        $alert_type = 'danger';
    }
}

// --- Cargar datos para editar ---
$id_editar = $_GET['editar'] ?? null;
$editar_partida = null;
if ($id_editar) {
    $stmt = $con->prepare("SELECT * FROM partidas WHERE id_partida=:id");
    $stmt->bindParam(':id', $id_editar);
    $stmt->execute();
    $editar_partida = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- Obtener datos para selects ---
$mapas = $con->query("SELECT id_mapa, nombre_mapa FROM mapas ORDER BY nombre_mapa ASC")->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Los modos se obtienen desde mapas.modo_juego
$modos = [];
foreach ($con->query("SELECT DISTINCT modo_juego FROM mapas") as $i => $row) {
    $modos[] = [
        'modo_juego' => $i + 1,
        'nombre_modo' => $row['modo_juego']
    ];
}

// --- Listar partidas ---
$partidas = $con->query("
    SELECT p.*, m.nombre_mapa AS mapa, m.modo_juego AS modo
    FROM partidas p
    INNER JOIN mapas m ON p.id_mapa = m.id_mapa
    ORDER BY p.id_partida DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin Partidas - COD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">
    <div class="container py-5">
        <h1 class="text-center text-warning mb-4"><?= $editar_partida ? "Editar Partida" : "Agregar Nueva Partida" ?></h1>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $alert_type ?> text-center"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="card bg-dark border-warning p-4 shadow mb-4">
            <form action="add_salas.php" method="POST">
                <input type="hidden" name="id_editar" value="<?= $editar_partida['id_partida'] ?? '' ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-warning">Mapa</label>
                        <select class="form-select" name="id_mapa" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($mapas as $m): ?>
                                <option value="<?= $m['id_mapa'] ?>" <?= ($editar_partida && $editar_partida['id_mapa'] == $m['id_mapa']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['nombre_mapa']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-warning">Modo</label>
                        <select class="form-select" name="modo_juego" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($modos as $mo): ?>
                                <option value="<?= $mo['modo_juego'] ?>" <?= ($editar_partida && $editar_partida['modo_juego'] == $mo['modo_juego']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mo['nombre_modo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-warning">Jugadores Máximos</label>
                        <input type="number" class="form-control" name="jugadores_max" min="1" required value="<?= htmlspecialchars($editar_partida['jugadores_max'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-warning">Estado</label>
                        <select class="form-select" name="estado">
                            <option value="esperando" <?= ($editar_partida && $editar_partida['estado'] == 'esperando') ? 'selected' : '' ?>>Esperando</option>
                            <option value="en_curso" <?= ($editar_partida && $editar_partida['estado'] == 'en_curso') ? 'selected' : '' ?>>En Curso</option>
                            <option value="finalizada" <?= ($editar_partida && $editar_partida['estado'] == 'finalizada') ? 'selected' : '' ?>>Finalizada</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label text-warning">Nivel Mínimo</label>
                        <input type="number" class="form-control" name="nivel_minimo" required value="<?= htmlspecialchars($editar_partida['nivel_minimo'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label text-warning">Nivel Máximo</label>
                        <input type="number" class="form-control" name="nivel_maximo" required value="<?= htmlspecialchars($editar_partida['nivel_maximo'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label text-warning">Rango Mínimo</label>
                        <input type="number" class="form-control" name="rango_minimo" required value="<?= htmlspecialchars($editar_partida['rango_minimo'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label text-warning">Rango Máximo</label>
                        <input type="number" class="form-control" name="rango_maximo" required value="<?= htmlspecialchars($editar_partida['rango_maximo'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-warning">Fecha Inicio</label>
                        <input type="datetime-local" class="form-control" name="fecha_inicio" value="<?= $editar_partida['fecha_inicio'] ? date('Y-m-d\TH:i', strtotime($editar_partida['fecha_inicio'])) : '' ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-warning">Fecha Fin</label>
                        <input type="datetime-local" class="form-control" name="fecha_fin" value="<?= $editar_partida['fecha_fin'] ? date('Y-m-d\TH:i', strtotime($editar_partida['fecha_fin'])) : '' ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-warning w-100 mt-4"><?= $editar_partida ? "Editar Partida" : "Agregar Partida" ?></button>
            </form>
        </div>

        <!-- Tabla de partidas -->
        <h2 class="text-warning mb-3">Partidas Existentes</h2>
        <table class="table table-dark table-striped text-center align-middle">
            <thead class="table-warning text-dark">
                <tr>
                    <th>ID</th>
                    <th>Mapa</th>
                    <th>Modo</th>
                    <th>Estado</th>
                    <th>Jugadores</th>
                    <th>Niveles</th>
                    <th>Rangos</th>
                    <th>Fechas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($partidas as $p): ?>
                    <tr>
                        <td><?= $p['id_partida'] ?></td>
                        <td><?= htmlspecialchars($p['mapa']) ?></td>
                        <td><?= htmlspecialchars($p['modo']) ?></td>
                        <td><?= htmlspecialchars($p['estado']) ?></td>
                        <td><?= $p['jugadores_max'] ?></td>
                        <td><?= "{$p['nivel_minimo']} - {$p['nivel_maximo']}" ?></td>
                        <td><?= "{$p['rango_minimo']} - {$p['rango_maximo']}" ?></td>
                        <td>
                            <?= $p['fecha_inicio'] ? date('d/m/Y H:i', strtotime($p['fecha_inicio'])) : '-' ?><br>
                            <?= $p['fecha_fin'] ? date('d/m/Y H:i', strtotime($p['fecha_fin'])) : '-' ?>
                        </td>
                        <td>
                            <a href="add_salas.php?editar=<?= $p['id_partida'] ?>" class="btn btn-sm btn-warning mb-1">Editar</a>
                            <a href="add_salas.php?eliminar=<?= $p['id_partida'] ?>" onclick="return confirm('¿Seguro que deseas eliminar esta partida?');" class="btn btn-sm btn-danger">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="text-center mt-4">
            <a href="../index_admin.php" class="btn btn-warning">← Volver al Panel</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>