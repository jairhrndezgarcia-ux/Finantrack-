<?php
// includes/categorias_vista.php - Vista compartida del Módulo 3.
// Variables esperadas: $categorias, $tituloPagina, $descripcionPagina, $accionForm
?>
  <main class="seccion-padding">
    <div class="encabezado-modulo">
      <div>
        <h2><?= e($tituloPagina) ?></h2>
        <p><?= e($descripcionPagina) ?></p>
      </div>
      <button type="button" class="btn-primario" id="btn-nueva-categoria">+ Nueva categoría</button>
    </div>

    <!-- Filtros del lado del cliente (JavaScript) -->
    <div class="barra-filtros">
      <div class="filtros-tipo" role="group" aria-label="Filtrar por tipo">
        <button type="button" class="filtro activo" data-filtro="todas">Todas</button>
        <button type="button" class="filtro" data-filtro="ingreso">Ingresos</button>
        <button type="button" class="filtro" data-filtro="gasto">Gastos</button>
      </div>
      <input type="search" id="buscar-categoria" class="campo-buscar" placeholder="Buscar categoría...">
    </div>

    <!-- Lectura / consulta: cuadrícula responsiva -->
    <div class="grid-categorias" id="grid-categorias">
      <?php foreach ($categorias as $cat): ?>
        <article class="tarjeta-categoria"
                 data-tipo="<?= e($cat['tipo']) ?>"
                 data-nombre="<?= e(mb_strtolower($cat['nombre'])) ?>"
                 style="--color-cat: <?= e($cat['color']) ?>">
          <div class="cat-icono"><?= e($cat['icono']) ?></div>

          <div class="cat-info">
            <h3><?= e($cat['nombre']) ?></h3>
            <p>
              <span class="badge badge-<?= e($cat['tipo']) ?>"><?= $cat['tipo'] === 'ingreso' ? 'Ingreso' : 'Gasto' ?></span>
              <?php if (!$cat['editable']): ?>
                <span class="badge badge-global">Predeterminada</span>
              <?php endif; ?>
            </p>
            <p class="cat-usos">
              <?= $cat['usos'] === 0 ? 'Sin movimientos' : $cat['usos'] . ' movimiento(s)' ?>
            </p>
          </div>

          <?php if ($cat['editable']): ?>
            <div class="cat-acciones">
              <!-- Modificación: el modal se llena con estos datos -->
              <button type="button" class="btn-icono btn-editar" title="Editar"
                      data-id="<?= (int) $cat['id'] ?>"
                      data-nombre="<?= e($cat['nombre']) ?>"
                      data-tipo="<?= e($cat['tipo']) ?>"
                      data-color="<?= e($cat['color']) ?>"
                      data-icono="<?= e($cat['icono']) ?>"
                      data-usos="<?= $cat['usos'] ?>">✏️</button>

              <!-- Baja: se confirma con JavaScript antes de enviar -->
              <form method="POST" action="<?= e($accionForm) ?>" class="form-eliminar"
                    data-nombre="<?= e($cat['nombre']) ?>" data-usos="<?= $cat['usos'] ?>">
                <?= campo_csrf() ?>
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                <button type="submit" class="btn-icono btn-peligro" title="Eliminar">🗑️</button>
              </form>
            </div>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>

    <p class="mensaje-vacio <?= $categorias ? 'oculto' : '' ?>" id="sin-resultados">
      No hay categorías que coincidan.
    </p>
  </main>

  <!-- Modal de alta / modificación -->
  <dialog id="modal-categoria" class="modal">
    <form method="POST" action="<?= e($accionForm) ?>" id="form-categoria" class="form-modal">
      <?= campo_csrf() ?>
      <input type="hidden" name="accion" id="cat-accion" value="crear">
      <input type="hidden" name="id" id="cat-id" value="0">

      <h3 id="modal-titulo">Nueva categoría</h3>

      <!-- Vista previa en vivo -->
      <div class="tarjeta-categoria vista-previa" id="vista-previa" style="--color-cat: #2563eb">
        <div class="cat-icono" id="previa-icono">💰</div>
        <div class="cat-info">
          <h3 id="previa-nombre">Nombre de la categoría</h3>
          <p><span class="badge badge-gasto" id="previa-tipo">Gasto</span></p>
        </div>
      </div>

      <div class="campo-grupo">
        <label for="cat-nombre">Nombre:</label>
        <input type="text" id="cat-nombre" name="nombre" maxlength="50" required placeholder="Ej. Mascotas">
        <span class="texto-error" id="error-categoria"></span>
      </div>

      <div class="campo-fila">
        <div class="campo-grupo">
          <label for="cat-tipo">Tipo:</label>
          <select id="cat-tipo" name="tipo" required>
            <option value="gasto">Gasto</option>
            <option value="ingreso">Ingreso</option>
          </select>
          <small class="nota oculto" id="nota-tipo">No se puede cambiar: ya tiene movimientos.</small>
        </div>

        <div class="campo-grupo">
          <label for="cat-color">Color:</label>
          <input type="color" id="cat-color" name="color" value="#2563eb">
        </div>
      </div>

      <fieldset class="selector-iconos">
        <legend>Ícono:</legend>
        <?php foreach (ICONOS_CATEGORIA as $i => $icono): ?>
          <label class="opcion-icono">
            <input type="radio" name="icono" value="<?= e($icono) ?>" <?= $i === 0 ? 'checked' : '' ?>>
            <span><?= e($icono) ?></span>
          </label>
        <?php endforeach; ?>
      </fieldset>

      <div class="modal-acciones">
        <button type="button" class="btn-secundario" id="btn-cancelar-modal">Cancelar</button>
        <button type="submit" class="btn-primario">Guardar</button>
      </div>
    </form>
  </dialog>
