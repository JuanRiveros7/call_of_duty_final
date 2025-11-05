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
$alert_type = 'warning'; // default

// --- Procesar agregar o editar avatar ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_editar = $_POST['id_editar'] ?? null;
    $nombre = trim($_POST['nombre']);
    $imagen = $_FILES['imagen'] ?? null;

    if (strlen($nombre) < 3) {
        $mensaje = "El nombre debe tener al menos 3 caracteres.";
        $alert_type = 'danger';
    } else {
        $ruta_db = null;

        if ($imagen && $imagen['name'] != '') {
            $ext = pathinfo($imagen['name'], PATHINFO_EXTENSION);
            $nombre_seguro = preg_replace("/[^a-zA-Z0-9]/", "_", $nombre);
            $nombre_imagen = $nombre_seguro . "." . $ext;
            $ruta_destino = "../../../assets/img/avatar/" . $nombre_imagen;
            $ruta_db = "assets/img/avatar/" . $nombre_imagen;

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
                // Editar avatar
                if ($ruta_db) {
                    $sql = "UPDATE avatar SET nombre=:nombre, url_imagen=:imagen WHERE id_avatar=:id";
                    $stmt = $con->prepare($sql);
                    $stmt->bindParam(':imagen', $ruta_db);
                } else {
                    $sql = "UPDATE avatar SET nombre=:nombre WHERE id_avatar=:id";
                    $stmt = $con->prepare($sql);
                }
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':id', $id_editar);
                if ($stmt->execute()) {
                    $mensaje = "Avatar editado correctamente.";
                    $alert_type = 'success';
                } else {
                    $mensaje = "Error al editar avatar.";
                    $alert_type = 'danger';
                }
            } else {
                // Insertar avatar
                $sql = "INSERT INTO avatar (nombre, url_imagen) VALUES (:nombre, :imagen)";
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':imagen', $ruta_db);
                if ($stmt->execute()) {
                    $mensaje = "Avatar agregado correctamente.";
                    $alert_type = 'success';
                } else {
                    $mensaje = "Error al agregar avatar.";
                    $alert_type = 'danger';
                }
            }
        }
    }
}

// --- Eliminar avatar ---
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $sql_img = $con->prepare("SELECT url_imagen FROM avatar WHERE id_avatar=:id");
    $sql_img->bindParam(':id', $id);
    $sql_img->execute();
    $img = $sql_img->fetch(PDO::FETCH_ASSOC);
    if ($img && file_exists("../../../" . $img['url_imagen'])) {
        unlink("../../../" . $img['url_imagen']);
    }

    $sql = $con->prepare("DELETE FROM avatar WHERE id_avatar=:id");
    $sql->bindParam(':id', $id);
    if ($sql->execute()) {
        $mensaje = "Avatar eliminado correctamente.";
        $alert_type = 'success';
    } else {
        $mensaje = "Error al eliminar avatar.";
        $alert_type = 'danger';
    }
}

// --- Cargar datos para editar ---
$id_editar = $_GET['editar'] ?? null;
$editar_avatar = null;
if ($id_editar) {
    $stmt = $con->prepare("SELECT * FROM avatar WHERE id_avatar=:id");
    $stmt->bindParam(':id', $id_editar);
    $stmt->execute();
    $editar_avatar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- Obtener todos los avatares ---
$avatares = $con->query("SELECT * FROM avatar ORDER BY id_avatar DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin Avatares - COD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">
    <div class="container py-5">
        <h1 class="text-center text-warning mb-4"><?= $editar_avatar ? "Editar Avatar" : "Agregar Nuevo Avatar" ?></h1>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $alert_type ?> text-center"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="card bg-dark border-warning p-4 shadow mb-4">
            <form action="add_avatar.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_editar" value="<?= $editar_avatar['id_avatar'] ?? '' ?>">
                <div class="mb-3">
                    <label for="nombre" class="form-label text-warning">Nombre del Avatar</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required value="<?= htmlspecialchars($editar_avatar['nombre'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="imagen" class="form-label text-warning">Imagen <?= $editar_avatar ? "(Opcional para cambiar)" : "" ?></label>
                    <input class="form-control" type="file" id="imagen" name="imagen" accept="image/*" <?= $editar_avatar ? "" : "required" ?>>
                </div>

                <button type="submit" class="btn btn-warning w-100"><?= $editar_avatar ? "Editar Avatar" : "Agregar Avatar" ?></button>
            </form>
        </div>

        <!-- Tabla de avatares -->
        <h2 class="text-warning mb-3">Avatares Existentes</h2>
        <table class="table table-dark table-striped text-center align-middle">
            <thead class="table-warning text-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Imagen</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($avatares as $a): ?>
                    <tr>
                        <td><?= $a['id_avatar'] ?></td>
                        <td><?= htmlspecialchars($a['nombre']) ?></td>
                        <td><img src="../../../<?= $a['url_imagen'] ?>" alt="<?= htmlspecialchars($a['nombre']) ?>" width="80"></td>
                        <td>
                            <a href="add_avatar.php?editar=<?= $a['id_avatar'] ?>" class="btn btn-sm btn-warning mb-1">Editar</a>
                            <a href="add_avatar.php?eliminar=<?= $a['id_avatar'] ?>" onclick="return confirm('¿Seguro que deseas eliminar este avatar?');" class="btn btn-sm btn-danger">Eliminar</a>
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