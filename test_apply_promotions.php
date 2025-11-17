<?php
/**
 * Isolated test to verify applyPromotions() works correctly
 * Access: http://localhost/.../test_apply_promotions.php
 */

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/storage.php';

use function App\Lib\readProducts;
use function App\Lib\applyPromotions;
use function App\Lib\getPDO;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test applyPromotions()</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
        }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #252526;
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
        pre {
            background: #252526;
            border: 1px solid #3e3e42;
            padding: 15px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🧪 Test applyPromotions() Function</h1>

    <h2>Step 1: Get Active Promotions from Database</h2>
    <?php
    try {
        $pdo = getPDO();
        $stmt = $pdo->query("
            SELECT 
                id_promocion, 
                nombre_promo,
                valor_descuento,
                tipo_descuento,
                id_producto_asociado,
                id_categoria_asociada,
                activa,
                NOW() BETWEEN fecha_inicio AND fecha_final as is_active_now
            FROM promociones
            WHERE activa = TRUE
        ");
        $promos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='success'>✅ Found " . count($promos) . " active promotions</p>";
        
        if (!empty($promos)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Discount</th><th>Target Product ID</th><th>Target Category ID</th><th>Active Now?</th></tr>";
            foreach ($promos as $p) {
                $activeNow = $p['is_active_now'] ? '✅ YES' : '❌ NO';
                echo "<tr>";
                echo "<td>{$p['id_promocion']}</td>";
                echo "<td>{$p['nombre_promo']}</td>";
                echo "<td>{$p['tipo_descuento']}</td>";
                echo "<td>{$p['valor_descuento']}</td>";
                echo "<td>" . ($p['id_producto_asociado'] ?: 'N/A') . "</td>";
                echo "<td>" . ($p['id_categoria_asociada'] ?: 'N/A') . "</td>";
                echo "<td>{$activeNow}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    }
    ?>

    <h2>Step 2: Get Products WITHOUT Promotions</h2>
    <?php
    try {
        $products = readProducts();
        echo "<p class='success'>✅ Loaded " . count($products) . " products</p>";
        
        // Show first 3 products
        echo "<h3>First 3 Products (raw, no promotions yet):</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Category ID</th><th>has_promotion?</th></tr>";
        
        for ($i = 0; $i < min(3, count($products)); $i++) {
            $p = $products[$i];
            echo "<tr>";
            echo "<td>{$p['id']}</td>";
            echo "<td>{$p['name']}</td>";
            echo "<td>\${$p['price']}</td>";
            echo "<td>" . ($p['id_categoria'] ?? 'N/A') . "</td>";
            echo "<td>" . (isset($p['has_promotion']) && $p['has_promotion'] ? 'YES' : 'NO') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    }
    ?>

    <h2>Step 3: Apply Promotions</h2>
    <?php
    try {
        $productsWithPromos = applyPromotions($products);
        
        $promoCount = 0;
        $promoProducts = [];
        foreach ($productsWithPromos as $p) {
            if (isset($p['has_promotion']) && $p['has_promotion']) {
                $promoCount++;
                $promoProducts[] = $p;
            }
        }
        
        if ($promoCount === 0) {
            echo "<p class='error'>❌ NO PROMOTIONS APPLIED!</p>";
            echo "<p class='warning'>This means applyPromotions() is not working correctly.</p>";
            
            // Debug: Check first product in detail
            echo "<h3>Debug: First Product After applyPromotions():</h3>";
            echo "<pre>" . json_encode($productsWithPromos[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            
        } else {
            echo "<p class='success'>✅ Successfully applied promotions to {$promoCount} products!</p>";
            
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Original Price</th><th>New Price</th><th>Discount</th></tr>";
            
            foreach ($promoProducts as $p) {
                echo "<tr>";
                echo "<td>{$p['id']}</td>";
                echo "<td>{$p['name']}</td>";
                echo "<td class='warning'>\$" . number_format($p['original_price'], 2) . "</td>";
                echo "<td class='success'>\$" . number_format($p['price'], 2) . "</td>";
                echo "<td>{$p['discount_percentage']}%</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<h3>Full Data Structure of First Promo Product:</h3>";
            echo "<pre>" . json_encode($promoProducts[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    ?>

    <h2>Step 4: Verify JSON Encoding</h2>
    <?php
    try {
        $json = json_encode($productsWithPromos, JSON_UNESCAPED_UNICODE);
        $jsonError = json_last_error();
        
        if ($jsonError !== JSON_ERROR_NONE) {
            echo "<p class='error'>❌ JSON encoding failed: " . json_last_error_msg() . "</p>";
        } else {
            echo "<p class='success'>✅ JSON encoding successful</p>";
            echo "<p>JSON size: " . strlen($json) . " bytes</p>";
            
            // Decode and check
            $decoded = json_decode($json, true);
            $decodedPromoCount = 0;
            foreach ($decoded as $p) {
                if (isset($p['has_promotion']) && $p['has_promotion']) {
                    $decodedPromoCount++;
                }
            }
            
            echo "<p>Products with promotions after JSON encode/decode: <strong>{$decodedPromoCount}</strong></p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    }
    ?>

    <h2>✅ Summary</h2>
    <ul>
        <li>Active promotions in database: <strong><?php echo count($promos ?? []); ?></strong></li>
        <li>Products loaded: <strong><?php echo count($products ?? []); ?></strong></li>
        <li>Products with promotions applied: <strong class="<?php echo $promoCount > 0 ? 'success' : 'error'; ?>"><?php echo $promoCount ?? 0; ?></strong></li>
    </ul>

    <?php if ($promoCount > 0): ?>
        <p class='success'>✅ applyPromotions() is working correctly!</p>
        <p>If catalog page still doesn't show promotions, the issue is in:</p>
        <ul>
            <li>1. How data is passed to JavaScript (check JSON encoding)</li>
            <li>2. JavaScript not reading the data correctly</li>
            <li>3. CSS not styling the badges</li>
        </ul>
    <?php else: ?>
        <p class='error'>❌ applyPromotions() is NOT working!</p>
        <p>Possible causes:</p>
        <ul>
            <li>1. Promotion dates don't match current time</li>
            <li>2. Product IDs don't match promotion targets</li>
            <li>3. Category IDs don't match promotion targets</li>
            <li>4. Logic error in applyPromotions() function</li>
        </ul>
    <?php endif; ?>
</body>
</html>