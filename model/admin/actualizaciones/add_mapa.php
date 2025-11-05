<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

require_once("../../../db/conexion.php");
$db = new Database();
$con = $db->conectar();

$mensaje = '';
$alert_type = 'warning';

// --- Token para prevenir reenvío ---
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}
$form_token = $_SESSION['form_token'];

// Verificar si la columna nivel_minimo existe en la tabla mapas
$checkCol = $con->prepare("
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mapas' AND COLUMN_NAME = 'nivel_minimo'
");
$checkCol->execute();
$colExists = (bool) $checkCol->fetchColumn();

// Cargar niveles (si existe la tabla niveles) para el select
$niveles = [];
try {
    $stmtNiv = $con->prepare("SELECT id_nivel, nombre_nivel FROM niveles ORDER BY id_nivel ASC");
    $stmtNiv->execute();
    $niveles = $stmtNiv->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $niveles = [];
}

// --- Procesar agregar o editar mapa ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
        $mensaje = "Formulario ya enviado o inválido.";
        $alert_type = 'danger';
    } else {
        unset($_SESSION['form_token']); // Invalida token
        $_SESSION['form_token'] = bin2hex(random_bytes(32)); // Genera uno nuevo

        $id_editar = filter_input(INPUT_POST, 'id_editar', FILTER_SANITIZE_NUMBER_INT);
        $nombre_mapa = trim($_POST['nombre_mapa']);
        $modo_juego = trim($_POST['modo_juego']);
        $descripcion = trim($_POST['descripcion']);
        $imagen = $_FILES['imagen_preview'] ?? null;
        $nivel_minimo = $colExists ? (int)($_POST['nivel_minimo'] ?? 1) : null;

        if (strlen($nombre_mapa) < 3) {
            $mensaje = "El nombre del mapa debe tener al menos 3 caracteres.";
            $alert_type = 'danger';
        } elseif (strlen($descripcion) < 5) {
            $mensaje = "La descripción es demasiado corta.";
            $alert_type = 'danger';
        } elseif ($colExists && ($nivel_minimo < 1)) {
            $mensaje = "Nivel mínimo inválido.";
            $alert_type = 'danger';
        } else {
            if (!$id_editar) {
                $check = $con->prepare("SELECT COUNT(*) FROM mapas WHERE nombre_mapa = :nombre AND modo_juego = :modo");
                $check->bindParam(':nombre', $nombre_mapa);
                $check->bindParam(':modo', $modo_juego);
                $check->execute();
                if ($check->fetchColumn() > 0) {
                    $mensaje = "Ya existe un mapa con este nombre y modo de juego.";
                    $alert_type = 'danger';
                }
            }

            if (!$mensaje) {
                $ruta_db = null;

                if ($imagen && $imagen['name'] != '') {
                    $carpeta_relativa = match ($modo_juego) {
                        'BR' => "assets/img/mapa_br/",
                        'DE' => "assets/img/mapa_de/",
                        default => "assets/img/mapas/",
                    };
                    $carpeta_absoluta = "../../../" . $carpeta_relativa;
                    if (!is_dir($carpeta_absoluta)) mkdir($carpeta_absoluta, 0755, true);

                    $ext = pathinfo($imagen['name'], PATHINFO_EXTENSION);
                    $nombre_seguro = preg_replace("/[^a-zA-Z0-9]/", "_", $nombre_mapa);
                    $nombre_imagen = $nombre_seguro . "_" . time() . "." . $ext;
                    $ruta_destino = $carpeta_absoluta . $nombre_imagen;
                    $ruta_db = $carpeta_relativa . $nombre_imagen;

                    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($imagen['type'], $tipos_permitidos)) {
                        $mensaje = "Tipo de imagen no permitido. Solo JPG, PNG, GIF o WEBP.";
                        $alert_type = 'danger';
                    } elseif ($imagen['size'] > 5 * 1024 * 1024) {
                        $mensaje = "La imagen es demasiado grande (máx 5MB).";
                        $alert_type = 'danger';
                    } elseif (!move_uploaded_file($imagen['tmp_name'], $ruta_destino)) {
                        $mensaje = "Error al subir la imagen.";
                        $alert_type = 'danger';
                    }
                }

                if (!$mensaje) {
                    if ($id_editar) {
                        if ($ruta_db) {
                            $sqlPrev = $con->prepare("SELECT imagen_preview FROM mapas WHERE id_mapa = :id");
                            $sqlPrev->bindParam(':id', $id_editar, PDO::PARAM_INT);
                            $sqlPrev->execute();
                            $prev = $sqlPrev->fetch(PDO::FETCH_ASSOC);
                            if ($prev && !empty($prev['imagen_preview']) && file_exists("../../../" . $prev['imagen_preview'])) {
                                @unlink("../../../" . $prev['imagen_preview']);
                            }
                        }

                        $sets = "nombre_mapa = :nombre_mapa, modo_juego = :modo_juego, descripcion = :descripcion";
                        if ($ruta_db) $sets .= ", imagen_preview = :imagen";
                        if ($colExists) $sets .= ", nivel_minimo = :nivel_minimo";

                        $sql = "UPDATE mapas SET {$sets} WHERE id_mapa = :id";
                        $stmt = $con->prepare($sql);
                        $stmt->bindParam(':nombre_mapa', $nombre_mapa);
                        $stmt->bindParam(':modo_juego', $modo_juego);
                        $stmt->bindParam(':descripcion', $descripcion);
                        if ($ruta_db) $stmt->bindParam(':imagen', $ruta_db);
                        if ($colExists) $stmt->bindParam(':nivel_minimo', $nivel_minimo, PDO::PARAM_INT);
                        $stmt->bindParam(':id', $id_editar, PDO::PARAM_INT);
                        $stmt->execute();

                        header("Location: add_mapa.php?success=edit");
                        exit();
                    } else {
                        if ($colExists) {
                            $sql = "INSERT INTO mapas (nombre_mapa, modo_juego, descripcion, imagen_preview, nivel_minimo) VALUES (:nombre_mapa, :modo_juego, :descripcion, :imagen, :nivel_minimo)";
                            $stmt = $con->prepare($sql);
                            $stmt->bindParam(':nivel_minimo', $nivel_minimo, PDO::PARAM_INT);
                        } else {
                            $sql = "INSERT INTO mapas (nombre_mapa, modo_juego, descripcion, imagen_preview) VALUES (:nombre_mapa, :modo_juego, :descripcion, :imagen)";
                            $stmt = $con->prepare($sql);
                        }
                        $stmt->bindParam(':nombre_mapa', $nombre_mapa);
                        $stmt->bindParam(':modo_juego', $modo_juego);
                        $stmt->bindParam(':descripcion', $descripcion);
                        $stmt->bindParam(':imagen', $ruta_db);
                        $stmt->execute();

                        header("Location: add_mapa.php?success=add");
                        exit();
                    }
                }
            }
        }
    }
}

// --- Mensaje después de redirección ---
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'add') {
        $mensaje = "Mapa agregado correctamente.";
        $alert_type = 'success';
    } elseif ($_GET['success'] === 'edit') {
        $mensaje = "Mapa editado correctamente.";
        $alert_type = 'success';
    }
}

// --- Eliminar mapa ---
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $sql_img = $con->prepare("SELECT imagen_preview FROM mapas WHERE id_mapa=:id");
    $sql_img->bindParam(':id', $id);
    $sql_img->execute();
    $img = $sql_img->fetch(PDO::FETCH_ASSOC);
    if ($img && !empty($img['imagen_preview']) && file_exists("../../../" . $img['imagen_preview'])) unlink("../../../" . $img['imagen_preview']);

    $sql = $con->prepare("DELETE FROM mapas WHERE id_mapa=:id");
    $sql->bindParam(':id', $id);
    $sql->execute();
    $mensaje = "Mapa eliminado correctamente.";
    $alert_type = 'success';
}

// --- Cargar datos para editar ---
$id_editar = $_GET['editar'] ?? null;
$editar_mapa = null;
if ($id_editar) {
    if ($colExists) {
        $stmt = $con->prepare("SELECT * FROM mapas WHERE id_mapa=:id");
    } else {
        $stmt = $con->prepare("SELECT id_mapa, nombre_mapa, modo_juego, descripcion, imagen_preview FROM mapas WHERE id_mapa=:id");
    }
    $stmt->bindParam(':id', $id_editar, PDO::PARAM_INT);
    $stmt->execute();
    $editar_mapa = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- Obtener todos los mapas ---
$mapas = $con->query("SELECT * FROM mapas ORDER BY id_mapa DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin Mapas - COD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">
    <div class="container py-5">
        <h1 class="text-center text-warning mb-4"><?= $editar_mapa ? "Editar Mapa" : "Agregar Nuevo Mapa" ?></h1>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $alert_type ?> text-center text-light"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if (!$colExists): ?>
            <div class="alert alert-info text-dark">
                Nota: tu tabla <strong>mapas</strong> no tiene la columna <code>nivel_minimo</code>. Si deseas
                asignar nivel mínimo a cada mapa, ejecuta en tu base de datos:
                <code>ALTER TABLE mapas ADD COLUMN nivel_minimo INT DEFAULT 1;</code>
            </div>
        <?php endif; ?>

        <div class="card bg-dark border-warning p-4 shadow mb-4">
            <form action="add_mapa.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_editar" value="<?= $editar_mapa['id_mapa'] ?? '' ?>">
                <input type="hidden" name="form_token" value="<?= $form_token ?>">

                <div class="mb-3">
                    <label class="form-label text-warning">Nombre del Mapa</label>
                    <input type="text" class="form-control" name="nombre_mapa" required value="<?= htmlspecialchars($editar_mapa['nombre_mapa'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label text-warning">Modo de Juego</label>
                    <select class="form-select" name="modo_juego" required>
                        <option value="">Selecciona un modo</option>
                        <option value="BR" <?= (isset($editar_mapa['modo_juego']) && $editar_mapa['modo_juego'] === 'BR') ? 'selected' : '' ?>>BR</option>
                        <option value="DE" <?= (isset($editar_mapa['modo_juego']) && $editar_mapa['modo_juego'] === 'DE') ? 'selected' : '' ?>>DE</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-warning">Descripción</label>
                    <textarea class="form-control" name="descripcion" rows="3" required><?= htmlspecialchars($editar_mapa['descripcion'] ?? '') ?></textarea>
                </div>

                <?php if ($colExists): ?>
                    <div class="mb-3">
                        <label class="form-label text-warning">Nivel Mínimo</label>
                        <?php if (!empty($niveles)): ?>
                            <select class="form-select" name="nivel_minimo" required>
                                <?php foreach ($niveles as $n):
                                    $sel = (isset($editar_mapa['nivel_minimo']) && $editar_mapa['nivel_minimo'] == $n['id_nivel']) ? 'selected' : '';
                                ?>
                                    <option value="<?= (int)$n['id_nivel'] ?>" <?= $sel ?>><?= htmlspecialchars($n['nombre_nivel']) ?> (<?= (int)$n['id_nivel'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="number" min="1" class="form-control" name="nivel_minimo" value="<?= htmlspecialchars($editar_mapa['nivel_minimo'] ?? 1) ?>" required>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label text-warning">Imagen Preview <?= $editar_mapa ? "(Opcional para cambiar)" : "" ?></label>
                    <input class="form-control" type="file" name="imagen_preview" accept="image/*" <?= $editar_mapa ? "" : "required" ?>>
                    <?php if ($editar_mapa && !empty($editar_mapa['imagen_preview'])): ?>
                        <img src="../../../<?= htmlspecialchars($editar_mapa['imagen_preview']) ?>" alt="" width="120" class="mt-2">
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-warning w-100"><?= $editar_mapa ? "Editar Mapa" : "Agregar Mapa" ?></button>
            </form>
        </div>

        <h2 class="text-warning mb-3">Mapas Existentes</h2>
        <table class="table table-dark table-striped text-center align-middle">
            <thead class="table-warning text-white">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Modo de Juego</th>
                    <?php if ($colExists): ?><th>Nivel Mínimo</th><?php endif; ?>
                    <th>Imagen Preview</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mapas as $m): ?>
                    <tr>
                        <td><?= $m['id_mapa'] ?></td>
                        <td><?= htmlspecialchars($m['nombre_mapa']) ?></td>
                        <td><?= htmlspecialchars($m['modo_juego']) ?></td>
                        <?php if ($colExists): ?>
                            <td><?= (int)($m['nivel_minimo'] ?? 1) ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if (!empty($m['imagen_preview'])): ?>
                                <img src="../../../<?= htmlspecialchars($m['imagen_preview']) ?>" alt="" width="100">
                            <?php else: ?>
                                <span class="text-muted">Sin imagen</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($m['descripcion']) ?></td>
                        <td>
                            <a href="add_mapa.php?editar=<?= $m['id_mapa'] ?>" class="btn btn-sm btn-warning mb-1">Editar</a>
                            <a href="add_mapa.php?eliminar=<?= $m['id_mapa'] ?>" onclick="return confirm('¿Seguro que deseas eliminar este mapa?');" class="btn btn-sm btn-danger">Eliminar</a>
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