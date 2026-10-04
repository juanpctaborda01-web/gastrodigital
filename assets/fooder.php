<?php
$baseUrl = defined('BASE_URL') ? BASE_URL : 'http://localhost/recetas';
$anio    = date('Y');
?>
<footer class="gd-footer">
    <div class="container">
        <div class="row gy-4">

            <!-- Marca -->
            <div class="col-lg-4 col-md-6">
                <a href="<?= $baseUrl ?>" class="gd-footer-brand">
                    <img src="<?= $baseUrl ?>/img/GastroDigitalLogo2.png" alt="GastroDigital" class="gd-footer-logo">
                    <span class="gd-footer-brand-name">GastroDigital</span>
                </a>
                <p class="gd-footer-desc">
                    Descubre, crea y comparte recetas increíbles con una comunidad apasionada por la cocina.
                </p>
                <div class="gd-footer-social">
                    <a href="#" class="gd-footer-social-link" aria-label="Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="#" class="gd-footer-social-link" aria-label="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>
                    <a href="#" class="gd-footer-social-link" aria-label="Twitter">
                        <i class="bi bi-twitter-x"></i>
                    </a>
                    <a href="#" class="gd-footer-social-link" aria-label="YouTube">
                        <i class="bi bi-youtube"></i>
                    </a>
                </div>
            </div>

            <!-- Explorar -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="gd-footer-heading">Explorar</h6>
                <ul class="gd-footer-links">
                    <li><a href="<?= $baseUrl ?>/recetas">Recetas</a></li>
                    <li><a href="<?= $baseUrl ?>/categorias">Categorías</a></li>
                    <li><a href="<?= $baseUrl ?>/paises">Países</a></li>
                </ul>
            </div>

            <!-- Cuenta -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="gd-footer-heading">Cuenta</h6>
                <ul class="gd-footer-links">
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
                        <li><a href="<?= $baseUrl ?>/perfil">Perfil</a></li>
                        <li><a href="<?= $baseUrl ?>/logout">Cerrar sesión</a></li>
                    <?php else: ?>
                        <li><a href="<?= $baseUrl ?>/login">Iniciar sesión</a></li>
                        <li><a href="<?= $baseUrl ?>/register">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Contacto -->
            <div class="col-lg-4 col-md-6">
                <h6 class="gd-footer-heading">Contacto</h6>
                <ul class="gd-footer-contact">
                    <li>
                        <i class="bi bi-envelope-fill"></i>
                        <a href="mailto:GastroDigital73@gmail.com">GastroDigital73@gmail.com</a>
                    </li>
                    <li>
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>Medellín, Colombia</span>
                    </li>
                </ul>
            </div>

        </div>

        <div class="gd-footer-bottom">
            <div class="gd-footer-copyright">
                &copy; <?= $anio ?> GastroDigital. Todos los derechos reservados.
            </div>
            <div class="gd-footer-credits">
                Desarrollado por <strong>J.P</strong>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $baseUrl ?>/js/app.js?v=5.0"></script>
<?php include __DIR__ . '/chatbot.php'; ?>
</body>
</html>
