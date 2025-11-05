<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0"); // evita cacheo en algunos navegadores

require_once __DIR__ . '/../../db/conexion.php';
$db = new Database();
$con = $db->conectar();

// Verificar sesión y rol
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['player', 'admin'])) {
  header("Location: ../../login.php");
  exit;
}

if (!isset($_SESSION['id_usuario'])) {
  header("Location: ../../login.php");
  exit;
}

$id_usuario = $_SESSION['id_usuario'];

// Consultar datos del usuario
$sql = "
    SELECT 
        u.nombre_usuario,
        u.puntos_totales,
        n.nombre_nivel
    FROM usuarios u
    LEFT JOIN niveles n ON u.id_nivel = n.id_nivel
    WHERE u.id_usuario = :id
";
$stmt = $con->prepare($sql);
$stmt->execute([':id' => $id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Si el usuario no existe, cerrar sesión por seguridad
if (!$usuario) {
  session_unset();
  session_destroy();
  header("Location: ../../login.php");
  exit;
}

// Cerrar sesión si el usuario lo solicita
if (isset($_POST['cerrar'])) {
  session_unset();
  session_destroy();
  header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
  header("Pragma: no-cache");
  header("Expires: 0");
  header("Location: ../../login.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Call of Duty</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
  <!-- Navbar -->
  <?php include '../../includes/navbar.php'; ?>

  <!-- Video Home -->
  <section id="home" class="video-container bg-black text-center text-white py-5">
    <div class="container">
      <h2 class="mb-4 text-warning fw-bold">Tráiler Oficial</h2>
      <div class="ratio ratio-16x9 shadow-lg border border-warning">
        <iframe
          src="https://www.youtube.com/embed/9Texnt09-kw?autoplay=1&mute=1&loop=1&playlist=9Texnt09-kw"
          title="Call of Duty Trailer"
          allow="autoplay; encrypted-media"
          allowfullscreen></iframe>
      </div>
    </div>
  </section>

  <!-- Personajes -->
  <section id="personajes" class="py-5 text-white text-center bg-black">
    <div class="container">
      <h2 class="mb-4">Personajes Disponibles</h2>
      <div class="row g-4">
        <!-- Personajes predefinidos -->
        <div class="col-md-4">
          <div class="card bg-dark text-white h-100">
            <img src="../../assets/img/pj/ghost.webp" class="card-img-top" alt="Ghost">
            <div class="card-body">
              <h5 class="card-title">Ghost</h5>
              <p class="card-text">Soldado británico de élite, famoso por su máscara de calavera y sigilo.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card bg-dark text-white h-100">
            <img src="../../assets/img/pj/hidora_kai.webp" class="card-img-top" alt="Hidora Kai">
            <div class="card-body">
              <h5 class="card-title">Hidora Kai</h5>
              <p class="card-text">Villano japonés, líder criminal ambicioso y despiadado.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card bg-dark text-white h-100">
            <img src="../../assets/img/pj/outrider.webp" class="card-img-top" alt="Outrider">
            <div class="card-body">
              <h5 class="card-title">Outrider</h5>
              <p class="card-text">Francotiradora cubana experta en rastreo y combate con arco.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Armas -->
  <section id="armas" class="py-5 bg-dark text-white text-center">
    <div class="container">
      <h2 class="mb-4">Armas Disponibles</h2>
      <div class="row g-4">

        <!-- Puños -->
        <div class="col-md-3">
          <h4>Puño</h4>
          <div class="card text-white">
            <img src="../../assets/img/armas/puño/puño_clasico.png" class="card-img-top" alt="Puño clásico">
            <div class="card-body">
              <p class="card-text">Puño clásico</p>
            </div>
          </div>
          <div class="card text-white mt-3">
            <img src="../../assets/img/armas/puño/puño_reforzados.png" class="card-img-top" alt="Puño reforzado">
            <div class="card-body">
              <p class="card-text">Puño reforzado</p>
            </div>
          </div>
          <div class="card text-white mt-3">
            <img src="../../assets/img/armas/puño/puño_metalico.png" class="card-img-top" alt="Puño metálico">
            <div class="card-body">
              <p class="card-text">Puño metálico</p>
            </div>
          </div>
        </div>

        <!-- Pistolas -->
        <div class="col-md-3">
          <h4>Pistolas</h4>
          <div class="card text-white">
            <img src="../../assets/img/armas/pistola/m1911.png" class="card-img-top" alt="M1911">
            <div class="card-body">
              <p class="card-text">M1911</p>
            </div>
          </div>
          <div class="card text-white mt-3">
            <img src="../../assets/img/armas/pistola/desert_eagle.png" class="card-img-top" alt="Desert Eagle">
            <div class="card-body">
              <p class="card-text">Desert Eagle</p>
            </div>
          </div>
          <div class="card text-white mt-3">
            <img src="../../assets/img/armas/pistola/glock_18.png" class="card-img-top" alt="Glock 18">
            <div class="card-body">
              <p class="card-text">Glock 18</p>
            </div>
          </div>
        </div>

        <!-- Francotiradores -->
        <div class="col-md-3">
          <h4>Francotiradores</h4>
          <div class="card text-white">
            <img src="../../assets/img/armas/francotirador/barrett_50.png" class="card-img-top" alt="Barrett 50">
            <div class="card-body">
              <p class="card-text">Barrett .50</p>
            </div>
          </div>
          <div class="card text-white mt-3">
            <img src="../../assets/img/armas/francotirador/dragunov.webp" class="card-img-top" alt="Dragunov">
            <div class="card-body">
              <p class="card-text">Dragunov</p>
            </div>
          </div>
          <div class="card text-white mt-3">
            <img src="../../assets/img/armas/francotirador/ax_50.png" class="card-img-top" alt="AX-50">
            <div class="card-body">
              <p class="card-text">AX-50</p>
            </div>
          </div>
        </div>

        <!-- Ametralladoras -->
        <div class="col-md-3">
          <h4>Ametralladoras</h4>
          <div class="card text-white">
            <img src="../../assets/img/armas/ametralladora/m249_saw.webp" class="card-img-top" alt="M249 SAW">
            <div class="card-body">
              <p class="card-text">M249 SAW</p>
            </div>
          </div>
          <div class="card text-white mt-3">
            <img src="../../assets/img/armas/ametralladora/pkm.webp" class="card-img-top" alt="PKM">
            <div class="card-body">
              <p class="card-text">PKM</p>
            </div>
          </div>
          <div class="card text-white mt-3">
            <img src="../../assets/img/armas/ametralladora/rpd.webp" class="card-img-top" alt="RPD">
            <div class="card-body">
              <p class="card-text">RPD</p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- Jugar ahora -->
  <section id="jugar" class="py-5 bg-black text-center text-white">
    <div class="container">
      <h2>¿Listo para la acción?</h2>
      <a href="lobby.php" class="btn btn-danger btn-lg mt-3">Jugar Ahora</a>
    </div>
  </section>

  <script>
    const sections = document.querySelectorAll("section");
    const navLinks = document.querySelectorAll(".nav-link");

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        const id = entry.target.getAttribute("id");
        const link = document.querySelector(`.nav-link[href="#${id}"]`);
        if (entry.isIntersecting) {
          navLinks.forEach(l => l.classList.remove("active-link"));
          if (link) link.classList.add("active-link");
        }
      });
    }, {
      threshold: 0.6
    });

    sections.forEach(section => observer.observe(section));
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>