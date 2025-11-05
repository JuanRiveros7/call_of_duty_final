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
$id_partida = $_GET['id_partida'] ?? null;

if (!$id_partida) {
    header("Location: mapas.php");
    exit;
}

// Verificar si la partida existe y está esperando
$sql = $con->prepare("SELECT * FROM partidas WHERE id_partida = ?");
$sql->execute([$id_partida]);
$partida = $sql->fetch(PDO::FETCH_ASSOC);

if (!$partida) {
    echo "La partida no existe.";
    exit;
}

if ($partida['estado'] !== 'esperando') {
    echo "La partida ya está en curso o finalizada.";
    exit;
}

// Verificar si el usuario ya está en la partida
$check = $con->prepare("SELECT * FROM partida_jugadores WHERE id_partida = ? AND id_usuario = ?");
$check->execute([$id_partida, $id_usuario]);

if ($check->rowCount() > 0) {
    header("Location: sala.php?id_partida=$id_partida");
    exit;
}

// Contar cuántos jugadores hay
$count = $con->prepare("SELECT COUNT(*) FROM partida_jugadores WHERE id_partida = ?");
$count->execute([$id_partida]);
$total = $count->fetchColumn();

// Si está llena, no se puede unir
if ($total >= $partida['jugadores_max']) {
    echo "La sala está llena.";
    exit;
}

// Insertar al jugador
$insert = $con->prepare("
    INSERT INTO partida_jugadores (id_partida, id_usuario, id_personaje, id_arma, estado)
    VALUES (?, ?, 1, 1, 'vivo')
");
$insert->execute([$id_partida, $id_usuario]);

// Redirigir a la vista de la sala
header("Location: sala.php?id_partida=$id_partida");
exit;
