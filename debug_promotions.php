<?php
/**
 * DEBUG PAGE - Promotions System Diagnostic
 * Access this page to see if promotions are working correctly
 */

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/storage.php';

use function App\Lib\getPDO;
use function App\Lib\readProducts;
use function App\Lib\applyPromotions;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Promotions System</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }
        .section {
            background: #252526;
            border: 1px solid #3e3e42;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        h1 { color: #4ec9b0; margin-top: 0; }
        h2 { color: #569cd6; border-bottom: 2px solid #569cd6; padding-bottom: 10px; }
        h3 { color: #ce9178; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        .info { color: #9cdcfe; }
        pre {
            background: #1e1e1e;
            border: 1px solid #3e3e42;
            border-radius: 4px;
            padding: 15px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #3e3e42;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #2d2d30;
            color: #4ec9b0;
        }
        tr:nth-child(even) {
            background: #2d2d30;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .badge.active { background: #4ec9b0; color: #000; }
        .badge.expired { background: #f48771; color: #000; }
        .badge.pending { background: #dcdcaa; color: #000; }
    </style>
</head>
<body>
    <h1>🔍 Promotions System Diagnostic</h1>
    <p class="info">Current Time: <?php echo date('Y-m-d H:i:s'); ?></p>

    <!-- CHECK 1: Active Promotions -->
    <div class="section">
        <h2>1. Active Promotions in Database</h2>
        <?php
        try {
            $pdo = getPDO();
            $stmt = $pdo->query("
                SELECT 
                    promo.id_promocion,
                    promo.nombre_promo,
                    promo.descripcion,
                    promo.valor_descuento,
                    promo.tipo_descuento,
                    promo.id_producto_asociado,
                    promo.id_categoria_asociada,
                    promo.activa,
                    promo.fecha_inicio,
                    promo.fecha_final,
                    p.nombre as producto_nombre,
                    c.nombre_categoria as categoria_nombre,
                    CASE 
                        WHEN NOW() BETWEEN promo.fecha_inicio AND promo.fecha_final THEN 'ACTIVE'
                        WHEN NOW() < promo.fecha_inicio THEN 'PENDING'
                        WHEN NOW() > promo.fecha_final THEN 'EXPIRED'
                    END as status
                FROM promociones promo
                LEFT JOIN producto p ON promo.id_producto_asociado = p.id_producto
                LEFT JOIN producto_categoria c ON promo.id_categoria_asociada = c.id_categoria
                WHERE promo.activa = TRUE
                ORDER BY promo.fecha_inicio DESC
            ");
            
            $promos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($promos)) {
                echo '<p class="warning">⚠️ No active promotions found in database!</p>';
            } else {
                echo '<p class="success">✅ Found ' . count($promos) . ' active promotion(s)</p>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Name</th><th>Type</th><th>Discount</th><th>Target</th><th>Dates</th><th>Status</th></tr>';
                
                foreach ($promos as $promo) {
                    $statusClass = strtolower($promo['status']);
                    $target = $promo['producto_nombre'] 
                        ? "Product: " . $promo['producto_nombre'] 
                        : ($promo['categoria_nombre'] ? "Category: " . $promo['categoria_nombre'] : "General");
                    
                    $discount = $promo['tipo_descuento'] === 'porcentaje' 
                        ? $promo['valor_descuento'] . '%' 
                        : '$' . $promo['valor_descuento'];
                    
                    echo "<tr>";
                    echo "<td>{$promo['id_promocion']}</td>";
                    echo "<td>{$promo['nombre_promo']}</td>";
                    echo "<td>{$promo['tipo_descuento']}</td>";
                    echo "<td><strong>{$discount}</strong></td>";
                    echo "<td>{$target}</td>";
                    echo "<td>" . date('M d', strtotime($promo['fecha_inicio'])) . " - " . date('M d', strtotime($promo['fecha_final'])) . "</td>";
                    echo "<td><span class='badge {$statusClass}'>{$promo['status']}</span></td>";
                    echo "</tr>";
                }
                
                echo '</table>';
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>

    <!-- CHECK 2: Product Data -->
    <div class="section">
        <h2>2. Products from readProducts()</h2>
        <?php
        try {
            $products = readProducts();
            echo '<p class="info">Loaded ' . count($products) . ' products</p>';
            
            // Show first 3 products
            echo '<h3>Sample Products (first 3):</h3>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Name</th><th>Price</th><th>Category ID</th><th>Category Name</th></tr>';
            
            for ($i = 0; $i < min(3, count($products)); $i++) {
                $p = $products[$i];
                echo "<tr>";
                echo "<td>{$p['id']}</td>";
                echo "<td>{$p['name']}</td>";
                echo "<td>\${$p['price']}</td>";
                echo "<td>" . ($p['id_categoria'] ?? 'N/A') . "</td>";
                echo "<td>" . ($p['category_name'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            
            echo '</table>';
            
            // Check Product ID 5 specifically (from your promotion)
            $product5 = array_filter($products, fn($p) => $p['id'] == '5');
            if (!empty($product5)) {
                $product5 = array_values($product5)[0];
                echo '<h3>Product ID 5 (Target of promotion):</h3>';
                echo '<pre>' . json_encode($product5, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
            } else {
                echo '<p class="error">❌ Product ID 5 not found!</p>';
            }
            
        } catch (Exception $e) {
            echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>

    <!-- CHECK 3: applyPromotions() Result -->
    <div class="section">
        <h2>3. Products After applyPromotions()</h2>
        <?php
        try {
            $products = readProducts();
            $productsWithPromotions = applyPromotions($products);
            
            $promoCount = 0;
            $promoProducts = [];
            
            foreach ($productsWithPromotions as $p) {
                if (isset($p['has_promotion']) && $p['has_promotion']) {
                    $promoCount++;
                    $promoProducts[] = $p;
                }
            }
            
            if ($promoCount === 0) {
                echo '<p class="error">❌ No products have promotions applied!</p>';
                echo '<h3>Debugging Info:</h3>';
                
                // Show sample product to see structure
                echo '<p>Sample product structure:</p>';
                echo '<pre>' . json_encode($productsWithPromotions[0] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                
                // Check if Product 5 has promotion
                $product5 = array_filter($productsWithPromotions, fn($p) => $p['id'] == '5');
                if (!empty($product5)) {
                    $product5 = array_values($product5)[0];
                    echo '<h3>Product ID 5 after applyPromotions():</h3>';
                    echo '<pre>' . json_encode($product5, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                }
                
            } else {
                echo '<p class="success">✅ Found ' . $promoCount . ' product(s) with promotions!</p>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Name</th><th>Original Price</th><th>New Price</th><th>Discount</th></tr>';
                
                foreach ($promoProducts as $p) {
                    $discount = isset($p['discount_percentage']) ? $p['discount_percentage'] . '%' : 'N/A';
                    echo "<tr>";
                    echo "<td>{$p['id']}</td>";
                    echo "<td>{$p['name']}</td>";
                    echo "<td class='warning'>\$" . number_format($p['original_price'], 2) . "</td>";
                    echo "<td class='success'>\$" . number_format($p['price'], 2) . "</td>";
                    echo "<td><strong>{$discount}</strong></td>";
                    echo "</tr>";
                }
                
                echo '</table>';
                
                // Show full data structure
                echo '<h3>Full Data Structure of First Promo Product:</h3>';
                echo '<pre>' . json_encode($promoProducts[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
            }
            
        } catch (Exception $e) {
            echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        ?>
    </div>

    <!-- CHECK 4: JavaScript Data -->
    <div class="section">
        <h2>4. Data Passed to JavaScript</h2>
        <p>This is what the catalog page will receive:</p>
        <pre><?php
        $products = readProducts();
        $productsWithPromotions = applyPromotions($products);
        echo htmlspecialchars(json_encode(array_slice($productsWithPromotions, 0, 3), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        ?></pre>
    </div>

    <div class="section">
        <h2>✅ Next Steps</h2>
        <ol>
            <li>If promotions show in Section 1 but not Section 3, there's an issue with <code>applyPromotions()</code></li>
            <li>If Product ID 5 doesn't have <code>has_promotion: true</code>, check the date range and product ID matching</li>
            <li>Verify that <code>id_categoria</code> is being properly set on products</li>
            <li>Check PHP error logs for any warnings or errors</li>
        </ol>
    </div>
</body>
</html>