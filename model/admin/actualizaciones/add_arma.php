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

// --- Procesar agregar o editar arma ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_editar = $_POST['id_editar'] ?? null;
    $id_tipo = $_POST['id_tipo'];
    $nombre = trim($_POST['nombre']);
    $dano = $_POST['dano'];
    $municion_max = $_POST['municion_max'];
    $municion_total = $_POST['municion_total'];
    $cadencia = $_POST['cadencia'];
    $descripcion = trim($_POST['descripcion']);
    $precision_porcentaje = $_POST['precision_porcentaje'];
    $nivel_requerido = $_POST['nivel_requerido'];
    $imagen = $_FILES['imagen'] ?? null;

    if (strlen($nombre) < 3) {
        $mensaje = "El nombre debe tener al menos 3 caracteres.";
        $alert_type = 'danger';
    } elseif (strlen($descripcion) < 5) {
        $mensaje = "La descripción es demasiado corta.";
        $alert_type = 'danger';
    } else {
        $ruta_db = null;

        if ($imagen && $imagen['name'] != '') {
            // --- Obtener slug de tipo ---
            $tipo_slug = 'otros';
            try {
                $stmtTipo = $con->prepare("SELECT nombre_tipo FROM tipos_arma WHERE id_tipo = :id");
                $stmtTipo->bindParam(':id', $id_tipo);
                $stmtTipo->execute();
                $rowTipo = $stmtTipo->fetch(PDO::FETCH_ASSOC);
                if ($rowTipo && !empty($rowTipo['nombre_tipo'])) {
                    $tipo_slug = preg_replace('/[^a-z0-9\-_]/', '_', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $rowTipo['nombre_tipo'])));
                    $tipo_slug = preg_replace('/_+/', '_', $tipo_slug);
                    $tipo_slug = trim($tipo_slug, '_-');
                }
            } catch (Exception $e) {
                $tipo_slug = 'otros';
            }

            $carpeta_relativa = "assets/img/armas/" . $tipo_slug . "/";
            $carpeta_absoluta = "../../../" . $carpeta_relativa;

            if (!is_dir($carpeta_absoluta)) {
                mkdir($carpeta_absoluta, 0755, true);
            }

            $ext = pathinfo($imagen['name'], PATHINFO_EXTENSION);
            $nombre_seguro = preg_replace("/[^a-zA-Z0-9]/", "_", $nombre);
            $nombre_imagen = $nombre_seguro . "_" . time() . "." . $ext;

            $ruta_destino = $carpeta_absoluta . $nombre_imagen;
            $ruta_db = $carpeta_relativa . $nombre_imagen;

            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($imagen['type'], $tipos_permitidos)) {
                $mensaje = "Tipo de imagen no permitido. Solo JPG, PNG, GIF o WEBP.";
                $alert_type = 'danger';
            } elseif (!move_uploaded_file($imagen['tmp_name'], $ruta_destino)) {
                $mensaje = "Error al subir la imagen.";
                $alert_type = 'danger';
            }
        }

        if (!$mensaje) {
            if ($id_editar) {
                // Editar arma
                if ($ruta_db) {
                    $sql = "UPDATE armas SET id_tipo=:id_tipo, nombre=:nombre, dano=:dano, municion_max=:municion_max, municion_total=:municion_total, cadencia=:cadencia, descripcion=:descripcion, precision_porcentaje=:precision_porcentaje, nivel_requerido=:nivel_requerido, imagen=:imagen WHERE id_arma=:id";
                    $stmt = $con->prepare($sql);
                    $stmt->bindParam(':imagen', $ruta_db);
                } else {
                    $sql = "UPDATE armas SET id_tipo=:id_tipo, nombre=:nombre, dano=:dano, municion_max=:municion_max, municion_total=:municion_total, cadencia=:cadencia, descripcion=:descripcion, precision_porcentaje=:precision_porcentaje, nivel_requerido=:nivel_requerido WHERE id_arma=:id";
                    $stmt = $con->prepare($sql);
                }
                $stmt->bindParam(':id_tipo', $id_tipo);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':dano', $dano);
                $stmt->bindParam(':municion_max', $municion_max);
                $stmt->bindParam(':municion_total', $municion_total);
                $stmt->bindParam(':cadencia', $cadencia);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':precision_porcentaje', $precision_porcentaje);
                $stmt->bindParam(':nivel_requerido', $nivel_requerido);
                $stmt->bindParam(':id', $id_editar);
                if ($stmt->execute()) {
                    $mensaje = "Arma editada correctamente.";
                    $alert_type = 'success';
                } else {
                    $mensaje = "Error al editar arma.";
                    $alert_type = 'danger';
                }
            } else {
                // Insertar arma
                $sql = "INSERT INTO armas (id_tipo, nombre, dano, municion_max, municion_total, cadencia, descripcion, precision_porcentaje, nivel_requerido, imagen) VALUES (:id_tipo, :nombre, :dano, :municion_max, :municion_total, :cadencia, :descripcion, :precision_porcentaje, :nivel_requerido, :imagen)";
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':id_tipo', $id_tipo);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':dano', $dano);
                $stmt->bindParam(':municion_max', $municion_max);
                $stmt->bindParam(':municion_total', $municion_total);
                $stmt->bindParam(':cadencia', $cadencia);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':precision_porcentaje', $precision_porcentaje);
                $stmt->bindParam(':nivel_requerido', $nivel_requerido);
                $stmt->bindParam(':imagen', $ruta_db);
                if ($stmt->execute()) {
                    $mensaje = "Arma agregada correctamente.";
                    $alert_type = 'success';
                } else {
                    $mensaje = "Error al agregar arma.";
                    $alert_type = 'danger';
                }
            }
        }
    }
}

// --- Eliminar arma ---
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $sql_img = $con->prepare("SELECT imagen FROM armas WHERE id_arma=:id");
    $sql_img->bindParam(':id', $id);
    $sql_img->execute();
    $img = $sql_img->fetch(PDO::FETCH_ASSOC);
    if ($img && file_exists("../../../" . $img['imagen'])) {
        unlink("../../../" . $img['imagen']);
    }

    $sql = $con->prepare("DELETE FROM armas WHERE id_arma=:id");
    $sql->bindParam(':id', $id);
    if ($sql->execute()) {
        $mensaje = "Arma eliminada correctamente.";
        $alert_type = 'success';
    } else {
        $mensaje = "Error al eliminar arma.";
        $alert_type = 'danger';
    }
}

// --- Cargar datos para editar ---
$id_editar = $_GET['editar'] ?? null;
$editar_arma = null;
if ($id_editar) {
    $stmt = $con->prepare("SELECT * FROM armas WHERE id_arma=:id");
    $stmt->bindParam(':id', $id_editar);
    $stmt->execute();
    $editar_arma = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- Obtener todos las armas ---
$armas = $con->query("SELECT * FROM armas ORDER BY id_arma DESC")->fetchAll(PDO::FETCH_ASSOC);

// --- Obtener tipos de armas ---
$tipos_armas = $con->query("SELECT * FROM tipos_arma ORDER BY id_tipo")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin Armas - COD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">
    <div class="container py-5">
        <h1 class="text-center text-warning mb-4"><?= $editar_arma ? "Editar Arma" : "Agregar Nueva Arma" ?></h1>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $alert_type ?> text-center text-light"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="card bg-dark border-warning p-4 shadow mb-4">
            <form action="add_arma.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_editar" value="<?= $editar_arma['id_arma'] ?? '' ?>">

                <!-- Tipo de arma -->
                <div class="mb-3">
                    <label for="id_tipo" class="form-label text-warning">Tipo de Arma</label>
                    <select name="id_tipo" id="id_tipo" class="form-select" required>
                        <?php foreach ($tipos_armas as $tipo): ?>
                            <option value="<?= $tipo['id_tipo'] ?>" <?= ($editar_arma && $editar_arma['id_tipo'] == $tipo['id_tipo']) ? 'selected' : '' ?>><?= htmlspecialchars($tipo['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Nombre y stats -->
                <div class="mb-3">
                    <label for="nombre" class="form-label text-warning">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required value="<?= htmlspecialchars($editar_arma['nombre'] ?? '') ?>">
                </div>

                <div class="mb-3 row">
                    <div class="col"><label class="form-label text-warning">Daño</label><input type="number" class="form-control" name="dano" required value="<?= htmlspecialchars($editar_arma['dano'] ?? '') ?>"></div>
                    <div class="col"><label class="form-label text-warning">Mun. Máx</label><input type="number" class="form-control" name="municion_max" required value="<?= htmlspecialchars($editar_arma['municion_max'] ?? '') ?>"></div>
                    <div class="col"><label class="form-label text-warning">Mun. Total</label><input type="number" class="form-control" name="municion_total" required value="<?= htmlspecialchars($editar_arma['municion_total'] ?? '') ?>"></div>
                    <div class="col"><label class="form-label text-warning">Cadencia</label><input type="number" class="form-control" name="cadencia" required value="<?= htmlspecialchars($editar_arma['cadencia'] ?? '') ?>"></div>
                </div>

                <div class="mb-3 row">
                    <div class="col"><label class="form-label text-warning">Precisión (%)</label><input type="number" class="form-control" name="precision_porcentaje" required value="<?= htmlspecialchars($editar_arma['precision_porcentaje'] ?? '') ?>"></div>
                    <div class="col"><label class="form-label text-warning">Nivel Requerido</label><input type="number" class="form-control" name="nivel_requerido" required value="<?= htmlspecialchars($editar_arma['nivel_requerido'] ?? '') ?>"></div>
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label text-warning">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?= htmlspecialchars($editar_arma['descripcion'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="imagen" class="form-label text-warning">Imagen <?= $editar_arma ? "(Opcional para cambiar)" : "" ?></label>
                    <input class="form-control" type="file" id="imagen" name="imagen" accept="image/*" <?= $editar_arma ? "" : "required" ?>>
                </div>

                <button type="submit" class="btn btn-warning w-100"><?= $editar_arma ? "Editar Arma" : "Agregar Arma" ?></button>
            </form>
        </div>

        <!-- Tabla de armas -->
        <h2 class="text-warning mb-3">Armas Existentes</h2>
        <table class="table table-dark table-striped text-center align-middle">
            <thead class="table-warning text-dark">
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Nombre</th>
                    <th>Daño</th>
                    <th>Mun. Max</th>
                    <th>Mun. Total</th>
                    <th>Cadencia</th>
                    <th>Precisión</th>
                    <th>Nivel Req</th>
                    <th>Imagen</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($armas as $a): ?>
                    <tr>
                        <td><?= $a['id_arma'] ?></td>
                        <td>
                            <?php
                            $tipo = array_filter($tipos_armas, fn($t) => $t['id_tipo'] == $a['id_tipo']);
                            echo htmlspecialchars($tipo[array_key_first($tipo)]['nombre_tipo'] ?? '');
                            ?>
                        </td>
                        <td><?= htmlspecialchars($a['nombre']) ?></td>
                        <td><?= $a['dano'] ?></td>
                        <td><?= $a['municion_max'] ?></td>
                        <td><?= $a['municion_total'] ?></td>
                        <td><?= $a['cadencia'] ?></td>
                        <td><?= $a['precision_porcentaje'] ?>%</td>
                        <td><?= $a['nivel_requerido'] ?></td>
                        <td><img src="../../../<?= $a['imagen'] ?>" alt="<?= htmlspecialchars($a['nombre']) ?>" width="80"></td>
                        <td>
                            <a href="add_arma.php?editar=<?= $a['id_arma'] ?>" class="btn btn-sm btn-warning mb-1">Editar</a>
                            <a href="add_arma.php?eliminar=<?= $a['id_arma'] ?>" onclick="return confirm('¿Seguro que deseas eliminar esta arma?');" class="btn btn-sm btn-danger">Eliminar</a>
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