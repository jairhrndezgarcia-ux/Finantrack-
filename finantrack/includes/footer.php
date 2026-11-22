<?php if (!empty($layoutApp)): ?>
    <footer class="pie-app">
      <p>FinanTrack, proyecto de Fundamentos de Desarrollo Web (UASLP, 2026)</p>
    </footer>
  </div><!-- /.app-contenido -->
<?php else: ?>
  <footer class="footer-principal">
    <div class="footer-contenido">
      <div>
        <a href="<?= $base ?? '' ?>index.php" class="logo">Finan<span>Track</span></a>
        <p>Gestor web de finanzas personales: registra tus movimientos, fija presupuestos y alcanza tus metas de ahorro.</p>
      </div>
      <nav aria-label="Enlaces del pie de página">
        <a href="<?= $base ?? '' ?>index.php#funciones">Funciones</a>
        <a href="<?= $base ?? '' ?>index.php#consejos">Consejos financieros</a>
        <a href="<?= $base ?? '' ?>login.php">Iniciar sesión</a>
        <a href="<?= $base ?? '' ?>index.php#registro">Crear cuenta</a>
      </nav>
    </div>
    <p class="footer-legal">Proyecto de Fundamentos de Desarrollo Web, Facultad de Ingeniería de la UASLP (2026)</p>
  </footer>
<?php endif; ?>

  <script src="<?= $base ?? '' ?>js/main.js"></script>
</body>
</html>
