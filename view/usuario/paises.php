<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../../config/config.php';

$pageTitle = 'Explorar Países';
$pdo       = Conexion::getInstancia()->getPDO();
$baseUrl   = rtrim(BASE_URL, '/');

// Query robusta con LEFT JOIN y múltiples fallbacks
$paises = [];
try {
    $stmt = $pdo->query("
        SELECT p.id_pais AS id,
               p.nombre,
               COALESCE(p.codigo_iso, '') AS codigo_iso,
               COUNT(DISTINCT r.id_receta) AS total_recetas
        FROM paises p
        LEFT JOIN recetas r ON r.id_pais = p.id_pais AND r.publicada = 1
        GROUP BY p.id_pais, p.nombre, p.codigo_iso
        HAVING total_recetas > 0
        ORDER BY total_recetas DESC, p.nombre ASC
    ");
    $paises = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    try {
        $stmt = $pdo->query("
            SELECT p.id AS id,
                   p.nombre,
                   COALESCE(p.codigo_iso, '') AS codigo_iso,
                   COUNT(DISTINCT r.id) AS total_recetas
            FROM paises p
            LEFT JOIN recetas r ON r.id_pais = p.id AND r.publicada = 1
            GROUP BY p.id, p.nombre, p.codigo_iso
            HAVING total_recetas > 0
            ORDER BY total_recetas DESC, p.nombre ASC
        ");
        $paises = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e2) {
        // Último recurso: solo países sin contar recetas
        try {
            $stmt = $pdo->query("SELECT id_pais AS id, nombre, codigo_iso, 0 AS total_recetas FROM paises ORDER BY nombre ASC LIMIT 50");
            $paises = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e3) {
            $paises = [];
        }
    }
}
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-5">
<div class="container">

    <!-- ENCABEZADO -->
    <div class="text-center mb-5">
        <span class="section-tag">
            <i class="bi bi-globe-americas me-1"></i>Gastronomía Mundial
        </span>
        <h1 class="section-title">Explora recetas por país</h1>
        <p class="section-subtitle">
            Descubre sabores auténticos de <?= count($paises) ?> países con recetas publicadas
        </p>
    </div>

    <?php if (empty($paises)): ?>
    <div class="empty-state">
        <i class="bi bi-globe"></i>
        <h5>Aún no hay recetas por país disponibles</h5>
        <a href="<?= $baseUrl ?>/recetas" class="btn btn-primary mt-2">
            <i class="bi bi-search me-1"></i>Ver todas las recetas
        </a>
    </div>

    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($paises as $pais):
            $nombre = htmlspecialchars($pais['nombre']);
            $total  = (int)($pais['total_recetas'] ?? 0);
            $iso    = !empty($pais['codigo_iso']) ? strtolower($pais['codigo_iso']) : '';
            
            // Si no hay ISO, intentar inferirlo del nombre
            if (!$iso) {
                $mapeo = [
                    'colombia' => 'co', 'mexico' => 'mx', 'méxico' => 'mx', 'argentina' => 'ar',
                    'españa' => 'es', 'espana' => 'es', 'italia' => 'it', 'francia' => 'fr',
                    'japon' => 'jp', 'japón' => 'jp', 'china' => 'cn', 'eeuu' => 'us',
                    'estados unidos' => 'us', 'brasil' => 'br', 'peru' => 'pe', 'perú' => 'pe',
                    'chile' => 'cl', 'venezuela' => 've', 'alemania' => 'de', 'grecia' => 'gr',
                    'turquia' => 'tr', 'turquía' => 'tr', 'india' => 'in', 'tailandia' => 'th',
                    'vietnam' => 'vn', 'corea del sur' => 'kr', 'marruecos' => 'ma', 'etiopia' => 'et',
                    'etiopía' => 'et', 'libano' => 'lb', 'líbano' => 'lb', 'portugal' => 'pt',
                    'reino unido' => 'gb', 'inglaterra' => 'gb', 'canada' => 'ca', 'canadá' => 'ca'
                ];
                $iso = $mapeo[strtolower($nombre)] ?? '';
            }
            
            $flagPath = $iso ? "{$baseUrl}/img/icon/flags/4x3/{$iso}.svg" : '';
            
            // Colores aleatorios suaves para países
            $colores = ['#e74c3c','#3498db','#2ecc71','#f39c12','#9b59b6','#1abc9c','#e67e22','#34495e'];
            $color = $colores[abs(crc32($nombre)) % count($colores)];
        ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="<?= $baseUrl ?>/recetas?pais=<?= urlencode($nombre) ?>"
               class="pais-card text-decoration-none"
               style="--pais-color:<?= $color ?>">
                
                <!-- Bandera -->
                <div class="pais-card-flag">
                    <?php if ($flagPath): ?>
                    <img src="<?= $flagPath ?>"
                         alt="<?= $nombre ?>"
                         class="pais-flag-img"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <?php endif; ?>
                    <!-- Fallback: icono mundo -->
                    <span class="pais-flag-fallback" style="display:<?= $flagPath ? 'none' : 'flex' ?>;color:var(--pais-color)">
                        <i class="bi bi-globe2"></i>
                    </span>
                </div>

                <!-- Info -->
                <h5 class="pais-card-nombre"><?= $nombre ?></h5>
                <p class="pais-card-count" style="color:var(--pais-color)">
                    <i class="bi bi-egg-fried me-1"></i><?= $total ?> receta<?= $total !== 1 ? 's' : '' ?>
                </p>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</main>

<style>
.pais-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 2rem 1.5rem;
    background: #fff;
    border: 2px solid rgba(0,0,0,.08);
    border-radius: 18px;
    transition: all .3s cubic-bezier(.4,0,.2,1);
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    position: relative;
    overflow: hidden;
}
.pais-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 150%;
    height: 150%;
    background: radial-gradient(circle, var(--pais-color) 0%, transparent 70%);
    opacity: 0;
    transition: opacity .4s;
}
.pais-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 32px rgba(0,0,0,.15);
    border-color: var(--pais-color);
}
.pais-card:hover::before {
    opacity: .08;
}
.pais-card-flag {
    width: 90px;
    height: 90px;
    margin-bottom: 1.25rem;
    border-radius: 50%;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(0,0,0,.12);
    position: relative;
    z-index: 1;
    transition: transform .3s;
}
.pais-card:hover .pais-card-flag {
    transform: scale(1.1) rotate(5deg);
}
.pais-flag-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.pais-flag-fallback {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
}
.pais-card-nombre {
    font-size: 1.1rem;
    font-weight: 700;
    color: #2c1810;
    margin-bottom: .5rem;
    position: relative;
    z-index: 1;
}
.pais-card-count {
    font-size: .9rem;
    font-weight: 600;
    margin: 0;
    position: relative;
    z-index: 1;
}
@media (max-width: 576px) {
    .pais-card {
        padding: 1.5rem 1rem;
    }
    .pais-card-flag {
        width: 70px;
        height: 70px;
    }
}
</style>

<?php include __DIR__ . '/../../assets/chatbot.php'; ?>
<?php include __DIR__ . '/../../assets/fooder.php'; ?>
