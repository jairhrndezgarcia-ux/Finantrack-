<?php
// categorias.php - Módulo 3: categorías privadas del usuario registrado
require_once 'includes/auth.php';
requerir_login();

// El administrador gestiona las categorías globales en su propio panel
if (es_admin()) {
    redirigir('admin/categorias.php');
}

require_once 'config/conexion.php';
require_once 'includes/categorias_crud.php';

$usuarioId = (int) $_SESSION['usuario_id'];

// Alta, modificación o baja (POST -> procesa -> redirige)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    procesar_categorias($conexion, $usuarioId, 'categorias.php');
}

// Lectura: globales + privadas del usuario
$categorias = obtener_categorias($conexion, $usuarioId);

$titulo            = 'Mis categorías';
$tituloPagina      = 'Mis categorías';
$descripcionPagina = 'Las predeterminadas son para todos; además puedes crear las tuyas.';
$accionForm        = 'categorias.php';

require 'includes/header.php';
require 'includes/categorias_vista.php';
require 'includes/footer.php';
