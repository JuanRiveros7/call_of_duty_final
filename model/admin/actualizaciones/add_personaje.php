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

// --- Procesar agregar o editar personaje ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_editar = $_POST['id_editar'] ?? null;
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
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
            $ext = pathinfo($imagen['name'], PATHINFO_EXTENSION);
            $nombre_seguro = preg_replace("/[^a-zA-Z0-9]/", "_", $nombre);
            $nombre_imagen = $nombre_seguro . "." . $ext;
            $ruta_destino = "../../../assets/img/pj/" . $nombre_imagen;
            $ruta_db = "assets/img/pj/" . $nombre_imagen;

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
                // Editar personaje
                if ($ruta_db) {
                    $sql = "UPDATE personajes SET nombre=:nombre, descripcion=:descripcion, imagen=:imagen WHERE id_personaje=:id";
                    $stmt = $con->prepare($sql);
                    $stmt->bindParam(':imagen', $ruta_db);
                } else {
                    $sql = "UPDATE personajes SET nombre=:nombre, descripcion=:descripcion WHERE id_personaje=:id";
                    $stmt = $con->prepare($sql);
                }
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':id', $id_editar);
                if ($stmt->execute()) {
                    $mensaje = "Personaje editado correctamente.";
                    $alert_type = 'success';
                } else {
                    $mensaje = "Error al editar personaje.";
                    $alert_type = 'danger';
                }
            } else {
                // Insertar personaje
                $sql = "INSERT INTO personajes (nombre, descripcion, imagen) VALUES (:nombre, :descripcion, :imagen)";
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':imagen', $ruta_db);
                if ($stmt->execute()) {
                    $mensaje = "Personaje agregado correctamente.";
                    $alert_type = 'success';
                } else {
                    $mensaje = "Error al agregar personaje.";
                    $alert_type = 'danger';
                }
            }
        }
    }
}

// --- Eliminar personaje ---
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    // Opcional: borrar la imagen del servidor
    $sql_img = $con->prepare("SELECT imagen FROM personajes WHERE id_personaje=:id");
    $sql_img->bindParam(':id', $id);
    $sql_img->execute();
    $img = $sql_img->fetch(PDO::FETCH_ASSOC);
    if ($img && file_exists("../../../" . $img['imagen'])) {
        unlink("../../../" . $img['imagen']);
    }

    $sql = $con->prepare("DELETE FROM personajes WHERE id_personaje=:id");
    $sql->bindParam(':id', $id);
    if ($sql->execute()) {
        $mensaje = "Personaje eliminado correctamente.";
        $alert_type = 'success';
    } else {
        $mensaje = "Error al eliminar personaje.";
        $alert_type = 'danger';
    }
}

// --- Cargar datos para editar ---
$id_editar = $_GET['editar'] ?? null;
$editar_personaje = null;
if ($id_editar) {
    $stmt = $con->prepare("SELECT * FROM personajes WHERE id_personaje=:id");
    $stmt->bindParam(':id', $id_editar);
    $stmt->execute();
    $editar_personaje = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- Obtener todos los personajes ---
$personajes = $con->query("SELECT * FROM personajes ORDER BY id_personaje DESC")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin Personajes - COD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">
    <div class="container py-5">
        <h1 class="text-center text-warning mb-4"><?= $editar_personaje ? "Editar Personaje" : "Agregar Nuevo Personaje" ?></h1>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $alert_type ?> text-center"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="card bg-dark border-warning p-4 shadow mb-4">
            <form action="add_personaje.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_editar" value="<?= $editar_personaje['id_personaje'] ?? '' ?>">
                <div class="mb-3">
                    <label for="nombre" class="form-label text-warning">Nombre del Personaje</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required value="<?= htmlspecialchars($editar_personaje['nombre'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label text-warning">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?= htmlspecialchars($editar_personaje['descripcion'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="imagen" class="form-label text-warning">Imagen <?= $editar_personaje ? "(Opcional para cambiar)" : "" ?></label>
                    <input class="form-control" type="file" id="imagen" name="imagen" accept="image/*" <?= $editar_personaje ? "" : "required" ?>>
                </div>

                <button type="submit" class="btn btn-warning w-100"><?= $editar_personaje ? "Editar Personaje" : "Agregar Personaje" ?></button>
            </form>
        </div>

        <!-- Tabla de personajes -->
        <h2 class="text-warning mb-3">Personajes Existentes</h2>
        <table class="table table-dark table-striped text-center align-middle">
            <thead class="table-warning text-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Imagen</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($personajes as $p): ?>
                    <tr>
                        <td><?= $p['id_personaje'] ?></td>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= htmlspecialchars($p['descripcion']) ?></td>
                        <td><img src="../../../<?= $p['imagen'] ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" width="80"></td>
                        <td>
                            <a href="add_personaje.php?editar=<?= $p['id_personaje'] ?>" class="btn btn-sm btn-warning mb-1">Editar</a>
                            <a href="add_personaje.php?eliminar=<?= $p['id_personaje'] ?>" onclick="return confirm('¿Seguro que deseas eliminar este personaje?');" class="btn btn-sm btn-danger">Eliminar</a>
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