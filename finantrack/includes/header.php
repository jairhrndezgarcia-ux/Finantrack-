<?php
// includes/header.php - Estructura y navegación compartidas.
// Antes de incluirlo, cada página define:
//   $titulo  -> título de la pestaña
//   $base    -> '' en la raíz, '../' en subcarpetas (ej. admin/)
//
// Visitante: barra superior pública (con menú hamburguesa en móvil).
// Con sesión: estructura de aplicación con barra lateral (cajón en móvil).
$base         = $base ?? '';
$titulo       = $titulo ?? 'Inicio';
$mensajeFlash = flash();
$layoutApp    = usuario_autenticado();

// Página actual, para resaltar el enlace activo del menú
$paginaActual = basename($_SERVER['SCRIPT_NAME']);
$enAdmin      = str_contains($_SERVER['SCRIPT_NAME'], '/admin/');

/** Imprime un enlace del menú lateral y lo marca si es la página actual. */
function enlace_menu(string $href, string $icono, string $texto, bool $activo): void
{
    printf(
        '<a href="%s" class="menu-enlace%s"%s><span class="menu-icono" aria-hidden="true">%s</span>%s</a>',
        e($href),
        $activo ? ' activo' : '',
        $activo ? ' aria-current="page"' : '',
        $icono,
        e($texto)
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#0F172A">
  <title><?= e($titulo) ?> - FinanTrack</title>
  <!-- Tipografía: Plus Jakarta Sans (si no hay internet se usa la fuente del sistema) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $base ?>css/style.css">
</head>
<body class="<?= $layoutApp ? 'layout-app' : 'layout-publico' ?>">

<?php if ($layoutApp): ?>
  <!-- ===== Barra superior móvil (solo pantallas chicas) ===== -->
  <header class="barra-movil">
    <button type="button" class="btn-menu" id="btn-abrir-menu" aria-label="Abrir menú"
            aria-controls="menu-lateral" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <a href="<?= $base . ruta_inicio() ?>" class="logo">Finan<span>Track</span></a>
    <a href="<?= $base ?>perfil.php" class="avatar-mini" aria-label="Mi perfil">
      <?= e(mb_strtoupper(mb_substr($_SESSION['usuario_nombre'], 0, 1))) ?>
    </a>
  </header>

  <!-- ===== Barra lateral (cajón deslizable en móvil) ===== -->
  <aside class="menu-lateral" id="menu-lateral" aria-label="Menú principal">
    <a href="<?= $base . ruta_inicio() ?>" class="logo">Finan<span>Track</span></a>

    <nav class="menu-grupo">
      <?php if (es_admin()): ?>
        <?php
          enlace_menu($base . 'admin/index.php',      '🏠', 'Panel',           $enAdmin && $paginaActual === 'index.php');
          enlace_menu($base . 'admin/usuarios.php',   '👥', 'Usuarios',        $enAdmin && $paginaActual === 'usuarios.php');
          enlace_menu($base . 'admin/categorias.php', '🏷️', 'Categorías',      $enAdmin && $paginaActual === 'categorias.php');
          enlace_menu($base . 'admin/metodos.php',    '💳', 'Métodos de pago', $enAdmin && $paginaActual === 'metodos.php');
          enlace_menu($base . 'admin/divisas.php',    '💱', 'Divisas',         $enAdmin && $paginaActual === 'divisas.php');
        ?>
      <?php else: ?>
        <?php
          enlace_menu($base . 'dashboard.php',     '🏠', 'Mi panel',      $paginaActual === 'dashboard.php');
          enlace_menu($base . 'transacciones.php', '💸', 'Movimientos',   $paginaActual === 'transacciones.php');
          enlace_menu($base . 'presupuestos.php',  '🎯', 'Presupuestos',  $paginaActual === 'presupuestos.php');
          enlace_menu($base . 'metas.php',         '🐷', 'Metas de ahorro', $paginaActual === 'metas.php');
          enlace_menu($base . 'categorias.php',    '🏷️', 'Categorías',    $paginaActual === 'categorias.php');
        ?>
      <?php endif; ?>
    </nav>

    <div class="menu-pie">
      <a href="<?= $base ?>perfil.php" class="menu-usuario<?= $paginaActual === 'perfil.php' ? ' activo' : '' ?>">
        <span class="avatar-mini" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($_SESSION['usuario_nombre'], 0, 1))) ?></span>
        <span>
          <strong><?= e($_SESSION['usuario_nombre']) ?></strong>
          <small><?= es_admin() ? 'Administrador' : 'Ver mi perfil' ?></small>
        </span>
      </a>
      <a href="<?= $base ?>logout.php" class="menu-enlace menu-salir">
        <span class="menu-icono" aria-hidden="true">↩</span>Cerrar sesión
      </a>
    </div>
  </aside>
  <div class="fondo-menu" id="fondo-menu" hidden></div>

  <div class="app-contenido">
<?php else: ?>
  <!-- ===== Barra superior pública ===== -->
  <header class="header-principal">
    <a href="<?= $base ?>index.php" class="logo">Finan<span>Track</span></a>
    <button type="button" class="btn-menu" id="btn-abrir-menu" aria-label="Abrir menú"
            aria-controls="nav-principal" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <nav class="nav-principal" id="nav-principal" aria-label="Menú principal">
      <a href="<?= $base ?>index.php#funciones">Funciones</a>
      <a href="<?= $base ?>index.php#calculadora">Calculadora</a>
      <a href="<?= $base ?>index.php#consejos">Consejos</a>
      <a href="<?= $base ?>login.php" class="nav-login">Iniciar sesión</a>
      <a href="<?= $base ?>index.php#registro" class="btn-nav">Crear cuenta</a>
    </nav>
  </header>
<?php endif; ?>

  <?php if ($mensajeFlash): ?>
    <div class="toast toast-<?= e($mensajeFlash['tipo']) ?>" role="alert">
      <?= e($mensajeFlash['mensaje']) ?>
    </div>
  <?php endif; ?>
