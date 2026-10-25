<?php
/**
 * view/admin/receta_form.php
 * Formulario de creación y edición de recetas para el administrador.
 * Variables inyectadas por AdminRecetaController:
 *   $modoEditar  bool    — true = edición, false = creación
 *   $receta      array|null — datos actuales (solo en edición)
 *   $categorias  array   — [id_categoria, nombre, icono, color, bg]
 *   $paises      array   — [id_pais, nombre]
 *   $chefs       array   — [id_usuario, nombre]
 *   $pageTitle   string
 */
require_once __DIR__ . '/../../config/config.php';
requireRole(ROL_ADMIN);

// Si se accede directamente sin variables del controlador, delegar
if (!isset($modoEditar)) {
    require_once __DIR__ . '/../../controllers/AdminRecetaController.php';
    $ctrl = new AdminRecetaController();
    isset($_GET['id']) ? $ctrl->editar() : $ctrl->nueva();
    exit;
}

$r       = $receta ?? [];      // datos actuales (edición) o vacío (creación)
$rId     = (int)($r['id_receta'] ?? 0);
$ingList = $r['ingredientes'] ?? [];
$pasList = $r['pasos']        ?? [];
$baseUrl = BASE_URL;

$accion  = $modoEditar
    ? $baseUrl . '/index.php?route=admin/recetas/editar'
    : $baseUrl . '/index.php?route=admin/recetas/nueva';

$exito = $_SESSION['exito'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['exito'], $_SESSION['error']);

// Helpers de selección
$sel = fn($a, $b) => (string)$a === (string)$b ? 'selected' : '';
$chk = fn($v)     => $v ? 'checked' : '';
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>
<?php
$topbarBadge = 'Admin';
$topbarBreadcrumb = [
    ['label' => 'Inicio',   'href' => $baseUrl . '/admin'],
    ['label' => 'Recetas',  'href' => $baseUrl . '/index.php?route=admin/recetas'],
    ['label' => $modoEditar ? 'Editar' : 'Nueva receta'],
];
include __DIR__ . '/../../assets/topbar.php';
?>

<main class="pb-5">
<div class="container-fluid px-3 mt-3" style="max-width:960px">

<?php
$phTag     = 'Catálogo';
$phTagImg  = $baseUrl . '/img/Icon/Categorias/plato-principal.png';
$phTitle   = $modoEditar ? 'Editar receta' : 'Nueva receta';
$phSub     = $modoEditar
    ? 'Actualiza los datos y la preparación de esta receta'
    : 'Añade una nueva receta al catálogo de GastroDigital';
$phArtImg  = $baseUrl . '/img/Icon/ICON-Sobrantes/book_recipe.gif';
include __DIR__ . '/../../assets/page_header.php';
?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST"
      action="<?= $accion ?>"
      enctype="multipart/form-data"
      id="formReceta"
      novalidate>

    <?php csrfField(); ?>
    <?php if ($modoEditar): ?>
    <input type="hidden" name="receta_id" value="<?= $rId ?>">
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════
         BLOQUE 1 — Información principal
    ══════════════════════════════════════════════════════ -->
    <div class="card panel-card mb-4">
        <div class="card-header py-3 d-flex align-items-center gap-2"
             style="background:var(--surface-3);border-bottom:1px solid var(--border)">
            <i class="bi bi-journal-text" style="color:var(--primary);font-size:1.1rem"></i>
            <h5 class="mb-0 fw-800">Información de la receta</h5>
        </div>
        <div class="card-body">

            <!-- Título -->
            <div class="mb-3">
                <label class="form-label fw-700" for="titulo">
                    Título <span class="text-danger">*</span>
                </label>
                <input type="text" id="titulo" name="titulo"
                       class="form-control"
                       placeholder="Ej: Paella valenciana, Tiramisú clásico…"
                       maxlength="150" required
                       value="<?= htmlspecialchars($r['titulo'] ?? '') ?>">
                <div class="form-text">Máximo 150 caracteres.</div>
            </div>

            <!-- Descripción -->
            <div class="mb-3">
                <label class="form-label fw-700" for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion"
                          class="form-control" rows="3"
                          placeholder="Describe brevemente la receta…"
                          maxlength="5000"><?= htmlspecialchars($r['descripcion'] ?? '') ?></textarea>
            </div>

            <!-- Fila: Categoría + País -->
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-700" for="id_categoria">Categoría</label>
                    <select id="id_categoria" name="id_categoria" class="form-select">
                        <option value="">— Sin categoría —</option>
                        <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id'] ?>"
                            <?= $sel($r['id_categoria'] ?? '', $c['id']) ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-700" for="id_pais">País de origen</label>
                    <select id="id_pais" name="id_pais" class="form-select" data-live-search="true">
                        <option value="">— Sin país —</option>
                        <?php foreach ($paises as $p): ?>
                        <option value="<?= $p['id_pais'] ?>"
                            <?= $sel($r['id_pais'] ?? '', $p['id_pais']) ?>>
                            <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Fila: Chef + Dificultad -->
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-700" for="id_chef">Chef</label>
                    <select id="id_chef" name="id_chef" class="form-select">
                        <option value="">— Sin asignar —</option>
                        <?php foreach ($chefs as $c): ?>
                        <option value="<?= $c['id_usuario'] ?>"
                            <?= $sel($r['id_chef'] ?? '', $c['id_usuario']) ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-700" for="dificultad">
                        Dificultad <span class="text-danger">*</span>
                    </label>
                    <select id="dificultad" name="dificultad" class="form-select" required>
                        <?php foreach (['Fácil', 'Media', 'Difícil'] as $d): ?>
                        <option value="<?= $d ?>"
                            <?= $sel($r['dificultad'] ?? 'Media', $d) ?>>
                            <?= $d ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Fila: Tiempo + Porciones + Calorías -->
            <div class="row g-3 mb-3">
                <div class="col-sm-4">
                    <label class="form-label fw-700" for="tiempo_preparacion">
                        Tiempo (min) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-clock"></i></span>
                        <input type="number" id="tiempo_preparacion" name="tiempo_preparacion"
                               class="form-control" min="1" max="9999" required
                               placeholder="30"
                               value="<?= htmlspecialchars($r['tiempo_preparacion'] ?? '') ?>">
                        <span class="input-group-text text-muted">min</span>
                    </div>
                </div>
                <div class="col-sm-4">
                    <label class="form-label fw-700" for="porciones">
                        Porciones <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-people"></i></span>
                        <input type="number" id="porciones" name="porciones"
                               class="form-control" min="1" max="99" required
                               placeholder="4"
                               value="<?= htmlspecialchars($r['porciones'] ?? '4') ?>">
                    </div>
                </div>
                <div class="col-sm-4">
                    <label class="form-label fw-700" for="calorias">Calorías (kcal)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-fire"></i></span>
                        <input type="number" id="calorias" name="calorias"
                               class="form-control" min="0" max="9999"
                               placeholder="Sin datos"
                               value="<?= htmlspecialchars($r['calorias'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Video URL -->
            <div class="mb-3">
                <label class="form-label fw-700" for="video_url">URL de video (opcional)</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-play-circle"></i></span>
                    <input type="url" id="video_url" name="video_url"
                           class="form-control"
                           placeholder="https://youtube.com/watch?v=…"
                           value="<?= htmlspecialchars($r['video_url'] ?? '') ?>">
                </div>
            </div>

            <!-- Imagen -->
            <div class="mb-3">
                <label class="form-label fw-700" for="imagen">
                    Imagen<?= $modoEditar ? ' (dejar vacío para conservar la actual)' : '' ?>
                </label>
                <input type="file" id="imagen" name="imagen"
                       class="form-control" accept=".jpg,.jpeg,.png,.webp">
                <div class="form-text">
                    JPG, PNG o WebP · máx. 3 MB
                </div>
                <?php if ($modoEditar && !empty($r['imagen'])): ?>
                <div class="mt-2 d-flex align-items-center gap-3">
                    <img src="<?= $baseUrl ?>/uploads/<?= htmlspecialchars($r['imagen']) ?>"
                         alt="Imagen actual" class="rounded shadow-sm"
                         style="height:80px;width:auto;object-fit:cover"
                         onerror="this.style.display='none'">
                    <span class="text-muted small">
                        Imagen actual: <code><?= htmlspecialchars($r['imagen']) ?></code>
                    </span>
                </div>
                <?php endif; ?>
                <!-- Preview en tiempo real -->
                <div id="imgPreviewWrap" class="mt-2" style="display:none">
                    <img id="imgPreview" src="" alt="Vista previa"
                         class="rounded shadow-sm"
                         style="height:80px;width:auto;object-fit:cover">
                    <span class="text-muted small ms-2">Vista previa</span>
                </div>
            </div>

            <!-- Switches: Publicada + Destacada -->
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox"
                               role="switch" id="publicada" name="publicada"
                               value="1" <?= $chk($r['publicada'] ?? 0) ?>>
                        <label class="form-check-label fw-700" for="publicada">
                            <i class="bi bi-eye me-1" style="color:var(--primary)"></i>
                            Publicada (visible para usuarios)
                        </label>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox"
                               role="switch" id="destacada" name="destacada"
                               value="1" <?= $chk($r['destacada'] ?? 0) ?>>
                        <label class="form-check-label fw-700" for="destacada">
                            <i class="bi bi-star-fill me-1" style="color:#f4ab2e"></i>
                            Receta destacada
                        </label>
                    </div>
                </div>
            </div>

        </div><!-- /card-body -->
    </div><!-- /card info -->

    <!-- ══════════════════════════════════════════════════════
         BLOQUE 2 — Ingredientes dinámicos
    ══════════════════════════════════════════════════════ -->
    <div class="card panel-card mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between"
             style="background:var(--surface-3);border-bottom:1px solid var(--border)">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-list-check" style="color:var(--primary);font-size:1.1rem"></i>
                <h5 class="mb-0 fw-800">Ingredientes</h5>
                <span class="badge rounded-pill bg-secondary ms-1" id="countIngredientes">
                    <?= count($ingList) ?: 1 ?>
                </span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAgregarIng">
                <i class="bi bi-plus-circle me-1"></i>Agregar ingrediente
            </button>
        </div>
        <div class="card-body p-0">
            <!-- Encabezados -->
            <div class="row g-0 px-3 pt-2 pb-1 d-none d-md-flex"
                 style="border-bottom:1px solid var(--border)">
                <div class="col-md-3">
                    <small class="fw-700 text-muted text-uppercase" style="font-size:.72rem">
                        Cantidad
                    </small>
                </div>
                <div class="col-md-3">
                    <small class="fw-700 text-muted text-uppercase" style="font-size:.72rem">
                        Unidad
                    </small>
                </div>
                <div class="col-md-5">
                    <small class="fw-700 text-muted text-uppercase" style="font-size:.72rem">
                        Ingrediente <span class="text-danger">*</span>
                    </small>
                </div>
            </div>

            <div id="listaIngredientes" class="p-3 d-flex flex-column gap-2">
                <?php
                // Si no hay ingredientes, mostrar una fila vacía
                $ingRows = !empty($ingList) ? $ingList : [['cantidad'=>'','unidad'=>'','nombre'=>'']];
                foreach ($ingRows as $idx => $ing):
                ?>
                <div class="row g-2 align-items-center ingrediente-fila"
                     data-idx="<?= $idx ?>">
                    <div class="col-12 col-md-3">
                        <input type="text" name="ing_cantidad[]"
                               class="form-control form-control-sm"
                               placeholder="200, 1/2, al gusto…"
                               value="<?= htmlspecialchars($ing['cantidad'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <input type="text" name="ing_unidad[]"
                               class="form-control form-control-sm"
                               placeholder="g, ml, taza, pieza…"
                               value="<?= htmlspecialchars($ing['unidad'] ?? '') ?>">
                    </div>
                    <div class="col-10 col-md-5">
                        <input type="text" name="ing_nombre[]"
                               class="form-control form-control-sm ing-nombre"
                               placeholder="Nombre del ingrediente"
                               value="<?= htmlspecialchars($ing['nombre'] ?? '') ?>">
                    </div>
                    <div class="col-2 col-md-1 text-end">
                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-eliminar-ing"
                                title="Eliminar ingrediente"
                                aria-label="Eliminar ingrediente">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div><!-- /card-body -->
    </div><!-- /card ingredientes -->

    <!-- ══════════════════════════════════════════════════════
         BLOQUE 3 — Pasos dinámicos (con imagen y duración)
    ══════════════════════════════════════════════════════ -->
    <div class="card panel-card mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between"
             style="background:var(--surface-3);border-bottom:1px solid var(--border)">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-journal-text" style="color:var(--secondary);font-size:1.1rem"></i>
                <h5 class="mb-0 fw-800">Preparación</h5>
                <span class="badge rounded-pill bg-secondary ms-1" id="countPasos">
                    <?= count($pasList) ?: 1 ?>
                </span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAgregarPaso">
                <i class="bi bi-plus-circle me-1"></i>Agregar paso
            </button>
        </div>
        <div class="card-body p-3">
            <div id="listaPasos" class="d-flex flex-column gap-3">
                <?php
                $pasRows = !empty($pasList) ? $pasList : [['descripcion' => '', 'imagen' => '', 'duracion' => '']];
                foreach ($pasRows as $idx => $paso):
                    $num      = $idx + 1;
                    $pasDesc  = is_array($paso) ? ($paso['descripcion'] ?? '') : $paso;
                    $pasDur   = is_array($paso) ? ($paso['duracion']    ?? '') : '';
                    $pasImg   = is_array($paso) ? ($paso['imagen']      ?? '') : '';
                    $pasImgOk = $pasImg && file_exists(ROOT_PATH . '/uploads/pasos/' . $pasImg);
                ?>
                <div class="paso-fila" data-idx="<?= $idx ?>">
                    <div class="d-flex gap-3 align-items-start">
                        <!-- Número circular -->
                        <div class="paso-num d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:36px;height:36px;border-radius:50%;
                                    background:linear-gradient(135deg,var(--primary),var(--secondary));
                                    color:#fff;font-weight:800;font-size:.9rem;margin-top:2px">
                            <?= $num ?>
                        </div>

                        <!-- Cuerpo del paso -->
                        <div class="flex-grow-1 d-flex flex-column gap-2">
                            <!-- Descripción -->
                            <textarea name="paso_desc[]"
                                      class="form-control paso-desc"
                                      rows="2"
                                      placeholder="Describe este paso de preparación…"><?= htmlspecialchars($pasDesc) ?></textarea>

                            <!-- Fila: duración + imagen -->
                            <div class="row g-2 align-items-end">
                                <!-- Duración en minutos -->
                                <div class="col-sm-3">
                                    <label class="form-label form-label-sm mb-1 fw-600">
                                        <i class="bi bi-alarm me-1" style="color:var(--primary)"></i>
                                        Duración (min)
                                    </label>
                                    <input type="number"
                                           name="paso_duracion[]"
                                           class="form-control form-control-sm"
                                           min="1" max="9999"
                                           placeholder="Ej: 5"
                                           value="<?= htmlspecialchars((string)$pasDur) ?>">
                                </div>

                                <!-- Subir imagen del paso -->
                                <div class="col-sm-9">
                                    <label class="form-label form-label-sm mb-1 fw-600">
                                        <i class="bi bi-image me-1" style="color:var(--secondary)"></i>
                                        Imagen del paso
                                        <span class="text-muted fw-400">(opcional · JPG/PNG/WebP · máx. 3 MB)</span>
                                    </label>
                                    <input type="file"
                                           name="paso_imagen[]"
                                           class="form-control form-control-sm paso-img-input"
                                           accept=".jpg,.jpeg,.png,.webp"
                                           data-idx="<?= $idx ?>">
                                    <?php if ($pasImgOk): ?>
                                    <div class="mt-1 d-flex align-items-center gap-2">
                                        <img src="<?= $baseUrl ?>/uploads/pasos/<?= htmlspecialchars($pasImg) ?>"
                                             alt="Imagen actual del paso <?= $num ?>"
                                             class="rounded paso-img-actual"
                                             style="height:56px;width:auto;object-fit:cover"
                                             onerror="this.parentNode.style.display='none'">
                                        <span class="text-muted small">
                                            <code><?= htmlspecialchars($pasImg) ?></code>
                                        </span>
                                        <!-- Hidden para conservar imagen si no se sube una nueva -->
                                        <input type="hidden" name="paso_imagen_actual[]"
                                               value="<?= htmlspecialchars($pasImg) ?>">
                                    </div>
                                    <?php else: ?>
                                    <input type="hidden" name="paso_imagen_actual[]" value="">
                                    <?php endif; ?>
                                    <!-- Preview en tiempo real -->
                                    <div class="paso-img-preview-wrap mt-1" style="display:none">
                                        <img src="" alt="Vista previa paso <?= $num ?>"
                                             class="rounded paso-img-preview"
                                             style="height:56px;width:auto;object-fit:cover">
                                        <span class="text-muted small ms-2">Vista previa</span>
                                    </div>
                                </div>
                            </div><!-- /row -->
                        </div><!-- /cuerpo -->

                        <!-- Botón eliminar paso -->
                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-eliminar-paso flex-shrink-0"
                                title="Eliminar paso"
                                style="margin-top:2px"
                                aria-label="Eliminar paso <?= $num ?>">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div><!-- /card pasos -->

    <!-- ══════════════════════════════════════════════════════
         Botones de acción
    ══════════════════════════════════════════════════════ -->
    <div class="d-flex flex-wrap gap-3 justify-content-end mb-5">
        <a href="<?= $baseUrl ?>/index.php?route=admin/recetas"
           class="btn btn-outline-secondary px-4">
            <i class="bi bi-arrow-left me-2"></i>Cancelar
        </a>
        <button type="submit" class="btn btn-primary px-5" id="btnGuardar">
            <i class="bi bi-floppy me-2"></i>
            <?= $modoEditar ? 'Guardar cambios' : 'Crear receta' ?>
        </button>
    </div>

</form>
</div><!-- /container -->
</main>

<script>
(function () {
    'use strict';

    // ── Contadores de filas ─────────────────────────────────
    function actualizarContadores() {
        document.getElementById('countIngredientes').textContent =
            document.querySelectorAll('.ingrediente-fila').length;
        document.getElementById('countPasos').textContent =
            document.querySelectorAll('.paso-fila').length;
    }

    // ── Renumerar pasos visualmente ─────────────────────────
    function renumerarPasos() {
        document.querySelectorAll('.paso-fila').forEach((fila, i) => {
            const num = fila.querySelector('.paso-num');
            if (num) num.textContent = i + 1;
            fila.dataset.idx = i;
            // Actualizar data-idx en el input de imagen del paso
            const imgInput = fila.querySelector('.paso-img-input');
            if (imgInput) imgInput.dataset.idx = i;
        });
    }

    // ── INGREDIENTES ────────────────────────────────────────
    function crearFilaIngrediente(cantidad = '', unidad = '', nombre = '') {
        const div = document.createElement('div');
        div.className = 'row g-2 align-items-center ingrediente-fila';
        div.innerHTML = `
            <div class="col-12 col-md-3">
                <input type="text" name="ing_cantidad[]" class="form-control form-control-sm"
                       placeholder="200, 1/2, al gusto…" value="${esc(cantidad)}">
            </div>
            <div class="col-12 col-md-3">
                <input type="text" name="ing_unidad[]" class="form-control form-control-sm"
                       placeholder="g, ml, taza, pieza…" value="${esc(unidad)}">
            </div>
            <div class="col-10 col-md-5">
                <input type="text" name="ing_nombre[]" class="form-control form-control-sm ing-nombre"
                       placeholder="Nombre del ingrediente" value="${esc(nombre)}">
            </div>
            <div class="col-2 col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-ing"
                        title="Eliminar" aria-label="Eliminar ingrediente">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>`;
        return div;
    }

    document.getElementById('btnAgregarIng').addEventListener('click', function () {
        const lista = document.getElementById('listaIngredientes');
        const fila  = crearFilaIngrediente();
        lista.appendChild(fila);
        fila.querySelector('.ing-nombre').focus();
        actualizarContadores();
    });

    document.getElementById('listaIngredientes').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-eliminar-ing');
        if (!btn) return;
        const filas = this.querySelectorAll('.ingrediente-fila');
        if (filas.length <= 1) {
            // Limpiar la última fila en lugar de eliminarla
            btn.closest('.ingrediente-fila').querySelectorAll('input').forEach(i => i.value = '');
            return;
        }
        btn.closest('.ingrediente-fila').remove();
        actualizarContadores();
    });

    // ── PASOS ───────────────────────────────────────────────
    function crearFilaPaso(desc = '') {
        const idx = document.querySelectorAll('.paso-fila').length;
        const num = idx + 1;
        const div = document.createElement('div');
        div.className = 'paso-fila';
        div.dataset.idx = idx;
        div.innerHTML = `
            <div class="d-flex gap-3 align-items-start">
                <div class="paso-num d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:36px;height:36px;border-radius:50%;
                            background:linear-gradient(135deg,var(--primary),var(--secondary));
                            color:#fff;font-weight:800;font-size:.9rem;margin-top:2px">
                    ${num}
                </div>
                <div class="flex-grow-1 d-flex flex-column gap-2">
                    <textarea name="paso_desc[]" class="form-control paso-desc" rows="2"
                              placeholder="Describe este paso de preparación…">${esc(desc)}</textarea>
                    <div class="row g-2 align-items-end">
                        <div class="col-sm-3">
                            <label class="form-label form-label-sm mb-1 fw-600">
                                <i class="bi bi-alarm me-1" style="color:var(--primary)"></i>
                                Duración (min)
                            </label>
                            <input type="number" name="paso_duracion[]"
                                   class="form-control form-control-sm"
                                   min="1" max="9999" placeholder="Ej: 5">
                        </div>
                        <div class="col-sm-9">
                            <label class="form-label form-label-sm mb-1 fw-600">
                                <i class="bi bi-image me-1" style="color:var(--secondary)"></i>
                                Imagen del paso
                                <span class="text-muted fw-400">(opcional · JPG/PNG/WebP · máx. 3 MB)</span>
                            </label>
                            <input type="file" name="paso_imagen[]"
                                   class="form-control form-control-sm paso-img-input"
                                   accept=".jpg,.jpeg,.png,.webp" data-idx="${idx}">
                            <input type="hidden" name="paso_imagen_actual[]" value="">
                            <div class="paso-img-preview-wrap mt-1" style="display:none">
                                <img src="" alt="Vista previa"
                                     class="rounded paso-img-preview"
                                     style="height:56px;width:auto;object-fit:cover">
                                <span class="text-muted small ms-2">Vista previa</span>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-paso flex-shrink-0"
                        title="Eliminar paso" style="margin-top:2px"
                        aria-label="Eliminar paso ${num}">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>`;
        return div;
    }

    document.getElementById('btnAgregarPaso').addEventListener('click', function () {
        const lista = document.getElementById('listaPasos');
        const fila  = crearFilaPaso();
        lista.appendChild(fila);
        fila.querySelector('.paso-desc').focus();
        renumerarPasos();
        actualizarContadores();
    });

    document.getElementById('listaPasos').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-eliminar-paso');
        if (!btn) return;
        const filas = this.querySelectorAll('.paso-fila');
        if (filas.length <= 1) {
            btn.closest('.paso-fila').querySelector('textarea').value = '';
            return;
        }
        btn.closest('.paso-fila').remove();
        renumerarPasos();
        actualizarContadores();
    });

    // ── Preview de imagen de PASO (delegación en listaPasos) ──
    document.getElementById('listaPasos').addEventListener('change', function (e) {
        const input = e.target.closest('.paso-img-input');
        if (!input) return;
        const fila  = input.closest('.paso-fila');
        const wrap  = fila.querySelector('.paso-img-preview-wrap');
        const prev  = fila.querySelector('.paso-img-preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = ev => {
                prev.src = ev.target.result;
                wrap.style.display = '';
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            wrap.style.display = 'none';
            prev.src = '';
        }
    });

    // ── Previsualización de imagen de RECETA ────────────────
    document.getElementById('imagen').addEventListener('change', function () {
        const wrap = document.getElementById('imgPreviewWrap');
        const prev = document.getElementById('imgPreview');
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                prev.src = e.target.result;
                wrap.style.display = '';
            };
            reader.readAsDataURL(this.files[0]);
        } else {
            wrap.style.display = 'none';
        }
    });

    // ── Validación frontend antes de enviar ─────────────────
    document.getElementById('formReceta').addEventListener('submit', function (e) {
        let ok = true;
        const errores = [];

        const titulo = document.getElementById('titulo').value.trim();
        if (!titulo) { errores.push('El título es obligatorio.'); ok = false; }

        const tiempo = parseInt(document.getElementById('tiempo_preparacion').value, 10);
        if (!tiempo || tiempo <= 0) { errores.push('El tiempo debe ser mayor a 0.'); ok = false; }

        const porciones = parseInt(document.getElementById('porciones').value, 10);
        if (!porciones || porciones <= 0) { errores.push('Las porciones deben ser mayor a 0.'); ok = false; }

        // Al menos un ingrediente con nombre
        const nombres = [...document.querySelectorAll('input[name="ing_nombre[]"]')]
            .map(i => i.value.trim()).filter(Boolean);
        if (!nombres.length) { errores.push('Agrega al menos un ingrediente.'); ok = false; }

        // Al menos un paso con descripción
        const pasos = [...document.querySelectorAll('textarea[name="paso_desc[]"]')]
            .map(t => t.value.trim()).filter(Boolean);
        if (!pasos.length) { errores.push('Agrega al menos un paso de preparación.'); ok = false; }

        if (!ok) {
            e.preventDefault();
            // Mostrar errores en un alert temporal
            const div = document.createElement('div');
            div.className = 'alert alert-danger alert-dismissible fade show';
            div.innerHTML = '<strong>Corrige los siguientes errores:</strong><ul class="mb-0 mt-1">'
                + errores.map(err => `<li>${err}</li>`).join('')
                + '</ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            document.getElementById('formReceta').prepend(div);
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        // Indicar carga
        const btn = document.getElementById('btnGuardar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando…';
    });

    // ── Helper: escapar HTML para atributos ─────────────────
    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // Inicializar contadores
    actualizarContadores();
    renumerarPasos();
})();
</script>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
