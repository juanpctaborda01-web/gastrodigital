/**
 * RecetasApp — JavaScript principal
 */

document.addEventListener('DOMContentLoaded', function () {

    // ── Auto-cerrar alertas después de 4 segundos ──
    const alertas = document.querySelectorAll('.alert');
    alertas.forEach(function (alerta) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alerta);
            bsAlert.close();
        }, 4000);
    });

    // ── Activar tooltips de Bootstrap ──
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // ── Activar popovers de Bootstrap ──
    const popoverEls = document.querySelectorAll('[data-bs-toggle="popover"]');
    popoverEls.forEach(function (el) {
        new bootstrap.Popover(el);
    });

    // ── Navbar: añadir sombra al hacer scroll ──
    const navbar = document.getElementById('mainNavbar');
    if (navbar) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 20) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // ── Botones de favoritos: toggle ──
    document.querySelectorAll('.btn-favorito').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const icon = this.querySelector('i');
            if (icon.classList.contains('bi-heart')) {
                icon.classList.replace('bi-heart', 'bi-heart-fill');
                this.classList.replace('btn-outline-danger', 'btn-danger');
            } else {
                icon.classList.replace('bi-heart-fill', 'bi-heart');
                this.classList.replace('btn-danger', 'btn-outline-danger');
            }
        });
    });

    // ── Confirmación antes de eliminar ──
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = this.dataset.confirm || '¿Estás seguro de que deseas continuar?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // ── Preview de imagen al seleccionar archivo ──
    const imgInputs = document.querySelectorAll('input[type="file"][data-preview]');
    imgInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            const previewId = this.dataset.preview;
            const preview   = document.getElementById(previewId);
            if (!preview || !this.files[0]) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(this.files[0]);
        });
    });

});
