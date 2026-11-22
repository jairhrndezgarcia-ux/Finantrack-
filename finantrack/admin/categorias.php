<?php
// admin/categorias.php - Módulo 3: catálogo global de categorías (solo administrador)
$base = '../';
require_once '../includes/auth.php';
requerir_admin($base);

require_once '../config/conexion.php';
require_once '../includes/categorias_crud.php';

// Alta, modificación o baja de categorías globales (propietario = null)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    procesar_categorias($conexion, null, 'categorias.php');
}

$categorias = obtener_categorias($conexion, null);

$titulo            = 'Categorías globales';
$tituloPagina      = 'Categorías globales';
$descripcionPagina = 'Categorías predeterminadas que ven todos los usuarios.';
$accionForm        = 'categorias.php';

require '../includes/header.php';
require '../includes/categorias_vista.php';
require '../includes/footer.php';
