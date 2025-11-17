<?php
require_once __DIR__ . '/templates/header.php';
?>

<main class="container" class="section" style="padding-top: 40px; min-height: 70vh;">
    <header class="section-header" style="max-width: 800px; margin: auto;">
        <h1>Más Opciones</h1>
        <p class="lead">Explora funcionalidades adicionales.</p>
    </header>

    <div class="more-options-list" style="max-width: 800px; margin: 40px auto; padding: 0; list-style: none;">
        <a href="/LasSevillanas/Proyectini/sucursales.php" class="more-option-item">
            <span class="icon" aria-hidden="true">📍</span>
            <span>Sucursales</span>
        </a>
        <a href="/LasSevillanas/Proyectini/historia.php" class="more-option-item">
            <span class="icon" aria-hidden="true">📜</span>
            <span>Historia</span>
        </a>
        <!--
        <a href="/LasSevillanas/Proyectini/valores.php" class="more-option-item">
            <span class="icon" aria-hidden="true">✨</span>
            <span>Valores</span>
        </a>
        <a href="/LasSevillanas/Proyectini/contacto.php" class="more-option-item">
            <span class="icon" aria-hidden="true">📧</span>
            <span>Contacto</span>
        </a>
-->
        <a href="/LasSevillanas/Proyectini/terminos.php" class="more-option-item">
            <span class="icon" aria-hidden="true">📄</span>
            <span>Términos y Condiciones</span>
        </a>
        </div>
</main>

<?php
require_once __DIR__ . '/templates/footer.php';
?>