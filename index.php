<?php 
require_once __DIR__ . '/templates/header.php'; 
use function App\Lib\getRecommendedProductsForUser;
use function App\Lib\getPDO;

// DEBUG: Let's see what's happening
$recommendedProducts = [];
$activePromotions = [];
$debugInfo = [];

if($isLoggedIn && isset($_SESSION['user_id'])){
    $debugInfo['user_logged_in'] = true;
    $debugInfo['user_id'] = $_SESSION['user_id'];
    
    try{
        $recommendedProducts = getRecommendedProductsForUser((int)$_SESSION['user_id'], 8);
        $debugInfo['products_count'] = count($recommendedProducts);
        $debugInfo['has_recommendations'] = !empty($recommendedProducts);
    }catch(\Exception $e){
        $debugInfo['error'] = $e->getMessage();
        error_log('Error al obtener las recomendaciones: ' . $e->getMessage());
    }
} else {
    $debugInfo['user_logged_in'] = false;
}

// TEMPORARY: Show debug info in development
// Remove this after debugging
if (true) { // Set to false in production
    echo "<!-- DEBUG INFO: ";
    print_r($debugInfo);
    if (!empty($recommendedProducts)) {
        echo "\nFirst Product: ";
        print_r($recommendedProducts[0]);
    }
    echo " -->";
}

try {
    $pdo = getPDO();
    $stmt = $pdo->query("
        SELECT 
            promo.*,
            p.nombre as producto_nombre,
            p.foto as producto_imagen,
            p.precio as producto_precio,
            c.nombre_categoria as categoria_nombre
        FROM promociones promo
        LEFT JOIN producto p ON promo.id_producto_asociado = p.id_producto
        LEFT JOIN producto_categoria c ON promo.id_categoria_asociada = c.id_categoria
        WHERE promo.activa = TRUE
          AND NOW() BETWEEN promo.fecha_inicio AND promo.fecha_final
        ORDER BY promo.fecha_inicio DESC
        LIMIT 10
    ");
    $activePromotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Error fetching promotions: ' . $e->getMessage());
}
?>

<main class="container">
    <!-- Hero Section -->
    <section id="inicio" class="hero section">
        <div class="hero-content">
            <p class="eyebrow">Sabor artesanal</p>
            <h1>El sabor de la tradición mexicana</h1>
            <p class="lead">Dulces de leche elaborados con recetas familiares y los mejores ingredientes de nuestra tierra.</p>
            <div class="hero-actions">
                <button class="primary" type="button" onclick="location.href='catalogo.php'">Conoce nuestros productos</button>
                <span class="hero-subheadline">Descubre nuestros dulces artesanales.</span>
            </div>
        </div>
        <div class="hero-showcase">
            <div class="hero-carousel" aria-label="Productos destacados">
                <button class="carousel-control prev" type="button" aria-label="Producto anterior">
                    <span aria-hidden="true">&#10094;</span>
                </button>
                <div class="carousel-window">
                    <ul class="carousel-track"></ul>
                </div>
                <button class="carousel-control next" type="button" aria-label="Producto siguiente">
                    <span aria-hidden="true">&#10095;</span>
                </button>
            </div>
            <div class="carousel-indicator" aria-hidden="true"></div>
            <p class="carousel-status" data-carousel-status role="status" aria-live="polite"></p>
        </div>
    </section>

    <!-- DEBUG: Always show this section to test styling -->
    <?php if ($isLoggedIn): ?>
        <?php if (empty($recommendedProducts)): ?>
            <!-- Show message when logged in but no recommendations -->
            <section id="recomendados" class="section" style="text-align: center; padding: 2rem;">
                <p style="color: var(--color-text-muted);">
                    📦 Aún no tenemos recomendaciones para ti. 
                    <br>
                    Realiza tu primera compra para obtener sugerencias personalizadas.
                </p>
            </section>
        <?php else: ?>
            <!-- Recommendations Carousel -->
            <section id="recomendados" class="section recommendations-section">
                <header class="section-header">
                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px;">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                        Recomendado para ti
                    </h2>
                    <p>Basado en tus compras anteriores, creemos que estos productos podrían gustarte.</p>
                </header>
                
                <div class="recommendations-carousel" aria-label="Productos recomendados">
                    <button class="carousel-control prev" type="button" data-rec-control="prev" aria-label="Ver productos anteriores">
                        <span aria-hidden="true">&#10094;</span>
                    </button>
                    
                    <div class="recommendations-viewport">
                        <div class="recommendations-track" id="recommendations-track">
                            <?php foreach ($recommendedProducts as $product): ?>
                                <article class="recommendation-card" data-product-id="<?php echo htmlspecialchars($product['id']); ?>">
                                    <div class="recommendation-image-wrapper">
                                        <?php 
                                        $imageUrl = $product['image'];
                                        if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                                            if (!str_starts_with($imageUrl, 'uploads/')) {
                                                $imageUrl = 'uploads/' . $imageUrl;
                                            }
                                        }
                                        // Fallback image if none exists
                                        if (empty($imageUrl)) {
                                            $imageUrl = 'https://images.unsplash.com/photo-1481391032119-d89fee407e44?auto=format&fit=crop&w=400&q=80';
                                        }
                                        ?>
                                        <img 
                                            src="<?php echo htmlspecialchars($imageUrl); ?>" 
                                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                                            loading="lazy"
                                            onerror="this.src='https://images.unsplash.com/photo-1481391032119-d89fee407e44?auto=format&fit=crop&w=400&q=80'"
                                        >
                                        
                                        <button 
                                            type="button" 
                                            class="quick-add-btn"
                                            data-product-id="<?php echo htmlspecialchars($product['id']); ?>"
                                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                            data-product-price="<?php echo htmlspecialchars($product['price']); ?>"
                                            data-product-image="<?php echo htmlspecialchars($imageUrl); ?>"
                                            aria-label="Agregar <?php echo htmlspecialchars($product['name']); ?> al carrito"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="9" cy="21" r="1"></circle>
                                                <circle cx="20" cy="21" r="1"></circle>
                                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                            </svg>
                                            Agregar
                                        </button>
                                    </div>
                                    
                                    <div class="recommendation-content">
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        
                                        <p class="recommendation-description">
                                            <?php 
                                            $description = $product['description'] ?? 'Delicioso dulce artesanal';
                                            if (strlen($description) > 60) {
                                                $description = substr($description, 0, 60) . '...';
                                            }
                                            echo htmlspecialchars($description); 
                                            ?>
                                        </p>
                                        
                                        <div class="recommendation-footer">
                                            <span class="recommendation-price">
                                                $<?php echo number_format($product['price'], 2); ?>
                                            </span>
                                            
                                            <a 
                                                href="vista_producto.php?id_producto=<?php echo htmlspecialchars($product['id']); ?>" 
                                                class="recommendation-link"
                                            >
                                                Ver detalle
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button class="carousel-control next" type="button" data-rec-control="next" aria-label="Ver más productos">
                        <span aria-hidden="true">&#10095;</span>
                    </button>
                </div>
                
                <div class="recommendations-indicators" id="rec-indicators" aria-hidden="true"></div>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Promotional Banners Section - Only show if there are active promotions -->
    <?php if (!empty($activePromotions)): ?>
<section id="promociones" class="section promotions-section">
    <header class="section-header">
        <h2>
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px;">
                <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                <path d="M9 12h6"/>
                <path d="M12 9v6"/>
                <path d="m16 16 2 2"/>
                <path d="m16 8 2-2"/>
                <path d="M8 8 6 6"/>
                <path d="m8 16-2 2"/>
            </svg>
            ¡Ofertas Especiales!
        </h2>
        <p>Aprovecha nuestras promociones por tiempo limitado</p>
    </header>

    <div class="promotions-slider" data-promo-slider>
        <button class="promo-control prev" data-promo-prev aria-label="Promoción anterior">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
        </button>

        <div class="promotions-viewport">
            <div class="promotions-track" id="promotions-track">
                <?php foreach ($activePromotions as $promo): ?>
                    <div class="promo-banner" data-promo-id="<?php echo htmlspecialchars($promo['id_promocion']); ?>">
                        <div class="promo-image-container">
                            <?php 
                            // Determine image source (promotion image or product image)
                            $promoImage = $promo['imagen_url'] ?? $promo['producto_imagen'] ?? null;
                            if ($promoImage && !filter_var($promoImage, FILTER_VALIDATE_URL)) {
                                if (!str_starts_with($promoImage, 'uploads/')) {
                                    $promoImage = 'uploads/' . $promoImage;
                                }
                            }
                            // Fallback image
                            if (empty($promoImage)) {
                                $promoImage = 'https://images.unsplash.com/photo-1607478900766-efe13248b125?auto=format&fit=crop&w=800&q=80';
                            }
                            ?>
                            <img 
                                src="<?php echo htmlspecialchars($promoImage); ?>" 
                                alt="<?php echo htmlspecialchars($promo['nombre_promo']); ?>"
                                loading="lazy"
                                onerror="this.src='https://images.unsplash.com/photo-1607478900766-efe13248b125?auto=format&fit=crop&w=800&q=80'"
                            >
                            
                            <!-- Discount Badge - UPDATED to handle both types -->
                            <div class="promo-badge">
                                <?php 
                                $discountValue = $promo['valor_descuento'];
                                $discountType = $promo['tipo_descuento'];
                                $productPrice = $promo['producto_precio'] ?? 0;
                                
                                if ($discountType === 'porcentaje') {
                                    // Percentage discount
                                    echo "-" . number_format($discountValue, 0) . "%";
                                } else {
                                    // Fixed amount discount
                                    // If we have a product price, show as percentage
                                    if ($productPrice > 0 && $discountValue < $productPrice) {
                                        $percentage = round(($discountValue / $productPrice) * 100);
                                        echo "-{$percentage}%";
                                    } else {
                                        // Just show the fixed discount
                                        echo "-$" . number_format($discountValue, 0);
                                    }
                                }
                                ?>
                            </div>
                        </div>

                        <div class="promo-content">
                            <div class="promo-header">
                                <span class="promo-category">
                                    <?php 
                                    if ($promo['producto_nombre']) {
                                        echo htmlspecialchars($promo['producto_nombre']);
                                    } elseif ($promo['categoria_nombre']) {
                                        echo 'Categoría: ' . htmlspecialchars($promo['categoria_nombre']);
                                    } else {
                                        echo 'Promoción General';
                                    }
                                    ?>
                                </span>
                                
                                <h3><?php echo htmlspecialchars($promo['nombre_promo']); ?></h3>
                            </div>

                            <p class="promo-description">
                                <?php 
                                $desc = $promo['descripcion'] ?? 'Oferta especial por tiempo limitado';
                                echo htmlspecialchars($desc);
                                ?>
                            </p>

                            <!-- Pricing Section - UPDATED to handle both types -->
                            <div class="promo-pricing">
                                <?php if ($promo['producto_precio'] && $promo['id_producto_asociado']): ?>
                                    <div class="price-container">
                                        <span class="original-price">$<?php echo number_format($promo['producto_precio'], 2); ?></span>
                                        <span class="discounted-price">
                                            <?php 
                                            if ($discountType === 'porcentaje') {
                                                // Calculate price after percentage discount
                                                $finalPrice = $promo['producto_precio'] * (1 - ($discountValue / 100));
                                                echo '$' . number_format($finalPrice, 2);
                                            } else {
                                                // Subtract fixed discount
                                                $finalPrice = $promo['producto_precio'] - $discountValue;
                                                echo '$' . number_format($finalPrice, 2);
                                            }
                                            ?>
                                        </span>
                                        <span class="savings-tag">
                                            <?php 
                                            if ($discountType === 'porcentaje') {
                                                echo "Ahorra " . number_format($discountValue, 0) . "%";
                                            } else {
                                                echo "Ahorra $" . number_format($discountValue, 2);
                                            }
                                            ?>
                                        </span>
                                    </div>
                                <?php elseif ($promo['id_categoria_asociada']): ?>
                                    <!-- Category-wide discount -->
                                    <div class="discount-tag category-discount">
                                        <?php 
                                        if ($discountType === 'porcentaje') {
                                            echo "🏷️ " . number_format($discountValue, 0) . "% de descuento en toda la categoría";
                                        } else {
                                            echo "🏷️ $" . number_format($discountValue, 2) . " de descuento en productos seleccionados";
                                        }
                                        ?>
                                    </div>
                                <?php else: ?>
                                    <!-- General promotion -->
                                    <div class="discount-tag general-discount">
                                        <?php 
                                        if ($discountType === 'porcentaje') {
                                            echo "🎉 " . number_format($discountValue, 0) . "% de descuento";
                                        } else {
                                            echo "🎉 Descuento de $" . number_format($discountValue, 2);
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="promo-actions">
                                <?php if ($promo['id_producto_asociado']): ?>
                                    <a href="vista_producto.php?id_producto=<?php echo htmlspecialchars($promo['id_producto_asociado']); ?>" 
                                       class="promo-btn primary">
                                        Ver Producto
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                <?php elseif ($promo['id_categoria_asociada']): ?>
                                    <a href="catalogo.php?categoria=<?php echo htmlspecialchars($promo['id_categoria_asociada']); ?>" 
                                       class="promo-btn primary">
                                        Ver Categoría
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                <?php else: ?>
                                    <a href="catalogo.php" class="promo-btn primary">
                                        Ver Catálogo
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>

                                <!-- Countdown Timer -->
                                <span class="promo-timer" data-end-date="<?php echo htmlspecialchars($promo['fecha_final']); ?>">
                                    Termina pronto
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>

            <button class="promo-control next" data-promo-next aria-label="Siguiente promoción">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            </button>
        </div>

        <!-- Slider Indicators -->
        <div class="promo-indicators" id="promo-indicators"></div>

        <!-- Auto-play controls (optional) -->
        <div class="promo-controls-extra">
            <button type="button" class="promo-autoplay-toggle" id="promo-autoplay" aria-label="Pausar/Reproducir">
                <svg class="play-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M8 5v14l11-7z"/>
                </svg>
                <svg class="pause-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
                    <path d="M6 4h4v16H6zM14 4h4v16h-4z"/>
                </svg>
            </button>
        </div>
    </section>
    <?php endif; ?>

    <!-- Rest of sections remain the same... -->
    <section id="valores" class="section values">
        <header class="section-header">
            <h2>Valores que nos definen</h2>
            <p>Creemos en hacer las cosas con corazón, cuidando a nuestra gente, nuestro entorno y tu paladar.</p>
        </header>
        <div class="values-grid">
            <article class="value-card">
                <span class="value-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M12 4.2l8 6.2-1.2 1.6-1.3-1v7.8h-4.5v-4.5h-3v4.5H5.5V10.9l-1.3 1-1.2-1.6 9-6.1z" />
                    </svg>
                </span>
                <h3>Tradición</h3>
                <p>Recetas heredadas que mantienen viva la esencia de los dulces mexicanos.</p>
            </article>
            <article class="value-card">
                <span class="value-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M12 4l2.1 4.9 5.4.4-4.1 3.5L16 18l-4-2.6L8 18l.6-5.2-4.1-3.5 5.4-.4z" />
                    </svg>
                </span>
                <h3>Calidad</h3>
                <p>Ingredientes seleccionados y procesos cuidadosos para un sabor incomparable.</p>
            </article>
            <article class="value-card">
                <span class="value-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M20 12.5a5.4 5.4 0 00-.1-1.2l2-1.2-1-1.8-2.2.5a5.6 5.6 0 00-1.7-1l-.3-2.3h-2l-.3 2.3a5.6 5.6 0 00-1.7 1l-2.2-.5-1 1.8 2 1.2a5.4 5.4 0 000 2.4l-2 1.2 1 1.8 2.2-.5a5.6 5.6 0 001.7 1l.3 2.3h2l.3-2.3a5.6 5.6 0 001.7-1l2.2.5 1-1.8-2-1.2a5.4 5.4 0 00.1-1.2zm-8 0a2.5 2.5 0 112.5 2.5 2.5 2.5 0 01-2.5-2.5z" />
                    </svg>
                </span>
                <h3>Innovación</h3>
                <p>Sabores contemporáneos que reinventan lo clásico sin perder lo auténtico.</p>
            </article>
            <article class="value-card">
                <span class="value-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M17.5 4.5c-3 1.7-5 4.5-5.5 8.1-1-1.8-2.6-3-4.8-3.6-.4 5.3 3 8.4 5.2 9.9.7.5 1.6.5 2.3-.1 2.5-2.1 4.4-5.9 2.8-10.5zM8 4.5a5.5 5.5 0 00-5.5 5.5c0 3.1 2.6 5.2 6 5.4-2.1-1.6-3.2-3.7-3-6.3 1.9.6 3.2 1.8 4.1 3.4.2-4-2.3-6.7-4.1-7z" />
                    </svg>
                </span>
                <h3>Responsabilidad</h3>
                <p>Compromiso con proveedores locales y prácticas sustentables.</p>
            </article>
        </div>
    </section>

    <section id="testimonios" class="section testimonials">
        <header class="section-header">
            <h2>Lo que dicen nuestros clientes</h2>
            <p>Testimonios que nos impulsan a seguir creando dulces momentos.</p>
        </header>
        <div class="testimonial-list" aria-live="polite"></div>
    </section>

    <section id="contacto" class="section contact">
        <header class="section-header">
            <h2>Contacto</h2>
            <p>¿Tienes dudas o deseas una cotización especial? Escríbenos, será un placer atenderte.</p>
        </header>
        <div class="contact-grid">
            <div class="contact-info">
                <h3>Visítanos</h3>
                <p>Av. de la Dulzura 123, Col. Centro<br>Michoacán, México</p>
                <h3>Llámanos</h3>
                <p><a href="tel:+523511234567">+52 351 123 4567</a></p>
                <h3>Correo</h3>
                <p><a href="mailto:LaSevillanas@gmail.com">LaSevillanas@gmail.com</a></p>
            </div>

            <form class="contact-form" id="contact-form" aria-label="Formulario de contacto" method="post">
                <div class="form-field">
                    <label for="nombre">Nombre</label>
                    <input id="nombre" name="nombre" type="text" placeholder="Tu nombre" required />
                </div>
                <div class="form-field">
                    <label for="correo">Correo electrónico</label>
                    <input id="correo" name="correo" type="email" placeholder="tunombre@email.com" required />
                </div>
                <div class="form-field">
                    <label for="mensaje">Mensaje</label>
                    <textarea id="mensaje" name="mensaje" rows="4" placeholder="¿Cómo podemos ayudarte?" required></textarea>
                </div>
        
                <button class="primary" type="submit" id="contact-submit-btn">Enviar mensaje</button>

                <div id="contact-feedback" style="margin-top: 15px; text-align: center; font-weight: 500;"></div>
            </form>
        </div>
    </section>

    <section id="search-results-section" class="section" style="display: none; min-height: 80vh;">
        <header class="section-header" style="flex-direction: row; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <h2 id="search-results-title" style="margin: 0;">Resultados de búsqueda</h2>
            <button type="button" id="close-search-btn" class="secondary" style="max-width: 200px; margin: 0;">
                &larr; Volver a la portada
            </button>
        </header>
        <div class="product-list" data-view="grid" id="search-results-list">
            </div>
    </section>

    <script>
        window.RECAPTCHA_SITE_KEY = "<?php echo htmlspecialchars($_ENV['RECAPTCHA_SITE_KEY']); ?>";
    </script>
    <script src="./js/script.js"></script>
    <script src="./js/recommendations.js"></script>
    <script src="./js/promotions.js"></script>
</main>

<?php require_once __DIR__ . '/templates/footer.php'; ?>