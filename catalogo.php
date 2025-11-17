<?php 
require_once __DIR__ . '/templates/header.php';

// Import functions for products with promotions
use function App\Lib\readProducts;
use function App\Lib\applyPromotions;

?>

<main class="container">
    <section id="catalogo" class="section">
        <header class="section-header">
            <h2>Catálogo de productos</h2>
            <p>Descubre nuestra selección de dulces de leche, cada uno creado con dedicación y sabor auténtico.</p>
        </header>

        

        <div class="category-filters">
            <div class="category-filters-desktop"></div>

            <div class="category-filters-mobile">
                <label for="category-select-mobile" class="control-label">Categoría:</label>
                <select id="category-select-mobile"></select>
            </div>
        </div>
        
        <div class="product-controls">
            <span class="control-label">Vista:</span>
            <div class="view-toggle" role="group" aria-label="Cambiar vista del catálogo">
                <button type="button" data-view="grid" class="active">Cuadrícula</button>
                <button type="button" data-view="list">Lista</button>
            </div>
        </div>
        <div class="product-list" data-view="grid" aria-live="polite"></div>
    </section>

    <script>
        // Pass products with promotion data to JavaScript
        // Categories are already loaded in header.php
        window.__INITIAL_PRODUCTS__ = <?php echo json_encode($productsWithPromotions, JSON_UNESCAPED_UNICODE); ?>;
        
        // DEBUG: Immediate check in JavaScript
        console.log('🔍 IMMEDIATE CHECK:');
        console.log('Total products:', window.__INITIAL_PRODUCTS__.length);
        const withPromos = window.__INITIAL_PRODUCTS__.filter(p => p.has_promotion);
        console.log('Products with promotions:', withPromos.length);
        if (withPromos.length > 0) {
            console.log('✅ First promo product:', withPromos[0]);
        } else {
            console.error('❌ NO PROMOTIONS IN JAVASCRIPT DATA!');
            console.log('Sample product:', window.__INITIAL_PRODUCTS__[0]);
        }
    </script>
    
    <script src="./js/catalogo.js" defer></script>
</main>

<?php require_once __DIR__ . '/templates/footer.php'; ?>