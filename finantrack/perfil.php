<?php
// perfil.php - Módulo 2: perfil de usuario (datos de la cuenta y cambio de contraseña)
require_once 'includes/auth.php';
requerir_login();

require_once 'config/conexion.php';
require_once 'includes/perfil_crud.php';

$usuarioId = (int) $_SESSION['usuario_id'];
$perfil    = obtener_perfil($conexion, $usuarioId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    procesar_perfil($conexion, $usuarioId, $perfil, 'perfil.php');
}

$divisas      = divisas_para_perfil($conexion, (int) $perfil['divisa_id']);
$estadisticas = estadisticas_perfil($conexion, $usuarioId);

// Iniciales para el avatar (ej. "Jair Hernández" -> "JH")
$iniciales = mb_strtoupper(mb_substr($perfil['nombre'], 0, 1) . mb_substr($perfil['apellidos'], 0, 1));

$titulo = 'Mi perfil';
require 'includes/header.php';
?>

  <main class="seccion-padding">
    <div class="encabezado-modulo">
      <div>
        <h2>Mi perfil</h2>
        <p>Consulta y actualiza la información de tu cuenta.</p>
      </div>
    </div>

    <div class="grid-perfil">
      <!-- Resumen de la cuenta -->
      <aside class="panel-tarjeta tarjeta-perfil">
        <div class="avatar" aria-hidden="true"><?= e($iniciales) ?></div>
        <h3><?= e($perfil['nombre'] . ' ' . $perfil['apellidos']) ?></h3>
        <p class="texto-tenue"><?= e($perfil['correo']) ?></p>
        <span class="badge <?= (int) $perfil['rol_id'] === ROL_ADMIN ? 'badge-global' : 'badge-ingreso' ?>">
          <?= e($perfil['rol']) ?>
        </span>

        <ul class="datos-perfil">
          <li><span>Divisa base</span><strong><?= e($perfil['divisa']) ?></strong></li>
          <li><span>Miembro desde</span><strong><?= e(date('d/m/Y', strtotime($perfil['creado_en']))) ?></strong></li>
          <li>
            <span>Último acceso</span>
            <strong><?= $perfil['ultimo_acceso'] ? e(date('d/m/Y H:i', strtotime($perfil['ultimo_acceso']))) : '—' ?></strong>
          </li>
          <?php if ((int) $perfil['rol_id'] !== ROL_ADMIN): ?>
            <li><span>Movimientos</span><strong><?= (int) $estadisticas['movimientos'] ?></strong></li>
            <li><span>Categorías propias</span><strong><?= (int) $estadisticas['categorias'] ?></strong></li>
            <li><span>Metas de ahorro</span><strong><?= (int) $estadisticas['metas'] ?></strong></li>
          <?php endif; ?>
        </ul>
      </aside>

      <div class="columna-formularios">
        <!-- Actualizar datos personales -->
        <section class="panel-tarjeta">
          <h3>Datos personales</h3>
          <form method="POST" action="perfil.php" id="form-datos" novalidate>
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="datos">

            <div class="campo-fila">
              <div class="campo-grupo">
                <label for="perfil-nombre">Nombre(s):</label>
                <input type="text" id="perfil-nombre" name="nombre" maxlength="60" required
                       value="<?= e($perfil['nombre']) ?>">
              </div>
              <div class="campo-grupo">
                <label for="perfil-apellidos">Apellidos:</label>
                <input type="text" id="perfil-apellidos" name="apellidos" maxlength="80" required
                       value="<?= e($perfil['apellidos']) ?>">
              </div>
            </div>

            <div class="campo-fila">
              <div class="campo-grupo">
                <label for="perfil-correo">Correo electrónico:</label>
                <input type="email" id="perfil-correo" name="correo" maxlength="120" required
                       value="<?= e($perfil['correo']) ?>" data-original="<?= e($perfil['correo']) ?>">
              </div>
              <div class="campo-grupo">
                <label for="perfil-divisa">Divisa base:</label>
                <select id="perfil-divisa" name="divisa_id" data-original="<?= (int) $perfil['divisa_id'] ?>">
                  <?php foreach ($divisas as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= (int) $d['id'] === (int) $perfil['divisa_id'] ? 'selected' : '' ?>>
                      <?= e($d['nombre']) ?> (<?= e($d['codigo']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="nota oculto" id="nota-divisa">
                  Solo cambia el símbolo con que se muestran tus montos; no convierte cantidades.
                </small>
              </div>
            </div>

            <!-- Aparece solo si se cambia el correo (JavaScript) -->
            <div class="campo-grupo confirmar-correo oculto" id="grupo-confirmar-correo">
              <label for="perfil-password-confirmacion">Para cambiar tu correo, escribe tu contraseña actual:</label>
              <input type="password" id="perfil-password-confirmacion" name="password_confirmacion"
                     autocomplete="current-password">
            </div>

            <span class="texto-error" id="error-datos"></span>

            <div class="acciones-formulario">
              <button type="reset" class="btn-secundario" id="btn-descartar-datos">Descartar cambios</button>
              <button type="submit" class="btn-primario" id="btn-guardar-datos" disabled>Guardar cambios</button>
            </div>
          </form>
        </section>

        <!-- Cambio seguro de contraseña -->
        <section class="panel-tarjeta">
          <h3>Cambiar contraseña</h3>
          <form method="POST" action="perfil.php" id="form-password" novalidate>
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="password">
            <!-- Campo oculto de usuario: ayuda a los gestores de contraseñas del navegador -->
            <input type="email" name="usuario" value="<?= e($perfil['correo']) ?>" autocomplete="username" hidden>

            <div class="campo-grupo">
              <label for="password-actual">Contraseña actual:</label>
              <div class="campo-password">
                <input type="password" id="password-actual" name="password_actual" autocomplete="current-password" required>
                <button type="button" class="btn-ver-password" aria-label="Mostrar contraseña" data-ver="password-actual">👁️</button>
              </div>
            </div>

            <div class="campo-fila">
              <div class="campo-grupo">
                <label for="password-nueva">Nueva contraseña:</label>
                <div class="campo-password">
                  <input type="password" id="password-nueva" name="password_nueva" autocomplete="new-password" required>
                  <button type="button" class="btn-ver-password" aria-label="Mostrar contraseña" data-ver="password-nueva">👁️</button>
                </div>
                <!-- Medidor de seguridad (JavaScript) -->
                <div class="medidor-password" id="medidor-password">
                  <div class="medidor-barra"><div class="medidor-relleno"></div></div>
                  <small class="medidor-texto">Mínimo 6 caracteres</small>
                </div>
              </div>
              <div class="campo-grupo">
                <label for="password-confirmar">Confirmar nueva contraseña:</label>
                <div class="campo-password">
                  <input type="password" id="password-confirmar" name="password_confirmar" autocomplete="new-password" required>
                  <button type="button" class="btn-ver-password" aria-label="Mostrar contraseña" data-ver="password-confirmar">👁️</button>
                </div>
                <small class="texto-coincidencia" id="coincidencia-password"></small>
              </div>
            </div>

            <span class="texto-error" id="error-password-perfil"></span>

            <div class="acciones-formulario">
              <button type="submit" class="btn-primario">Cambiar contraseña</button>
            </div>
          </form>
        </section>
      </div>
    </div>
  </main>

<?php require 'includes/footer.php'; ?>
