<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/receta.php';
require_once __DIR__ . '/../../models/categoria.php';
requireRole(ROL_CHEF);

$recetaId = (int)($_GET['id'] ?? 0);
if (!$recetaId) {
    redirect('mis-recetas');
}

$model    = new Receta();
$receta   = $model->obtenerParaEditar($recetaId, $_SESSION['usuario_id']);

if (!$receta) {
    $_SESSION['error'] = 'No tienes permiso para editar esa receta.';
    redirect('mis-recetas');
}

$categorias = (new Categoria())->listarSimple();
$pageTitle  = 'Editar Receta';

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-5">
<div class="container" style="max-width:820px">

    <?php
    $phTag     = 'Mi cocina';
    $phTagImg  = BASE_URL . '/img/Icon/ICON-Sobrantes/chefbot.png';
    $phTitle   = 'Editar Receta';
    $phSub     = 'Actualiza los datos de «' . htmlspecialchars($receta['titulo']) . '»';
    $phArtImg  = BASE_URL . '/img/Icon/ICON-Sobrantes/book_recipe.gif';
    $phBack    = ['label' => 'Volver a mis recetas', 'href' => BASE_URL . '/mis-recetas'];
    include __DIR__ . '/../../assets/page_header.php';
    ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST"
          action="<?= BASE_URL ?>/index.php?route=editar-receta"
          enctype="multipart/form-data">
        <?php csrfField(); ?>
        <input type="hidden" name="receta_id" value="<?= $receta['id'] ?>">

        <!-- Info básica -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="fw-700 mb-3">
                    <i class="bi bi-info-circle me-2" style="color:var(--primary)"></i>Información básica
                </h5>
                <div class="mb-3">
                    <label class="form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="titulo" required
                           value="<?= htmlspecialchars($receta['titulo']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control" name="descripcion" rows="3"><?= htmlspecialchars($receta['descripcion'] ?? '') ?></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Categoría <span class="text-danger">*</span></label>
                        <select class="form-select" name="categoria" required>
                            <option value="">Seleccionar categoría...</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['nombre']) ?>"
                                <?= $receta['categoria'] === $cat['nombre'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Dificultad <span class="text-danger">*</span></label>
                        <select class="form-select" name="dificultad" required>
                            <?php foreach (['Fácil','Media','Difícil'] as $d): ?>
                            <option value="<?= $d ?>" <?= $receta['dificultad'] === $d ? 'selected' : '' ?>>
                                <?= $d ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tiempo (min) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="tiempo"
                               min="1" max="999" required
                               value="<?= (int)$receta['tiempo_preparacion'] ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Porciones</label>
                        <input type="number" class="form-control" name="porciones"
                               min="1" max="50"
                               value="<?= (int)($receta['porciones'] ?? 4) ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Kcal</label>
                        <input type="number" class="form-control" name="calorias"
                               min="0" placeholder="—"
                               value="<?= htmlspecialchars($receta['calorias'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Imagen -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="fw-700 mb-3">
                    <i class="bi bi-image me-2" style="color:var(--primary)"></i>Imagen
                    <small class="text-muted fw-400">(opcional · deja vacío para mantener la actual)</small>
                </h5>
                <?php if ($receta['imagen'] && file_exists(ROOT_PATH . '/uploads/' . $receta['imagen'])): ?>
                <div class="mb-3">
                    <p class="text-muted small mb-1">Imagen actual:</p>
                    <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($receta['imagen']) ?>"
                         alt="Imagen actual" class="rounded"
                         style="height:120px;object-fit:cover;max-width:100%">
                </div>
                <?php endif; ?>
                <input type="file" class="form-control" name="imagen" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>

        <!-- Ingredientes -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="fw-700 mb-3">
                    <i class="bi bi-basket me-2" style="color:var(--primary)"></i>Ingredientes
                </h5>
                <div id="ingredientesList">
                    <?php if (!empty($receta['ingredientes'])): ?>
                        <?php foreach ($receta['ingredientes'] as $ing): ?>
                        <div class="row g-2 mb-2 ingrediente-row">
                            <div class="col-5">
                                <input type="text" class="form-control form-control-sm" name="ing_nombre[]"
                                       placeholder="Ingrediente"
                                       value="<?= htmlspecialchars($ing['nombre']) ?>">
                            </div>
                            <div class="col-3">
                                <input type="text" class="form-control form-control-sm" name="ing_cantidad[]"
                                       placeholder="Cantidad"
                                       value="<?= htmlspecialchars($ing['cantidad'] ?? '') ?>">
                            </div>
                            <div class="col-3">
                                <input type="text" class="form-control form-control-sm" name="ing_unidad[]"
                                       placeholder="Unidad (g, ml…)"
                                       value="<?= htmlspecialchars($ing['unidad'] ?? '') ?>">
                            </div>
                            <div class="col-1">
                                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-ing">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="row g-2 mb-2 ingrediente-row">
                            <div class="col-5"><input type="text" class="form-control form-control-sm" name="ing_nombre[]"   placeholder="Ingrediente"></div>
                            <div class="col-3"><input type="text" class="form-control form-control-sm" name="ing_cantidad[]" placeholder="Cantidad"></div>
                            <div class="col-3"><input type="text" class="form-control form-control-sm" name="ing_unidad[]"   placeholder="Unidad (g, ml…)"></div>
                            <div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm btn-remove-ing"><i class="bi bi-x"></i></button></div>
                        </div>
                        <?php endfor; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="btnAddIng">
                    <i class="bi bi-plus-circle me-1"></i>Agregar ingrediente
                </button>
            </div>
        </div>

        <!-- Pasos -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="fw-700 mb-3">
                    <i class="bi bi-list-ol me-2" style="color:var(--primary)"></i>Pasos de preparación
                </h5>
                <div id="pasosList">
                    <?php if (!empty($receta['pasos'])): ?>
                        <?php foreach ($receta['pasos'] as $idx => $paso): ?>
                        <?php
                            $pasImg   = $paso['imagen']   ?? '';
                            $pasDur   = $paso['duracion'] ?? '';
                            $pasImgOk = $pasImg && file_exists(ROOT_PATH . '/uploads/pasos/' . $pasImg);
                        ?>
                        <div class="paso-row mb-3 p-3 rounded-3"
                             style="background:var(--surface-2);border:1px solid var(--border)">
                            <div class="d-flex gap-2 align-items-start mb-2">
                                <span class="badge flex-shrink-0 mt-1"
                                      style="background:var(--primary);min-width:28px;text-align:center">
                                    <?= $idx + 1 ?>
                                </span>
                                <textarea class="form-control form-control-sm flex-grow-1 paso-desc-txt"
                                          name="pasos[]" rows="2"><?= htmlspecialchars($paso['descripcion']) ?></textarea>
                                <button type="button" class="btn btn-outline-danger btn-sm align-self-start btn-remove-paso">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                            <div class="row g-2 mt-1">
                                <div class="col-sm-3">
                                    <label class="form-label form-label-sm mb-1 fw-600 text-muted">
                                        <i class="bi bi-alarm me-1"></i>Duración (min)
                                    </label>
                                    <input type="number" class="form-control form-control-sm"
                                           name="paso_duracion[]" min="1" max="9999"
                                           placeholder="Ej: 5"
                                           value="<?= htmlspecialchars((string)$pasDur) ?>">
                                </div>
                            <div class="col-sm-9">
                                    <label class="form-label form-label-sm mb-1 fw-600 text-muted">
                                        <i class="bi bi-image me-1"></i>Imagen del paso
                                        <span class="fw-400">(opcional · JPG/PNG/WebP)</span>
                                    </label>
                                    <div class="d-flex gap-2 align-items-center flex-wrap">
                                        <input type="file" class="form-control form-control-sm chef-paso-img flex-grow-1"
                                               name="paso_imagen[]" accept="image/jpeg,image/png,image/webp"
                                               style="min-width:0">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary btn-generar-paso flex-shrink-0"
                                                title="Generar ilustración con IA" style="white-space:nowrap">
                                            <i class="bi bi-stars me-1"></i>Generar
                                        </button>
                                    </div>
                                    <?php if ($pasImgOk): ?>
                                    <div class="mt-1 d-flex align-items-center gap-2">
                                        <img src="<?= BASE_URL ?>/uploads/pasos/<?= htmlspecialchars($pasImg) ?>"
                                             alt="Imagen actual" class="rounded"
                                             style="height:50px;width:auto;object-fit:cover"
                                             onerror="this.parentNode.style.display='none'">
                                        <span class="text-muted small"><code><?= htmlspecialchars($pasImg) ?></code></span>
                                        <input type="hidden" name="paso_imagen_actual[]"
                                               value="<?= htmlspecialchars($pasImg) ?>">
                                    </div>
                                    <?php else: ?>
                                    <input type="hidden" name="paso_imagen_actual[]" value="">
                                    <?php endif; ?>
                                    <div class="paso-img-preview-wrap mt-1" style="display:none">
                                        <img src="" alt="Vista previa" class="rounded paso-img-preview"
                                             style="height:50px;width:auto;object-fit:cover">
                                        <span class="text-muted small ms-2 paso-preview-label">Vista previa</span>
                                    </div>
                                    <div class="paso-gen-status mt-1 small text-muted" style="display:none"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php for ($i = 1; $i <= 2; $i++): ?>
                        <div class="paso-row mb-3 p-3 rounded-3"
                             style="background:var(--surface-2);border:1px solid var(--border)">
                            <div class="d-flex gap-2 align-items-start mb-2">
                                <span class="badge flex-shrink-0 mt-1"
                                      style="background:var(--primary);min-width:28px;text-align:center"><?= $i ?></span>
                                <textarea class="form-control form-control-sm flex-grow-1"
                                          name="pasos[]" rows="2"
                                          placeholder="Describe el paso <?= $i ?>..."></textarea>
                                <button type="button" class="btn btn-outline-danger btn-sm align-self-start btn-remove-paso">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                            <div class="row g-2 mt-1">
                                <div class="col-sm-3">
                                    <label class="form-label form-label-sm mb-1 fw-600 text-muted">
                                        <i class="bi bi-alarm me-1"></i>Duración (min)
                                    </label>
                                    <input type="number" class="form-control form-control-sm"
                                           name="paso_duracion[]" min="1" max="9999" placeholder="Ej: 5">
                                </div>
                                <div class="col-sm-9">
                                    <label class="form-label form-label-sm mb-1 fw-600 text-muted">
                                        <i class="bi bi-image me-1"></i>Imagen del paso
                                        <span class="fw-400">(opcional)</span>
                                    </label>
                                    <input type="file" class="form-control form-control-sm chef-paso-img"
                                           name="paso_imagen[]" accept="image/jpeg,image/png,image/webp">
                                    <input type="hidden" name="paso_imagen_actual[]" value="">
                                    <div class="paso-img-preview-wrap mt-1" style="display:none">
                                        <img src="" alt="Vista previa" class="rounded paso-img-preview"
                                             style="height:50px;width:auto;object-fit:cover">
                                        <span class="text-muted small ms-2">Vista previa</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="btnAddPaso">
                    <i class="bi bi-plus-circle me-1"></i>Agregar paso
                </button>
            </div>
        </div>

        <!-- Publicación -->
        <div class="card mb-4" style="border-radius:14px;border:2px solid rgba(232,168,56,.2)">
            <div class="card-body">
                <h5 class="fw-700 mb-3">
                    <i class="bi bi-globe me-2" style="color:var(--primary)"></i>Estado de publicación
                </h5>
                <p class="text-muted small mb-3">Controla si esta receta es visible públicamente o se mantiene como borrador.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <label class="pub-option d-flex align-items-center gap-3 p-3 rounded-3 border flex-grow-1"
                           style="cursor:pointer;transition:all .18s" id="optPublicar">
                        <input type="radio" name="publicada" value="1" class="visually-hidden"
                               <?= $receta['publicada'] ? 'checked' : '' ?>>
                        <span class="d-inline-flex align-items-center justify-content-center rounded-2 flex-shrink-0"
                              style="width:40px;height:40px;background:rgba(46,204,113,.12)">
                            <i class="bi bi-check-circle-fill" style="color:#1a8a44;font-size:1.2rem"></i>
                        </span>
                        <div>
                            <div class="fw-700" style="font-size:.92rem">Publicada</div>
                            <div class="text-muted" style="font-size:.78rem">Visible para todos los usuarios</div>
                        </div>
                    </label>
                    <label class="pub-option d-flex align-items-center gap-3 p-3 rounded-3 border flex-grow-1"
                           style="cursor:pointer;transition:all .18s" id="optBorrador">
                        <input type="radio" name="publicada" value="0" class="visually-hidden"
                               <?= !$receta['publicada'] ? 'checked' : '' ?>>
                        <span class="d-inline-flex align-items-center justify-content-center rounded-2 flex-shrink-0"
                              style="width:40px;height:40px;background:rgba(232,168,56,.12)">
                            <i class="bi bi-pencil-square" style="color:#8a5e00;font-size:1.2rem"></i>
                        </span>
                        <div>
                            <div class="fw-700" style="font-size:.92rem">Borrador</div>
                            <div class="text-muted" style="font-size:.78rem">Solo visible para ti</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <div class="d-flex gap-3 pb-4">
            <button type="submit" class="btn btn-primary px-4" id="btnSubmitEditar">
                <i class="bi bi-cloud-check me-2"></i>Guardar cambios
            </button>
            <a href="<?= BASE_URL ?>/mis-recetas" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
</div>
</main>

<script>
document.getElementById('btnAddIng').addEventListener('click', () => {
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 ingrediente-row';
    row.innerHTML = `
        <div class="col-5"><input type="text" class="form-control form-control-sm" name="ing_nombre[]" placeholder="Ingrediente"></div>
        <div class="col-3"><input type="text" class="form-control form-control-sm" name="ing_cantidad[]" placeholder="Cantidad"></div>
        <div class="col-3"><input type="text" class="form-control form-control-sm" name="ing_unidad[]" placeholder="Unidad"></div>
        <div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm btn-remove-ing"><i class="bi bi-x"></i></button></div>`;
    document.getElementById('ingredientesList').appendChild(row);
});

document.getElementById('btnAddPaso').addEventListener('click', () => {
    const list = document.getElementById('pasosList');
    const num  = list.querySelectorAll('.paso-row').length + 1;
    const row  = document.createElement('div');
    row.className = 'paso-row mb-3 p-3 rounded-3';
    row.style.cssText = 'background:var(--surface-2);border:1px solid var(--border)';
    row.innerHTML = `
        <div class="d-flex gap-2 align-items-start mb-2">
            <span class="badge flex-shrink-0 mt-1"
                  style="background:var(--primary);min-width:28px;text-align:center">${num}</span>
            <textarea class="form-control form-control-sm flex-grow-1 paso-desc-txt" name="pasos[]" rows="2"
                      placeholder="Describe el paso ${num}..."></textarea>
            <button type="button" class="btn btn-outline-danger btn-sm align-self-start btn-remove-paso">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="row g-2 mt-1">
            <div class="col-sm-3">
                <label class="form-label form-label-sm mb-1 fw-600 text-muted">
                    <i class="bi bi-alarm me-1"></i>Duración (min)
                </label>
                <input type="number" class="form-control form-control-sm"
                       name="paso_duracion[]" min="1" max="9999" placeholder="Ej: 5">
            </div>
            <div class="col-sm-9">
                <label class="form-label form-label-sm mb-1 fw-600 text-muted">
                    <i class="bi bi-image me-1"></i>Imagen del paso
                </label>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <input type="file" class="form-control form-control-sm chef-paso-img flex-grow-1"
                           name="paso_imagen[]" accept="image/jpeg,image/png,image/webp" style="min-width:0">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary btn-generar-paso flex-shrink-0"
                            title="Generar ilustración con IA" style="white-space:nowrap">
                        <i class="bi bi-stars me-1"></i>Generar
                    </button>
                </div>
                <input type="hidden" name="paso_imagen_actual[]" value="">
                <div class="paso-img-preview-wrap mt-1" style="display:none">
                    <img src="" alt="Vista previa" class="rounded paso-img-preview"
                         style="height:60px;width:auto;object-fit:cover">
                    <span class="text-muted small ms-2 paso-preview-label">Vista previa</span>
                </div>
                <div class="paso-gen-status mt-1 small text-muted" style="display:none"></div>
            </div>
        </div>`;
    list.appendChild(row);
});

document.addEventListener('click', e => {
    if (e.target.closest('.btn-remove-ing'))  e.target.closest('.ingrediente-row').remove();
    if (e.target.closest('.btn-remove-paso')) e.target.closest('.paso-row').remove();

    // ── Botón Generar ilustración IA ──────────────────────
    const btnGen = e.target.closest('.btn-generar-paso');
    if (btnGen) generarIlustracion(btnGen);
});

// ── Preview imagen de PASO ────────────────────────────────
document.getElementById('pasosList').addEventListener('change', function (e) {
    const input = e.target.closest('.chef-paso-img');
    if (!input) return;
    const fila = input.closest('.paso-row');
    const wrap = fila.querySelector('.paso-img-preview-wrap');
    const prev = fila.querySelector('.paso-img-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = ev => { prev.src = ev.target.result; wrap.style.display = ''; };
        reader.readAsDataURL(input.files[0]);
    } else {
        wrap.style.display = 'none';
    }
});

// ── Toggle visual de opciones de publicación ──
(function () {
    const radios    = document.querySelectorAll('input[name="publicada"]');
    const labels    = document.querySelectorAll('.pub-option');
    const btnSubmit = document.getElementById('btnSubmitEditar');

    function updatePubUI() {
        radios.forEach((r, i) => {
            labels[i].style.borderColor = r.checked ? (r.value === '1' ? 'rgba(46,204,113,.5)' : 'rgba(232,168,56,.5)') : 'var(--border)';
            labels[i].style.background  = r.checked ? (r.value === '1' ? 'rgba(46,204,113,.06)' : 'rgba(232,168,56,.06)') : '';
        });
        const pub = document.querySelector('input[name="publicada"]:checked').value === '1';
        if (btnSubmit) {
            btnSubmit.innerHTML = pub
                ? '<i class="bi bi-cloud-check me-2"></i>Guardar y publicar'
                : '<i class="bi bi-pencil-square me-2"></i>Guardar como borrador';
        }
    }
    radios.forEach(r => r.addEventListener('change', updatePubUI));
    labels.forEach(lbl => lbl.addEventListener('click', () => {
        const r = lbl.querySelector('input[type="radio"]');
        if (r) { r.checked = true; updatePubUI(); }
    }));
    updatePubUI();
})();

// ── Generador de ilustraciones IA ─────────────────────────
const GD_IA = {
    endpoint: <?= json_encode(BASE_URL . '/ajax/generar_imagen_paso.php') ?>,
    csrf:     document.querySelector('meta[name="csrf-token"]')?.content || '',
    receta:   <?= json_encode($receta['titulo'] ?? '') ?>,
    ingredientes: () => [...document.querySelectorAll('input[name="ing_nombre[]"]')]
        .map(i => i.value.trim()).filter(Boolean),
};

async function generarIlustracion(btn) {
    const fila = btn.closest('.paso-row');
    if (!fila) return;

    const pasos = [...document.querySelectorAll('.paso-row')];
    const numeroPaso = pasos.indexOf(fila) + 1;

    const desc = fila.querySelector('.paso-desc-txt, textarea[name="pasos[]"]')?.value?.trim() || '';
    if (!desc) {
        mostrarEstadoPaso(fila, 'error', '⚠️ Escribe primero la descripción del paso.');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generando…';
    mostrarEstadoPaso(fila, 'info', '✨ Generando imagen, puede tardar hasta 30 segundos…');

    try {
        const res = await fetch(GD_IA.endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                descripcion:  desc,
                receta:       GD_IA.receta,
                numero:       numeroPaso,
                ingredientes: GD_IA.ingredientes(),
                csrf_token:   GD_IA.csrf,
            }),
        });
        const data = await res.json();

        if (!data.ok) {
            mostrarEstadoPaso(fila, 'error', '❌ ' + (data.error || 'Error desconocido.'));
        } else {
            const wrap   = fila.querySelector('.paso-img-preview-wrap');
            const img    = fila.querySelector('.paso-img-preview');
            const hidden = fila.querySelector('input[name="paso_imagen_actual[]"]');
            const label  = fila.querySelector('.paso-preview-label');

            if (img)    img.src = data.url + '?t=' + Date.now();
            if (wrap)   wrap.style.display = '';
            if (hidden) hidden.value = data.archivo;
            if (label)  label.textContent = '✨ Ilustración generada con IA';

            mostrarEstadoPaso(fila, 'ok', '✅ Ilustración guardada. Se enviará con el formulario.');
        }
    } catch (err) {
        mostrarEstadoPaso(fila, 'error', '❌ Error de conexión. Verifica tu internet e inténtalo de nuevo.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-stars me-1"></i>Regenerar';
    }
}

function mostrarEstadoPaso(fila, tipo, msg) {
    const el = fila.querySelector('.paso-gen-status');
    if (!el) return;
    el.style.display = '';
    el.className = 'paso-gen-status mt-1 small ' + (
        tipo === 'ok'    ? 'text-success' :
        tipo === 'error' ? 'text-danger'  : 'text-muted'
    );
    el.textContent = msg;
}
</script>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
