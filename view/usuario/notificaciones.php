<?php
require_once __DIR__ . '/../../config/config.php';

$pageTitle = 'Notificaciones';
$noLeidas = $totalNoLeidas;
$exito = $_SESSION['exito'] ?? null;
unset($_SESSION['exito']);
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-5">
    <div class="container" style="max-width:960px">
        <div class="gd-section-hero mt-3 mb-4">
            <div class="gd-section-hero-body">
                <span class="section-tag"><i class="bi bi-bell me-1"></i>Actividad de tu cuenta</span>
                <h1 class="gd-section-hero-title">Notificaciones</h1>
                <p class="gd-section-hero-sub">
                    <?= $noLeidas ?> sin leer · <?= count($notificaciones) ?> recientes
                </p>
            </div>
        </div>

        <?php if ($exito): ?>
            <div class="alert alert-success"><?= htmlspecialchars($exito) ?></div>
        <?php endif; ?>

        <?php if ($noLeidas > 0): ?>
            <form method="POST" action="<?= BASE_URL ?>/index.php?route=notificaciones/leer-todas" class="text-end mb-3">
                <?php csrfField(); ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-check2-all me-1"></i>Marcar todas como leídas
                </button>
            </form>
        <?php endif; ?>

        <div class="card panel-card">
            <div class="list-group list-group-flush">
                <?php foreach ($notificaciones as $notificacion): ?>
                    <?php
                    $ruta = ltrim((string)($notificacion['url'] ?? ''), '/');
                    $enlace = $ruta !== '' && !preg_match('#^(?:https?:)?//#i', $ruta)
                        ? rtrim(BASE_URL, '/') . '/' . $ruta
                        : null;
                    ?>
                    <div class="list-group-item p-3 <?= empty($notificacion['leida']) ? 'bg-body-tertiary' : '' ?>">
                        <div class="d-flex align-items-start gap-3">
                            <span class="text-primary fs-5" aria-hidden="true">
                                <i class="bi <?= match ($notificacion['tipo']) {
                                    'comentario' => 'bi-chat-left-text',
                                    'valoracion' => 'bi-star',
                                    'reporte' => 'bi-flag',
                                    default => 'bi-bell',
                                } ?>"></i>
                            </span>
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap justify-content-between gap-2">
                                    <p class="mb-1 fw-semibold"><?= htmlspecialchars($notificacion['mensaje']) ?></p>
                                    <time class="small text-muted"><?= htmlspecialchars($notificacion['created_at']) ?></time>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <?php if ($enlace): ?>
                                        <a href="<?= htmlspecialchars($enlace) ?>" class="small">Ver actividad</a>
                                    <?php endif; ?>
                                    <?php if (empty($notificacion['leida'])): ?>
                                        <form method="POST" action="<?= BASE_URL ?>/index.php?route=notificacion/leida">
                                            <?php csrfField(); ?>
                                            <input type="hidden" name="notificacion_id" value="<?= (int)$notificacion['id_notificacion'] ?>">
                                            <button type="submit" class="btn btn-link btn-sm p-0">Marcar como leída</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="small text-muted">Leída</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (empty($notificacion['leida'])): ?>
                                <span class="badge rounded-pill bg-primary">Nueva</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$notificaciones): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-bell-slash fs-2 d-block mb-2"></i>
                        No tienes notificaciones todavía.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
