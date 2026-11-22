<?php
// logout.php - Cerrar sesión de forma segura
require_once 'includes/auth.php';

// 1. Vaciar los datos de la sesión
$_SESSION = [];

// 2. Borrar la cookie de sesión del navegador
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

// 3. Destruir la sesión en el servidor
session_destroy();

// 4. Nueva sesión vacía solo para mostrar el mensaje de despedida
session_start();
flash('Cerraste sesión correctamente.', 'exito');
redirigir('index.php');
