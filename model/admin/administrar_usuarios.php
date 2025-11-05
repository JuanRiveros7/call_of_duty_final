<?php
session_start();

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../../login.php");
  exit();
}

require_once('../../db/conexion.php');
$db = new Database();
$conexion = $db->conectar();

$id_admin = (int) $_SESSION['id_usuario'];

try {
  $query = $conexion->prepare("
    SELECT 
      u.id_usuario, 
      u.nombre_usuario, 
      u.email, 
      e.nombre_estado, 
      u.ultimo_inicio_sesion, 
      r.nombre_rol
    FROM usuarios u
    INNER JOIN roles r ON u.id_rol = r.id_rol
    INNER JOIN estados_usuario e ON u.id_estado = e.id_estado
    ORDER BY u.id_usuario ASC
  ");
  $query->execute();
  $usuarios = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log("Error al obtener usuarios: " . $e->getMessage());
  die("Error al cargar los usuarios. Intenta más tarde.");
}

$pendientes = array_filter($usuarios, function ($u) {
  return strtolower(trim($u['nombre_estado'])) === 'pendiente';
});
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración - Call of Duty</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #0b0b0b;
    }

    .table-dark th,
    .table-dark td {
      vertical-align: middle;
    }
  </style>
</head>

<body class="bg-dark text-light">

  <nav class="navbar navbar-expand-lg" style="background-color: #111;">
    <div class="container-fluid">
      <a class="navbar-brand text-warning fw-bold" href="index_admin.php">
        <i class="bi bi-shield-lock-fill"></i> Admin COD
      </a>
      <button class="navbar-toggler bg-warning" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link text-light" href="index_admin.php">Inicio</a></li>
          <li class="nav-item"><a class="nav-link text-light" href="historial_admin.php">Historial</a></li>
          <li class="nav-item"><a class="nav-link text-warning" href="../../controller/cerrar_sesion.php">Cerrar Sesión</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container py-5">
    <div class="text-center mb-5">
      <h1 class="fw-bold text-warning">Panel de Control - Administrador</h1>
      <p class="text-secondary">Gestiona los usuarios: activos, bloqueados, inactivos o pendientes de aprobación.</p>
    </div>

    <!-- USUARIOS PENDIENTES -->
    <div class="card bg-secondary mb-4 border-warning">
      <div class="card-header bg-dark text-warning fw-bold">
        <i class="bi bi-hourglass-split"></i> Usuarios Pendientes de Activación
      </div>
      <div class="card-body">
        <table class="table table-dark table-striped align-middle text-center">
          <thead class="table-warning text-dark">
            <tr>
              <th>ID</th>
              <th>Usuario</th>
              <th>Correo</th>
              <th>Último Login</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($pendientes) === 0): ?>
              <tr>
                <td colspan="5">No hay usuarios pendientes de activación.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($pendientes as $u): ?>
                <tr>
                  <td><?= htmlspecialchars($u['id_usuario']) ?></td>
                  <td><?= htmlspecialchars($u['nombre_usuario']) ?></td>
                  <td><?= htmlspecialchars($u['email']) ?></td>
                  <td><?= $u['ultimo_inicio_sesion'] ?? '—' ?></td>
                  <td>
                    <?php if ((int)$u['id_usuario'] === $id_admin): ?>
                      <span class="text-muted">—</span>
                    <?php else: ?>
                      <button class="btn btn-success btn-sm btn-accion" data-accion="activar" data-id="<?= $u['id_usuario'] ?>">
                        <i class="bi bi-check-circle"></i> Activar
                      </button>
                      <button class="btn btn-danger btn-sm btn-accion" data-accion="bloquear" data-id="<?= $u['id_usuario'] ?>">
                        <i class="bi bi-x-circle"></i> Rechazar
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- LISTA DE USUARIOS -->
    <div class="card bg-secondary border-warning">
      <div class="card-header bg-dark text-warning fw-bold">
        <i class="bi bi-people-fill"></i> Lista de Usuarios
      </div>
      <div class="card-body">
        <table class="table table-dark table-hover align-middle text-center" id="tablaUsuarios">
          <thead class="table-warning text-dark">
            <tr>
              <th>ID</th>
              <th>Usuario</th>
              <th>Rol</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios as $u):
              $estado = strtolower(trim($u['nombre_estado']));
              $isSelf = ((int)$u['id_usuario'] === $id_admin);
            ?>
              <tr data-estado="<?= $estado ?>">
                <td><?= htmlspecialchars($u['id_usuario']) ?></td>
                <td><?= htmlspecialchars($u['nombre_usuario']) ?></td>
                <td><?= htmlspecialchars($u['nombre_rol']) ?></td>
                <td>
                  <?php if ($estado === 'activo'): ?>
                    <span class="badge bg-success">Activo</span>
                  <?php elseif ($estado === 'bloqueado' || $estado === 'baneado'): ?>
                    <span class="badge bg-danger"><?= ucfirst($estado) ?></span>
                  <?php elseif ($estado === 'pendiente'): ?>
                    <span class="badge bg-warning text-dark">Pendiente</span>
                  <?php elseif ($estado === 'inactivo'): ?>
                    <span class="badge bg-secondary">Inactivo</span>
                  <?php else: ?>
                    <span class="badge bg-secondary"><?= htmlspecialchars($u['nombre_estado']) ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($isSelf): ?>
                    <span class="text-muted">—</span>
                  <?php else: ?>
                    <?php if ($estado === 'activo'): ?>
                      <button class="btn btn-warning btn-sm btn-accion" data-accion="inactivar" data-id="<?= $u['id_usuario'] ?>">
                        <i class="bi bi-pause-circle"></i> Inactivar
                      </button>
                      <button class="btn btn-danger btn-sm btn-accion" data-accion="banear" data-id="<?= $u['id_usuario'] ?>">
                        <i class="bi bi-slash-circle"></i> Banear
                      </button>
                    <?php elseif ($estado === 'inactivo' || $estado === 'bloqueado' || $estado === 'baneado'): ?>
                      <button class="btn btn-success btn-sm btn-accion" data-accion="activar" data-id="<?= $u['id_usuario'] ?>">
                        <i class="bi bi-check-circle"></i> Activar
                      </button>
                    <?php elseif ($estado === 'pendiente'): ?>
                      <button class="btn btn-success btn-sm btn-accion" data-accion="activar" data-id="<?= $u['id_usuario'] ?>">
                        <i class="bi bi-check-circle"></i> Activar
                      </button>
                      <button class="btn btn-danger btn-sm btn-accion" data-accion="bloquear" data-id="<?= $u['id_usuario'] ?>">
                        <i class="bi bi-x-circle"></i> Rechazar
                      </button>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <footer class="text-center py-3 mt-5" style="background-color: #111;">
    <p class="mb-0 text-secondary">© 2025 Call of Duty Admin Panel | Desarrollado por sMonkey7</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener('click', async e => {
      if (e.target.closest('.btn-accion')) {
        const btn = e.target.closest('.btn-accion');
        const accion = btn.dataset.accion;
        const idUsuario = btn.dataset.id;

        if (!confirm(`¿Seguro que deseas ${accion} al usuario ID ${idUsuario}?`)) return;

        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
          const res = await fetch('acciones_admin.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `accion=${accion}&id_usuario=${idUsuario}`
          });
          const data = await res.json();
          alert(data.message);
          if (data.status === 'ok') location.reload();
        } catch (err) {
          alert(' Error al conectar con el servidor.');
        } finally {
          btn.disabled = false;
          btn.innerHTML = original;
        }
      }
    });
  </script>
</body>

</html>