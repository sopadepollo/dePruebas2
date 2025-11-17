<?php
require_once __DIR__ . '/templates/header.php';

use function App\Lib\findProduct;
use function App\Lib\applyPromotions;

// Get product ID
$id_producto = filter_input(INPUT_GET, 'id_producto', FILTER_SANITIZE_SPECIAL_CHARS);

// Get the product
$product = findProduct($id_producto);

if (!$product) {
    echo "<h1>Producto no encontrado</h1>";
    exit;
}

// Apply promotions
$productsWithPromotions = applyPromotions([$product]);
$product = $productsWithPromotions[0] ?? $product;

// Helper function to check if product has promotion
$hasPromotion = isset($product['has_promotion']) && $product['has_promotion'];
$originalPrice = $hasPromotion ? ($product['original_price'] ?? $product['price']) : $product['price'];
$currentPrice = $product['price'] ?? 0;
$savings = $hasPromotion ? ($originalPrice - $currentPrice) : 0;
$savingsPercent = $hasPromotion && $originalPrice > 0 ? round(($savings / $originalPrice) * 100) : 0;

// Ensure all required fields exist
$product['name'] = $product['name'] ?? 'Producto';
$product['description'] = $product['description'] ?? '';
$product['image'] = $product['image'] ?? 'placeholder.jpg';
$product['stock'] = $product['stock'] ?? 0;
$product['category_name'] = $product['category_name'] ?? 'Dulce Típico';
?>

<main class="product-page-container section">

    <div class="product-gallery">
        <?php if ($hasPromotion): ?>
            <div class="product-page-promo-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    <path d="M9 12h6"/>
                </svg>
                <?php echo "-{$savingsPercent}%"; ?>
            </div>
        <?php endif; ?>
        <img src="<?= htmlspecialchars($product['image'] ?? 'placeholder.jpg') ?>" alt="Vista principal de <?= htmlspecialchars($product['name']) ?>">
    </div>

    <div class="product-content">
        <h1><?= htmlspecialchars($product['name']) ?></h1>
        
        <?php if ($hasPromotion): ?>
            <!-- Promotion Alert -->
            <div class="promotion-alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    <path d="M9 12h6"/>
                    <path d="M12 9v6"/>
                </svg>
                <div>
                    <strong>¡Oferta Especial!</strong>
                    <p>Este producto tiene un descuento activo</p>
                </div>
            </div>
            
            <!-- Price with discount -->
            <div class="price-section">
                <span class="price-label">Precio original:</span>
                <span class="price-original">$<?= number_format($originalPrice, 2) ?></span>
                
                <span class="price-label">Precio con descuento:</span>
                <p class="price-large discounted">$<?= number_format($currentPrice, 2) ?></p>
                
                <p class="savings-highlight">
                    ¡Ahorras $<?= number_format($savings, 2) ?> 
                    (<?= $savingsPercent ?>%)!
                </p>
            </div>
        <?php else: ?>
            <p class="price-large">$<?= number_format($currentPrice, 2) ?></p>
        <?php endif; ?>

        <h3>Acerca de este artículo</h3>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        
        <div class="product-specs">
            <h4>Especificaciones</h4>
            <ul>
                <li><strong>Disponibilidad:</strong> <?= ($product['stock'] > 0) ? $product['stock'] . ' unidades en stock' : 'Agotado' ?></li>
                <li><strong>Categoría:</strong> <?= htmlspecialchars($product['category_name'] ?? 'Dulce Típico') ?></li>
            </ul>
        </div>
    </div>

    <aside class="product-action-card">
        <?php if ($hasPromotion): ?>
            <div class="action-card-promo">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    <path d="M9 12h6"/>
                    <path d="M12 9v6"/>
                </svg>
                <span>¡Oferta especial!</span>
            </div>
        <?php endif; ?>
        
        <div class="price-display">
            <?php if ($hasPromotion): ?>
                <span class="price-small-original">$<?= number_format($originalPrice, 2) ?></span>
            <?php endif; ?>
            <strong class="price-large <?= $hasPromotion ? 'discounted' : '' ?>">
                $<?= number_format($currentPrice, 2) ?>
            </strong>
            <?php if ($hasPromotion): ?>
                <span class="savings-badge">
                    Ahorras <?= $savingsPercent ?>%
                </span>
            <?php endif; ?>
        </div>
        
        <p class="delivery-info">
            Envío a todo San Luis Potosí
        </p>

        <?php if ($product['stock'] > 0): ?>
            <p class="stock-status available">
                Disponible
            </p>
            
            <div class="form-field quantity-container" style="margin-top: var(--space-md);">
                <label for="quantity">Cantidad:</label>
                <div class="quantity-selector">
                    <input type="number" id="quantity" name="cantidad" value="1" min="1" max="<?= htmlspecialchars($product['stock'] ?? 10) ?>" aria-label="Cantidad de producto">
                </div>
                <span id="stock-error-message" class="error-message"></span>
            </div>

            <button type="button" class="primary button full-width" id="add-to-cart-btn" data-product-id="<?= htmlspecialchars($product['id']) ?>">
                Agregar al carrito
            </button>
            <p id="cart-feedback" class="hint" hidden></p>

        <?php else: ?>
            <p class="stock-status unavailable">Agotado</p>
            <button class="primary button full-width" disabled>No disponible</button>
        <?php endif; ?>
    </aside>

</main>

<script>
    // Pass product data with promotion info to JavaScript
    window.__CURRENT_PRODUCT__ = <?php echo json_encode($product, JSON_UNESCAPED_UNICODE); ?>;
</script>

<script src="./js/vista_producto.js" defer></script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>