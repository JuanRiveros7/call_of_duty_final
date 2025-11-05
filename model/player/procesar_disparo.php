<?php
session_start();
$debug = isset($_GET['debug']) && $_GET['debug'] == 1;

require_once __DIR__ . '/../../db/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../../login.php");
    exit;
}

$db = new Database();
$con = $db->conectar();

$id_usuario = $_SESSION['id_usuario']; // atacante
$id_partida = intval($_POST['id_partida'] ?? 0);
$id_objetivo = intval($_POST['objetivo'] ?? 0);
$id_arma = intval($_POST['arma'] ?? 0);
$id_zona = intval($_POST['zona'] ?? 0);

// NUEVO: 0. VERIFICAR QUE EL ATACANTE SIGUE VIVO
$stmt = $con->prepare("SELECT salud_actual, estado FROM partida_jugadores WHERE id_partida = :id_partida AND id_usuario = :id_usuario");
$stmt->execute([':id_partida' => $id_partida, ':id_usuario' => $id_usuario]);
$atacante = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$atacante || $atacante['salud_actual'] <= 0 || $atacante['estado'] === 'eliminado') {
    echo "<script>
        alert('⚠️ No puedes disparar porque estás eliminado o muerto.');
        window.location.href='lobby.php';
    </script>";
    exit;
}

// 1. OBTENER DAÑO DEL ARMA
$stmt = $con->prepare("SELECT dano, nombre FROM armas WHERE id_arma = :id");
$stmt->execute([':id' => $id_arma]);
$arma = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$arma) die("Arma no encontrada.");

$nombre_arma = $arma['nombre'];
$daño_base = (int)$arma['dano'];
if ($debug) echo "DEBUG: Arma '$nombre_arma', Daño base: $daño_base<br>";

// 2. DEFINIR MULTIPLICADOR SEGÚN ZONA
$stmt = $con->prepare("SELECT nombre, multiplicador FROM zonas_dano WHERE id_zona = :id");
$stmt->execute([':id' => $id_zona]);
$zona = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$zona) {
    $zona_nombre = "Desconocida";
    $multiplicador = 1.0;
} else {
    $zona_nombre = $zona['nombre'];
    $multiplicador = (float)$zona['multiplicador'];
}
if ($debug) echo "DEBUG: Zona '$zona_nombre', Multiplicador: $multiplicador<br>";

// 3. CALCULAR DAÑO FINAL
$daño_total = round($daño_base * $multiplicador);
if ($debug) echo "DEBUG: Daño total calculado: $daño_total<br>";

// 4. RESTAR VIDA AL OBJETIVO
$stmt = $con->prepare("SELECT salud_actual FROM partida_jugadores WHERE id_partida = :id_partida AND id_usuario = :id_objetivo");
$stmt->execute([':id_partida' => $id_partida, ':id_objetivo' => $id_objetivo]);
$jugador = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$jugador) die("Jugador objetivo no encontrado en esta partida.");

$vida_actual = (int)$jugador['salud_actual'];
$nueva_vida = max(0, $vida_actual - $daño_total);

// Actualizar vida del objetivo
$stmt = $con->prepare("
    UPDATE partida_jugadores 
    SET salud_actual = :nueva_vida 
    WHERE id_partida = :id_partida AND id_usuario = :id_objetivo
");
$stmt->execute([
    ':nueva_vida' => $nueva_vida,
    ':id_partida' => $id_partida,
    ':id_objetivo' => $id_objetivo
]);
if ($debug) echo "DEBUG: Nueva vida del objetivo: $nueva_vida<br>";

// 5. REGISTRAR EL DAÑO EN HISTORIAL
$stmt = $con->prepare("
    INSERT INTO historial_dano (id_partida, id_atacante, id_victima, id_arma, id_zona, dano_aplicado, fecha)
    VALUES (:id_partida, :id_atacante, :id_objetivo, :id_arma, :zona, :dano, NOW())
");
$stmt->execute([
    ':id_partida' => $id_partida,
    ':id_atacante' => $id_usuario,
    ':id_objetivo' => $id_objetivo,
    ':id_arma' => $id_arma,
    ':zona' => $id_zona,
    ':dano' => $daño_total
]);
if ($debug) echo "DEBUG: Daño registrado en historial: " . $stmt->rowCount() . " fila(s)<br>";

// 6. COMPROBAR SI EL JUGADOR MURIÓ
if ($nueva_vida <= 0) {
    $stmt = $con->prepare("
        UPDATE partida_jugadores 
        SET estado = 'eliminado' 
        WHERE id_partida = :id_partida AND id_usuario = :id_objetivo
    ");
    $stmt->execute([':id_partida' => $id_partida, ':id_objetivo' => $id_objetivo]);
    if ($debug) echo "DEBUG: Jugador eliminado<br>";

    // Si el jugador eliminado es el mismo que está logueado, lo saca de la partida
    if ($id_objetivo == $_SESSION['id_usuario']) {
        echo "<script>
            alert('⚠️ Has sido eliminado de la partida.');
            window.location.href='lobby.php';
        </script>";
        exit;
    }
}

// 7. COMPROBAR SI HAY UN GANADOR
$stmt = $con->prepare("
    SELECT COUNT(*) AS vivos 
    FROM partida_jugadores 
    WHERE id_partida = :id_partida AND salud_actual > 0
");
$stmt->execute([':id_partida' => $id_partida]);
$vivos = $stmt->fetchColumn();
if ($debug) echo "DEBUG: Jugadores vivos: $vivos<br>";

if ($vivos <= 1) {
    // Último jugador vivo
    $stmt = $con->prepare("SELECT id_usuario FROM partida_jugadores WHERE id_partida = :id_partida AND salud_actual > 0 LIMIT 1");
    $stmt->execute([':id_partida' => $id_partida]);
    $id_ganador = $stmt->fetchColumn();

    // Finalizar partida
    $stmt = $con->prepare("UPDATE partidas SET estado = 'finalizada', fecha_fin = NOW() WHERE id_partida = :id_partida");
    $stmt->execute([':id_partida' => $id_partida]);
    if ($debug) echo "DEBUG: Partida finalizada<br>";

    // 8. ESTADÍSTICAS DEL JUGADOR
    $stmt = $con->prepare("SELECT COALESCE(SUM(dano_aplicado),0) AS total_dano FROM historial_dano WHERE id_partida = :id_partida AND id_atacante = :id_usuario");
    $stmt->execute([':id_partida' => $id_partida, ':id_usuario' => $id_usuario]);
    $total_dano = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_dano'];

    $stmt = $con->prepare("SELECT COALESCE(SUM(dano_aplicado),0) AS total_recibido FROM historial_dano WHERE id_partida = :id_partida AND id_victima = :id_usuario");
    $stmt->execute([':id_partida' => $id_partida, ':id_usuario' => $id_usuario]);
    $total_recibido = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_recibido'];

    $stmt = $con->prepare("
        SELECT COUNT(DISTINCT id_victima) AS kills 
        FROM historial_dano 
        JOIN partida_jugadores pj ON pj.id_usuario = historial_dano.id_victima
        WHERE historial_dano.id_partida = :id_partida 
          AND historial_dano.id_atacante = :id_usuario 
          AND pj.salud_actual <= 0
    ");
    $stmt->execute([':id_partida' => $id_partida, ':id_usuario' => $id_usuario]);
    $kills = (int)$stmt->fetch(PDO::FETCH_ASSOC)['kills'];

    if ($debug) echo "DEBUG: Estadísticas -> Daño: $total_dano, Recibido: $total_recibido, Kills: $kills<br>";

    // 9. CÁLCULO PERSONALIZADO DE PUNTOS
    $resultado = ($id_usuario == $id_ganador) ? 'victoria' : 'derrota';
    $puntos_base = ($resultado == 'victoria') ? 100 : 50;
    $puntos_desempeno = ($kills * 20) + intval($total_dano / 100) - intval($total_recibido / 150);
    $puntos = max(0, $puntos_base + $puntos_desempeno);
    if ($debug) echo "DEBUG: Resultado: $resultado, Puntos obtenidos: $puntos<br>";

    // 10. INSERTAR REPORTE FINAL
    $stmt = $con->prepare("SELECT id_mapa, modo_juego FROM partidas WHERE id_partida = :id_partida");
    $stmt->execute([':id_partida' => $id_partida]);
    $partida_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $id_mapa = $partida_info['id_mapa'] ?? 1;
    $modo = $partida_info['modo_juego'] ?? 'Desconocido';

    $stmt = $con->prepare("
        INSERT INTO reporte_partidas (
            id_partida, id_usuario, id_mapa, modo_juego, resultado, 
            kills, dano_causado, dano_recibido, tiros_cabeza, 
            puntos_obtenidos, nivel_al_jugar, duracion_segundos, fecha_partida
        ) VALUES (
            :id_partida, :id_usuario, :id_mapa, :modo, :resultado, 
            :kills, :dano_causado, :dano_recibido, 0, 
            :puntos, 1, 60, NOW()
        )
    ");
    $stmt->execute([
        ':id_partida' => $id_partida,
        ':id_usuario' => $id_usuario,
        ':id_mapa' => $id_mapa,
        ':modo' => $modo,
        ':resultado' => $resultado,
        ':kills' => $kills,
        ':dano_causado' => $total_dano,
        ':dano_recibido' => $total_recibido,
        ':puntos' => $puntos
    ]);

    // 10.1 ACTUALIZAR PUNTOS DEL USUARIO
    $update = $con->prepare("UPDATE usuarios SET puntos_totales = puntos_totales + :puntos WHERE id_usuario = :id_usuario");
    $update->execute([':puntos' => $puntos, ':id_usuario' => $id_usuario]);

    // 10.2 ACTUALIZAR NIVEL Y RANGO
    $stmt = $con->prepare("SELECT puntos_totales FROM usuarios WHERE id_usuario = :id_usuario");
    $stmt->execute([':id_usuario' => $id_usuario]);
    $puntos_totales = $stmt->fetchColumn();

    $stmt = $con->prepare("SELECT id_nivel FROM niveles WHERE puntos_requeridos <= :puntos ORDER BY puntos_requeridos DESC LIMIT 1");
    $stmt->execute([':puntos' => $puntos_totales]);
    $id_nivel_nuevo = $stmt->fetchColumn();
    if ($id_nivel_nuevo) $con->prepare("UPDATE usuarios SET id_nivel = :nivel WHERE id_usuario = :id_usuario")->execute([':nivel' => $id_nivel_nuevo, ':id_usuario' => $id_usuario]);

    $stmt = $con->prepare("SELECT id_rango FROM rangos WHERE :nivel BETWEEN nivel_minimo AND nivel_maximo LIMIT 1");
    $stmt->execute([':nivel' => $id_nivel_nuevo]);
    $id_rango_nuevo = $stmt->fetchColumn();
    if ($id_rango_nuevo) $con->prepare("UPDATE usuarios SET id_rango = :rango WHERE id_usuario = :id_usuario")->execute([':rango' => $id_rango_nuevo, ':id_usuario' => $id_usuario]);

    // 11. ALERTA FINAL
    if ($id_usuario == $id_ganador) {
        echo "<script>alert('🏆 ¡Felicidades! Has ganado la partida. Puntos obtenidos: $puntos'); window.location.href='lobby.php';</script>";
    } else {
        echo "<script>alert('La partida ha finalizado. El ganador es otro jugador. Puntos obtenidos: $puntos'); window.location.href='lobby.php';</script>";
    }
    exit;
}

// 12. SI NO HAY GANADOR TODAVÍA
echo "<script>
alert('Has hecho $daño_total de daño al jugador (Zona: $zona_nombre). Nueva vida del enemigo: $nueva_vida%');
window.location.href='partida.php?id_partida=$id_partida';
</script>";
