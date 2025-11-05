<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Formulario Inicio Sesion | CAEC</title>
    <link rel="stylesheet" href="controller/css/style.css">
</head>

<body class="instructor">

    <div class="login-box">
        <img src="controller/image/logo.png" class="avatar" alt="Avatar Image">
        <h1>ERROR INICIO SESION</h1>
        <form method="post" name="form1" id="form1" action="controller/inicio.php" autocomplete="off">
            <label for="username">Usuario</label>
            <input type="text" name="usuario" id="usuario" placeholder="Ingrese Usuario">
            <label for="password">Contraseña</label>
            <input type="password" name="contrasena" id="password" placeholder="Ingrese Contraseña">
            <input type="submit" name="inicio" id="inicio" value="Ingresar">
            <a href="#">Recordar Contraseña?</a><br>
            <a href="#">Registrarme?</a>
        </form>
    </div>
</body>

</html>