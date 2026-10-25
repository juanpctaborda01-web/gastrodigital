<?php
/**
 * cook_mode.php — Modo Cocinar de GastroDigital
 * Muestra el asistente interactivo de cocina para una receta específica.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/receta.php';

// ── Validar ID de receta ──────────────────────────────────────────────────────
$recetaId = (int)($_GET['id'] ?? 0);
if ($recetaId <= 0) {
    redirect('recetas');
}

$model  = new Receta();
$receta = $model->obtenerCompleta($recetaId);

if (!$receta) {
    redirect('recetas');
}

$pasos       = $receta['pasos']       ?? [];
$ingredientes = $receta['ingredientes'] ?? [];
$totalPasos  = count($pasos);

// ── Metadatos para la vista ───────────────────────────────────────────────────
$pageTitle = 'Modo Cocinar: ' . $receta['titulo'];
$extraCss  = 'cook_mode.css';

$baseUrl = rtrim(BASE_URL, '/');

// ── Determinar si existe imagen de receta ─────────────────────────────────────
$recetaImgUrl = '';
if (!empty($receta['imagen']) && file_exists(ROOT_PATH . '/uploads/' . $receta['imagen'])) {
    $recetaImgUrl = $baseUrl . '/uploads/' . rawurlencode($receta['imagen']);
}

// ── Preparar datos de pasos como JSON para JS ─────────────────────────────────
$pasosJs = [];
foreach ($pasos as $p) {
    $imgUrl = '';
    if (!empty($p['imagen'])) {
        // Imágenes de pasos van en uploads/pasos/; retrocompatible con uploads/
        $rutaPasos   = ROOT_PATH . '/uploads/pasos/' . $p['imagen'];
        $rutaLegacy  = ROOT_PATH . '/uploads/' . $p['imagen'];

        if (file_exists($rutaPasos)) {
            $imgUrl = $baseUrl . '/uploads/pasos/' . rawurlencode($p['imagen']);
        } elseif (file_exists($rutaLegacy)) {
            $imgUrl = $baseUrl . '/uploads/' . rawurlencode($p['imagen']);
        }
    }

    // Fallback: si el paso no tiene imagen propia, usar la imagen principal de la receta
    if ($imgUrl === '') {
        $imgUrl = $recetaImgUrl;
    }

    $pasosJs[] = [
        'numero'      => (int)$p['numero'],
        'descripcion' => $p['descripcion'],
        'imagen'      => $imgUrl,
        'duracion'    => isset($p['duracion']) && $p['duracion'] > 0 ? (int)$p['duracion'] : null,
    ];
}

// ── Preparar datos de ingredientes como JSON para JS ─────────────────────────
$ingredientesJs = [];
foreach ($ingredientes as $ing) {
    $ingredientesJs[] = [
        'id'       => (int)$ing['id_ingrediente'],
        'nombre'   => $ing['nombre'],
        'cantidad' => $ing['cantidad'] ?? '',
        'unidad'   => $ing['unidad']   ?? '',
        'opcional' => !empty($ing['opcional']),
    ];
}

$pasosJson       = json_encode($pasosJs,       JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$ingredientesJson = json_encode($ingredientesJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="cm-main" id="cookModeApp"
      data-receta-id="<?= $recetaId ?>"
      aria-label="Modo Cocinar: <?= htmlspecialchars($receta['titulo']) ?>">

    <!-- ══ CABECERA DEL MODO COCINAR ══════════════════════════════════════════ -->
    <header class="cm-header" role="banner">
        <div class="cm-header-inner">
            <!-- Logo / volver -->
            <a href="<?= $baseUrl ?>/index.php?route=recetas&id=<?= $recetaId ?>"
               class="cm-back-link"
               aria-label="Salir del Modo Cocinar y volver a la receta"
               id="btnSalirModo">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span class="cm-back-text">Salir</span>
            </a>

            <!-- Título -->
            <div class="cm-header-title">
                <i class="bi bi-fire cm-fire-icon" aria-hidden="true"></i>
                <span class="cm-recipe-name"><?= htmlspecialchars($receta['titulo']) ?></span>
            </div>

            <!-- Progreso compacto en cabecera -->
            <div class="cm-header-progress" aria-live="polite" aria-atomic="true">
                <span class="cm-step-counter" id="headerStepCounter">Preparación</span>
            </div>
        </div>

        <!-- Barra de progreso global -->
        <div class="cm-progress-bar-wrap" role="progressbar"
             aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"
             id="globalProgressBar" aria-label="Progreso de la receta">
            <div class="cm-progress-bar-fill" id="globalProgressFill" style="width:0%"></div>
        </div>
    </header>

    <!-- ══ PANTALLA: PREPARACIÓN DE INGREDIENTES ══════════════════════════════ -->
    <section class="cm-screen cm-screen--active" id="screenIngredientes"
             aria-label="Pantalla de preparación de ingredientes">
        <div class="cm-screen-inner">

            <div class="cm-phase-badge">
                <i class="bi bi-list-check" aria-hidden="true"></i>
                Antes de comenzar
            </div>

            <h2 class="cm-phase-title">Prepara tus ingredientes</h2>
            <p class="cm-phase-sub">
                Marca cada ingrediente conforme lo tengas listo. Puedes comenzar cuando quieras.
            </p>

            <?php if (empty($ingredientes)): ?>
            <div class="cm-empty-box">
                <i class="bi bi-clipboard-x" aria-hidden="true"></i>
                <p>Esta receta no tiene ingredientes registrados.</p>
            </div>
            <?php else: ?>

            <!-- Contador de ingredientes -->
            <div class="cm-ing-counter" aria-live="polite" aria-atomic="true">
                <span id="ingCheckedCount">0</span> de
                <span id="ingTotalCount"><?= count($ingredientes) ?></span>
                ingredientes preparados
                <div class="cm-ing-mini-bar">
                    <div class="cm-ing-mini-fill" id="ingMiniBar" style="width:0%"></div>
                </div>
            </div>

            <!-- Lista de ingredientes con checkboxes -->
            <ul class="cm-ing-list" id="ingList" role="list">
                <?php foreach ($ingredientes as $i => $ing): ?>
                <li class="cm-ing-item" data-ing-index="<?= $i ?>">
                    <label class="cm-ing-label" for="ing-<?= (int)$ing['id_ingrediente'] ?>">
                        <span class="cm-ing-check-wrap">
                            <input type="checkbox"
                                   class="cm-ing-checkbox visually-hidden"
                                   id="ing-<?= (int)$ing['id_ingrediente'] ?>"
                                   data-ing-id="<?= (int)$ing['id_ingrediente'] ?>"
                                   aria-label="Marcar <?= htmlspecialchars($ing['nombre']) ?> como preparado">
                            <span class="cm-ing-check-box" aria-hidden="true">
                                <i class="bi bi-check2" aria-hidden="true"></i>
                            </span>
                        </span>
                        <span class="cm-ing-info">
                            <span class="cm-ing-nombre"><?= htmlspecialchars($ing['nombre']) ?></span>
                            <?php $cantStr = trim(($ing['cantidad'] ?? '') . ' ' . ($ing['unidad'] ?? '')); ?>
                            <?php if ($cantStr !== ''): ?>
                            <span class="cm-ing-cantidad"><?= htmlspecialchars($cantStr) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($ing['opcional'])): ?>
                            <span class="cm-ing-opcional">Opcional</span>
                            <?php endif; ?>
                        </span>
                    </label>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <!-- Consejo contextual -->
            <div class="cm-tip cm-tip--info" role="note" aria-label="Consejo">
                <i class="bi bi-lightbulb-fill" aria-hidden="true"></i>
                <span>Preparar todos los ingredientes antes de cocinar
                      (<em>mise en place</em>) ayuda a que el proceso sea más fluido y preciso.</span>
            </div>

            <?php if ($totalPasos > 0): ?>
            <button type="button" class="cm-btn cm-btn--primary cm-btn--lg cm-btn--block"
                    id="btnIniciarCocina"
                    aria-label="Comenzar a cocinar la receta <?= htmlspecialchars($receta['titulo']) ?>">
                <i class="bi bi-play-fill" aria-hidden="true"></i>
                Comenzar a cocinar
            </button>
            <?php else: ?>
            <div class="cm-empty-box">
                <i class="bi bi-journal-x" aria-hidden="true"></i>
                <p>Esta receta no tiene pasos registrados. No se puede iniciar el Modo Cocinar.</p>
            </div>
            <a href="<?= $baseUrl ?>/index.php?route=recetas&id=<?= $recetaId ?>"
               class="cm-btn cm-btn--outline cm-btn--lg cm-btn--block">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Volver a la receta
            </a>
            <?php endif; ?>

        </div>
    </section>

    <!-- ══ PANTALLA: CARRUSEL DE PASOS ════════════════════════════════════════ -->
    <section class="cm-screen" id="screenPasos"
             aria-label="Pantalla de pasos de cocina" hidden>
        <div class="cm-pasos-layout">

            <!-- Columna principal: paso actual ─────────────────────────────── -->
            <div class="cm-paso-main">

                <!-- Indicador de paso -->
                <div class="cm-paso-indicator" aria-live="polite" aria-atomic="true">
                    <span class="cm-paso-num-label" id="pasoNumLabel">Paso 1 de <?= $totalPasos ?></span>
                    <div class="cm-paso-indicator-actions">
                        <button type="button" class="cm-mini-ing-toggle"
                                id="btnMiniIngredientes"
                                aria-expanded="false"
                                aria-controls="miniIngPanel"
                                title="Ver ingredientes">
                            <i class="bi bi-card-list" aria-hidden="true"></i>
                            <span>Ingredientes</span>
                        </button>
                        <button type="button" class="cm-mini-ing-toggle cm-mini-chefbot-toggle"
                                id="btnMiniChefbot"
                                aria-expanded="false"
                                aria-controls="cmChefbotPanel"
                                title="Preguntar al ChefBot">
                            <img src="<?= $baseUrl ?>/img/Icon/ICON-Sobrantes/chefbot.png"
                                 alt="" style="width:14px;height:14px;object-fit:contain;filter:brightness(0.3)"
                                 onerror="this.style.display='none'">
                            <span>ChefBot</span>
                        </button>
                    </div>
                </div>

                <!-- Tarjeta del paso ────────────────────────────────────────── -->
                <article class="cm-paso-card" id="pasoCard" aria-live="polite" aria-atomic="false">

                    <!-- Imagen del paso (si existe) -->
                    <div class="cm-paso-img-wrap" id="pasoImgWrap" hidden>
                        <img id="pasoImg" src="" alt="" class="cm-paso-img" loading="eager" decoding="async" fetchpriority="high">
                    </div>

                    <!-- Número circular grande -->
                    <div class="cm-paso-numero-badge" id="pasoNumeroBadge" aria-hidden="true">1</div>

                    <!-- Descripción -->
                    <div class="cm-paso-body">
                        <p class="cm-paso-desc" id="pasoDesc"></p>
                    </div>

                    <!-- Consejo contextual del paso -->
                    <div class="cm-tip cm-tip--step" id="pasoTip" hidden role="note"></div>

                </article>

                <!-- Estado de completado del paso ───────────────────────────── -->
                <div class="cm-paso-done-row">
                    <button type="button"
                            class="cm-btn cm-btn--check"
                            id="btnMarcarCompletado"
                            aria-pressed="false"
                            aria-label="Marcar este paso como completado">
                        <i class="bi bi-check-circle" id="iconCheck" aria-hidden="true"></i>
                        <span id="labelCheck">Marcar como completado</span>
                    </button>
                </div>

                <!-- Navegación ───────────────────────────────────────────────── -->
                <nav class="cm-nav" aria-label="Navegación entre pasos">
                    <button type="button" class="cm-btn cm-btn--nav cm-btn--prev"
                            id="btnAnterior"
                            aria-label="Paso anterior"
                            disabled>
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        <span>Anterior</span>
                    </button>

                    <!-- Miniaturas de pasos (desktop) -->
                    <div class="cm-step-dots" id="stepDots" role="tablist"
                         aria-label="Navegación por paso">
                    </div>

                    <button type="button" class="cm-btn cm-btn--nav cm-btn--next"
                            id="btnSiguiente"
                            aria-label="Siguiente paso">
                        <span id="lblSiguiente">Siguiente</span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </button>
                </nav>

            </div><!-- /.cm-paso-main -->

            <!-- Panel lateral: ingredientes (desktop) ──────────────────────── -->
            <aside class="cm-sidebar" id="cmSidebar" aria-label="Panel de ingredientes">
                <div class="cm-sidebar-header">
                    <i class="bi bi-card-list" aria-hidden="true"></i>
                    <span>Ingredientes</span>
                    <span class="cm-sidebar-counter" id="sidebarIngCounter">0/<?= count($ingredientes) ?></span>
                </div>
                <ul class="cm-sidebar-list" id="sidebarIngList" role="list">
                    <?php foreach ($ingredientes as $i => $ing): ?>
                    <li class="cm-sidebar-item" data-ing-index="<?= $i ?>">
                        <span class="cm-sidebar-check" aria-hidden="true">
                            <i class="bi bi-circle" id="sidebarCheck-<?= $i ?>"></i>
                        </span>
                        <span class="cm-sidebar-ing-nombre"><?= htmlspecialchars($ing['nombre']) ?></span>
                        <?php $cantStr = trim(($ing['cantidad'] ?? '') . ' ' . ($ing['unidad'] ?? '')); ?>
                        <?php if ($cantStr !== ''): ?>
                        <span class="cm-sidebar-ing-cant"><?= htmlspecialchars($cantStr) ?></span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </aside>

        </div><!-- /.cm-pasos-layout -->

        <!-- Panel de ingredientes móvil (overlay) ───────────────────────────── -->
        <div class="cm-mini-ing-panel" id="miniIngPanel" hidden
             role="dialog" aria-label="Ingredientes de la receta" aria-modal="false">
            <div class="cm-mini-ing-header">
                <span><i class="bi bi-card-list me-2" aria-hidden="true"></i>Ingredientes</span>
                <button type="button" class="cm-mini-close" id="btnCerrarMiniIng"
                        aria-label="Cerrar panel de ingredientes">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <ul class="cm-mini-ing-list" id="miniIngList" role="list">
                <?php foreach ($ingredientes as $i => $ing): ?>
                <li class="cm-mini-ing-item" data-ing-index="<?= $i ?>">
                    <i class="bi bi-circle cm-mini-ing-icon" id="miniCheck-<?= $i ?>" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($ing['nombre']) ?></span>
                    <?php $cantStr = trim(($ing['cantidad'] ?? '') . ' ' . ($ing['unidad'] ?? '')); ?>
                    <?php if ($cantStr !== ''): ?>
                    <span class="cm-mini-ing-cant"><?= htmlspecialchars($cantStr) ?></span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

    </section><!-- /#screenPasos -->

    <!-- ══ PANEL CHEFBOT INLINE (MODO COCINAR) ═══════════════════════════════ -->
    <!--
        Este panel es específico del Modo Cocinar.
        El botón flotante global del ChefBot se oculta via CSS en esta página.
        El panel usa el mismo endpoint /index.php?route=chatbot con CSRF.
    -->
    <?php
    $csrfCook   = function_exists('csrfToken') ? csrfToken() : ($_SESSION['csrf_token'] ?? '');
    $chatStorageCook = 'gd_cook_chat_' . $recetaId;
    $horaCook   = date('H:i');
    ?>

    <!-- Botón para abrir/cerrar el ChefBot en móvil (fijo, esquina inferior derecha) -->
    <button type="button"
            class="cm-chefbot-fab"
            id="cmChefbotFab"
            aria-label="Abrir ChefBot — asistente de cocina"
            aria-expanded="false"
            aria-controls="cmChefbotPanel"
            title="Pregúntale al ChefBot">
        <img src="<?= $baseUrl ?>/img/Icon/ICON-Sobrantes/chefbot.png"
             alt="" style="width:26px;height:26px;object-fit:contain;filter:brightness(0) invert(1)"
             onerror="this.style.display='none'">
        <i class="bi bi-x-lg cm-chefbot-fab-close" aria-hidden="true" hidden></i>
        <span class="cm-chefbot-fab-badge" id="cmChefbotBadge" hidden aria-hidden="true">1</span>
    </button>

    <!-- Panel del ChefBot -->
    <aside class="cm-chefbot-panel" id="cmChefbotPanel"
           aria-label="ChefBot — Asistente de cocina"
           hidden>

        <!-- Header -->
        <div class="cm-chefbot-header">
            <div class="cm-chefbot-identity">
                <div class="cm-chefbot-avatar" aria-hidden="true">
                    <img src="<?= $baseUrl ?>/img/Icon/ICON-Sobrantes/chefbot.png"
                         alt=""
                         style="width:20px;height:20px;object-fit:contain;filter:brightness(0) invert(1)"
                         onerror="this.style.display='none'">
                </div>
                <div>
                    <div class="cm-chefbot-name">ChefBot</div>
                    <div class="cm-chefbot-status">
                        <span class="cm-chefbot-dot" aria-hidden="true"></span>
                        <span id="cmChefbotStatusText">Asistente de cocina</span>
                    </div>
                </div>
            </div>
            <div class="cm-chefbot-header-actions">
                <button type="button" class="cm-chefbot-action-btn"
                        id="cmChefbotClearBtn"
                        title="Limpiar conversación"
                        aria-label="Limpiar conversación del ChefBot">
                    <i class="bi bi-trash3" aria-hidden="true"></i>
                </button>
                <button type="button" class="cm-chefbot-action-btn"
                        id="cmChefbotCloseBtn"
                        aria-label="Cerrar ChefBot">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <!-- Mensajes -->
        <div class="cm-chefbot-messages" id="cmChefbotMessages" role="log" aria-live="polite">
            <div class="cm-cb-msg cm-cb-msg--bot" data-time="<?= $horaCook ?>">
                <div class="cm-cb-bubble">
                    👨‍🍳 ¡Hola! Estoy aquí para ayudarte mientras cocinas
                    <strong><?= htmlspecialchars($receta['titulo']) ?></strong>.
                    Pregúntame sobre ingredientes, técnicas o tiempos.
                </div>
                <div class="cm-cb-time"><?= $horaCook ?></div>
            </div>
        </div>

        <!-- Sugerencias contextuales rápidas -->
        <div class="cm-chefbot-suggestions" id="cmChefbotSuggestions">
            <button class="cm-cb-chip" data-msg="¿Cuáles son los ingredientes de <?= htmlspecialchars($receta['titulo']) ?>?">
                🧂 Ingredientes
            </button>
            <button class="cm-cb-chip" data-msg="¿Cuánto tiempo tarda <?= htmlspecialchars($receta['titulo']) ?>?">
                ⏱️ Tiempo total
            </button>
            <button class="cm-cb-chip" data-msg="Dame un consejo para preparar <?= htmlspecialchars($receta['titulo']) ?>">
                💡 Consejo
            </button>
            <button class="cm-cb-chip" data-msg="¿Cuál es la dificultad de <?= htmlspecialchars($receta['titulo']) ?>?">
                📊 Dificultad
            </button>
        </div>

        <!-- Input -->
        <div class="cm-chefbot-input-area">
            <form id="cmChefbotForm" autocomplete="off" novalidate>
                <div class="cm-chefbot-input-wrap">
                    <textarea id="cmChefbotInput"
                              class="cm-chefbot-input"
                              placeholder="Pregunta sobre esta receta…"
                              maxlength="500"
                              rows="1"
                              aria-label="Mensaje para el ChefBot"></textarea>
                    <button type="submit"
                            class="cm-chefbot-send"
                            id="cmChefbotSendBtn"
                            aria-label="Enviar mensaje al ChefBot">
                        <i class="bi bi-send-fill" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        </div>

    </aside><!-- /#cmChefbotPanel -->

    <!-- Datos para el JS del ChefBot inline -->
    <script>
    window.GD_COOK_BOT = {
        endpoint:   <?= json_encode($baseUrl . '/index.php?route=chatbot') ?>,
        csrfToken:  <?= json_encode($csrfCook) ?>,
        storageKey: <?= json_encode($chatStorageCook) ?>,
        recetaNombre: <?= json_encode($receta['titulo']) ?>,
        recetaId:   <?= $recetaId ?>
    };
    </script>

    <!-- ══ PANTALLA: RECETA COMPLETADA ════════════════════════════════════════ -->
    <section class="cm-screen" id="screenFin"
             aria-label="Pantalla de receta completada" hidden>
        <div class="cm-fin-inner">

            <div class="cm-fin-anim" aria-hidden="true">
                <div class="cm-fin-circle">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <div class="cm-fin-sparkles">
                    <span></span><span></span><span></span>
                    <span></span><span></span><span></span>
                </div>
            </div>

            <h2 class="cm-fin-title">¡Receta completada!</h2>
            <p class="cm-fin-sub">
                Has terminado de preparar
                <strong><?= htmlspecialchars($receta['titulo']) ?></strong>.
                ¡Buen provecho!
            </p>

            <!-- Resumen de pasos -->
            <div class="cm-fin-stats">
                <div class="cm-fin-stat">
                    <span class="cm-fin-stat-val"><?= $totalPasos ?></span>
                    <span class="cm-fin-stat-lbl">Pasos seguidos</span>
                </div>
                <div class="cm-fin-stat">
                    <span class="cm-fin-stat-val" id="finIngCount">0</span>
                    <span class="cm-fin-stat-lbl">Ingredientes listos</span>
                </div>
                <div class="cm-fin-stat">
                    <span class="cm-fin-stat-val"><?= (int)$receta['tiempo_preparacion'] ?> min</span>
                    <span class="cm-fin-stat-lbl">Tiempo estimado</span>
                </div>
            </div>

            <!-- Acciones finales -->
            <div class="cm-fin-actions">
                <button type="button" class="cm-btn cm-btn--outline"
                        id="btnRevisarPasos"
                        aria-label="Revisar los pasos de la receta">
                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                    Revisar pasos
                </button>
                <a href="<?= $baseUrl ?>/index.php?route=recetas&id=<?= $recetaId ?>"
                   class="cm-btn cm-btn--primary"
                   aria-label="Volver al detalle de la receta">
                    <i class="bi bi-house-fill" aria-hidden="true"></i>
                    Volver a la receta
                </a>
                <?php if (isLoggedIn()): ?>
                <a href="<?= $baseUrl ?>/index.php?route=recetas&id=<?= $recetaId ?>#valoracion"
                   class="cm-btn cm-btn--secondary"
                   aria-label="Valorar esta receta">
                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                    Valorar receta
                </a>
                <?php endif; ?>
            </div>

        </div>
    </section><!-- /#screenFin -->

</main><!-- /#cookModeApp -->

<!-- ═══════════════════════════════════════════════════════════════════════════
     Datos serializados para JavaScript (no hay PHP en cook_mode.js)
     ═══════════════════════════════════════════════════════════════════════════ -->
<script>
    window.GD_COOK = {
        recetaId:     <?= $recetaId ?>,
        baseUrl:      <?= json_encode($baseUrl) ?>,
        pasos:        <?= $pasosJson ?>,
        ingredientes: <?= $ingredientesJson ?>
    };
</script>
<?php $cookJsFile = __DIR__ . '/../../js/cook_mode.js';
      $cookJsV   = is_file($cookJsFile) ? filemtime($cookJsFile) : 1; ?>
<script defer src="<?= $baseUrl ?>/js/cook_mode.js?v=<?= $cookJsV ?>"></script>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
