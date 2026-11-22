<?php
// dashboard.php - Panel del usuario registrado (rol 2)
require_once 'includes/auth.php';
requerir_login();

// El administrador tiene su propio panel
if (es_admin()) {
    redirigir('admin/index.php');
}

$titulo = 'Mi panel';
require 'includes/header.php';
?>

  <main class="seccion-padding">
    <h2 class="titulo-seccion">Hola, <?= e($_SESSION['usuario_nombre']) ?></h2>
    <p class="subtitulo-seccion">Este es tu panel de finanzas personales.</p>

    <!-- Accesos a los módulos del usuario (se irán habilitando) -->
    <div class="contenedor-grid">
      <article class="tarjeta-funcion">
        <div class="icono-tarjeta">💸</div>
        <h3>Transacciones</h3>
        <p>Registra y consulta tus ingresos y gastos.</p>
        <span class="etiqueta-pronto">Próximamente</span>
      </article>

      <article class="tarjeta-funcion">
        <div class="icono-tarjeta">🏷️</div>
        <h3>Categorías</h3>
        <p>Crea categorías de gasto personalizadas.</p>
        <a href="categorias.php" class="btn-primario btn-chico">Administrar</a>
      </article>

      <article class="tarjeta-funcion">
        <div class="icono-tarjeta">🎯</div>
        <h3>Presupuestos y metas</h3>
        <p>Fija topes mensuales y metas de ahorro.</p>
        <span class="etiqueta-pronto">Próximamente</span>
      </article>
    </div>
  </main>

<?php require 'includes/footer.php'; ?>
