<?php
// config/conexion.php

$host = "localhost";
$user = "root";
$pass = "";
$bd   = "finantrack";

$conexion = mysqli_connect($host, $user, $pass, $bd);

if (!$conexion) {
    die("Error al conectar a la base de datos: " . mysqli_connect_error());
}
?>