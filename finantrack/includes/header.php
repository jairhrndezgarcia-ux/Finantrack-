<?php
// includes/header.php - Encabezado y navegación compartidos.
// Antes de incluirlo, cada página define:
//   $titulo  -> título de la pestaña
//   $base    -> '' en la raíz, '../' en subcarpetas (ej. admin/)
$base   = $base ?? '';
$titulo = $titulo ?? 'Inicio';
$mensajeFlash = flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo) ?> - FinanTrack</title>
  <link rel="stylesheet" href="<?= $base ?>css/style.css">
</head>
<body>

  <header class="header-principal">
    <div class="logo">
      <a href="<?= $base ?>index.php"><h1>Finan<span>Track</span></h1></a>
    </div>
    <nav class="nav-principal">
      <?php if (usuario_autenticado()): ?>
        <a href="<?= $base ?>perfil.php" class="nav-usuario" title="Mi perfil">👤 <?= e($_SESSION['usuario_nombre']) ?></a>
        <?php if (es_admin()): ?>
          <a href="<?= $base ?>admin/index.php">Panel</a>
          <a href="<?= $base ?>admin/usuarios.php">Usuarios</a>
          <a href="<?= $base ?>admin/categorias.php">Categorías</a>
          <a href="<?= $base ?>admin/metodos.php">Métodos de pago</a>
          <a href="<?= $base ?>admin/divisas.php">Divisas</a>
        <?php else: ?>
          <a href="<?= $base ?>dashboard.php">Mi panel</a>
          <a href="<?= $base ?>transacciones.php">Transacciones</a>
          <a href="<?= $base ?>presupuestos.php">Presupuestos</a>
          <a href="<?= $base ?>metas.php">Metas</a>
          <a href="<?= $base ?>categorias.php">Categorías</a>
        <?php endif; ?>
        <a href="<?= $base ?>logout.php" class="btn-nav">Cerrar sesión</a>
      <?php else: ?>
        <a href="<?= $base ?>index.php#funciones">Funcionalidades</a>
        <a href="<?= $base ?>index.php#calculadora">Calculadora</a>
        <a href="<?= $base ?>login.php">Iniciar sesión</a>
        <a href="<?= $base ?>index.php#registro" class="btn-nav">Registrarse</a>
      <?php endif; ?>
    </nav>
  </header>

  <?php if ($mensajeFlash): ?>
    <div class="toast toast-<?= e($mensajeFlash['tipo']) ?>" role="alert">
      <?= e($mensajeFlash['mensaje']) ?>
    </div>
  <?php endif; ?>
