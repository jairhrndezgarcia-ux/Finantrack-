<?php
// config/conexion.php

$host = "localhost";
$user = "root";
$pass = "";
$bd   = "finantrack";

// Hacer que mysqli lance excepciones ante cualquier error de SQL
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexion = mysqli_connect($host, $user, $pass, $bd);
    // utf8mb4 para guardar correctamente acentos, ñ y emojis (íconos de categorías)
    mysqli_set_charset($conexion, 'utf8mb4');
} catch (mysqli_sql_exception $e) {
    // No mostrar el detalle técnico al usuario; queda en el log de PHP
    error_log('Error de conexión a la BD: ' . $e->getMessage());
    die('Error al conectar con la base de datos. Intenta más tarde.');
}
