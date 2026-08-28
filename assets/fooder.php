<?php
$baseUrl = defined('BASE_URL') ? BASE_URL : 'http://localhost/recetas';
$anio    = date('Y');
?>
<footer class="footer mt-auto">
    <div class="container">
        <div class="row gy-4">

            <!-- Marca -->
            <div class="col-lg-4 col-md-6">
                <a href="<?= $baseUrl ?>" class="d-inline-block mb-3">
                    <img src="<?= $baseUrl ?>/img/logoGastroDigital.jpeg" alt="GastroDigital" height="70">
                </a>
                <p class="text-white-50 small">
                    Descubre, crea y comparte recetas increíbles con una comunidad apasionada por la cocina.
                </p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="footer-social" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="footer-social" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="footer-social" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="footer-social" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                </div>
            </div>

            <!-- Explorar -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="footer-heading">Explorar</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?= $baseUrl ?>/index.php?route=recetas">Recetas</a></li>
                    <li><a href="<?= $baseUrl ?>/admin/categorias">Categorías</a></li>
                </ul>
            </div>

            <!-- Cuenta -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="footer-heading">Cuenta</h6>
                <ul class="list-unstyled footer-links">
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <?php
                        $rolActual = $_SESSION['rol'] ?? 'usuario';
                        $dashLink  = match($rolActual) {
                            'admin' => $baseUrl . '/admin',
                            'chef'  => $baseUrl . '/mis-recetas',
                            default => $baseUrl . '/dashboard',
                        };
                        ?>
                        <li><a href="<?= $dashLink ?>">Mi panel</a></li>
                        <li><a href="<?= $baseUrl ?>/index.php?route=logout">Cerrar sesión</a></li>
                    <?php else: ?>
                        <li><a href="<?= $baseUrl ?>/index.php?route=login">Iniciar sesión</a></li>
                        <li><a href="<?= $baseUrl ?>/index.php?route=register">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Contacto -->
            <div class="col-lg-4 col-md-6">
                <h6 class="footer-heading">Contacto</h6>
                <ul class="list-unstyled footer-links">
                    <li class="d-flex align-items-center gap-2">
                        <i class="bi bi-envelope" style="color:var(--secondary)"></i>
                        <span>GastroDigital73@gmail.com</span>
                    </li>
                    <li class="d-flex align-items-center gap-2 mt-2">
                        <i class="bi bi-geo-alt" style="color:var(--secondary)"></i>
                        <span>Medellín, Colombia</span>
                    </li>
                </ul>
            </div>

        </div>

        <hr class="border-secondary mt-4">

        <div class="row align-items-center py-2">
            <div class="col-md-6 text-center text-md-start">
                <small class="text-white-50">
                    &copy; <?= $anio ?> GastroDigital &middot; &middot; Todos los derechos reservados.
                </small>
            </div>
            <div class="col-md-6 text-center text-md-start">
                <small class="text-white-50">
                   Desarrollado Por J.P
                </small>
            </div>
                <div class="col-md-6 text-center text-md-end mt-2 mt-md-0"> 
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $baseUrl ?>/js/app.js"></script>
<?php include __DIR__ . '/chatbot.php'; ?>
</body>
</html>
