<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Título de página (puede sobreescribirse antes de incluir este archivo)
$pageTitle = $pageTitle ?? 'RecetasApp';
$baseUrl   = rtrim(BASE_URL, '/');
$mainCssVersion = filemtime(__DIR__ . '/../styles/main.css');
$chatbotCssVersion = filemtime(__DIR__ . '/../styles/chatbot.css');
$bodyClass = 'app-layout';
if (strpos($_SERVER['PHP_SELF'] ?? '', '/view/auth/') !== false) {
    $bodyClass = 'auth-page';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | GastroDigital</title>
    <meta name="base-url" content="<?= $baseUrl ?>">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
    <!-- Aplicar dark mode ANTES del paint para evitar flash -->
    <script>
        (function(){
            var t = localStorage.getItem('gdTheme');
            if (t === 'dark') {
                document.documentElement.setAttribute('data-theme','dark');
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <!-- Bootstrap 5 -->
    <link href="<?= $baseUrl ?>/assets/vendor/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="<?= $baseUrl ?>/assets/vendor/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/assets/vendor/nunito/nunito.css" rel="stylesheet">
    <!-- Estilos propios — v=8.0 (sidebar premium + landing + limpio) -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/styles/main.css?v=<?= $mainCssVersion ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/styles/chatbot.css?v=<?= $chatbotCssVersion ?>">
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="<?= $baseUrl ?>/styles/<?= $extraCss ?>">
    <?php endif; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">
