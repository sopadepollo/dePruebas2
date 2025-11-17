<?php
// test_recommendations.php - Debugging page
// REMOVE THIS FILE IN PRODUCTION!

require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/storage.php';
require_once __DIR__ . '/lib/auth_usr.php';

use function App\Lib\startSecureSession;
use function App\Lib\isLoggedIn;
use function App\Lib\getPDO;
use function App\Lib\getRecommendedProductsForUser;

startSecureSession();

$isLoggedIn = isLoggedIn();
$userId = $_SESSION['user_id'] ?? null;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Recommendations</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #1a1a1a;
            color: #00ff00;
        }
        .section {
            background: #000;
            border: 1px solid #00ff00;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        h2 {
            color: #00ffff;
            border-bottom: 2px solid #00ff00;
            padding-bottom: 10px;
        }
        pre {
            background: #0a0a0a;
            border: 1px solid #333;
            padding: 10px;
            overflow-x: auto;
        }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffff00; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #1a1a1a;
            color: #00ffff;
        }
    </style>
</head>
<body>
    <h1>🔍 Recommendations System Debug</h1>
    
    <!-- Session Info -->
    <div class="section">
        <h2>1. Session Status</h2>
        <?php if ($isLoggedIn): ?>
            <p class="success">✓ User is logged in</p>
            <p>User ID: <strong><?php echo htmlspecialchars($userId); ?></strong></p>
        <?php else: ?>
            <p class="error">✗ User is NOT logged in</p>
            <p class="warning">⚠ You need to be logged in to see recommendations</p>
            <p><a href="users/login.php" style="color: #00ff00;">→ Go to login</a></p>
        <?php endif; ?>
    </div>

    <?php if ($isLoggedIn && $userId): ?>
        
        <!-- Check User's Orders -->
        <div class="section">
            <h2>2. User's Purchase History</h2>
            <?php
            try {
                $pdo = getPDO();
                
                // Check completed orders
                $stmt = $pdo->prepare("
                    SELECT 
                        pe.id_pedido,
                        pe.fecha_compra,
                        pe.pago_completado,
                        COUNT(pi.id_pedido_item) as total_items,
                        SUM(pi.cantidad) as total_quantity
                    FROM pedido pe
                    LEFT JOIN pedido_item pi ON pe.id_pedido = pi.id_pedido
                    WHERE pe.id_usuario = :userId
                    GROUP BY pe.id_pedido
                    ORDER BY pe.fecha_compra DESC
                ");
                $stmt->execute([':userId' => $userId]);
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($orders)) {
                    echo '<p class="warning">⚠ No orders found for this user</p>';
                    echo '<p>The user needs to have at least one completed order to get recommendations.</p>';
                } else {
                    echo '<p class="success">✓ Found ' . count($orders) . ' order(s)</p>';
                    echo '<table>';
                    echo '<tr><th>Order ID</th><th>Date</th><th>Completed</th><th>Items</th><th>Quantity</th></tr>';
                    foreach ($orders as $order) {
                        $completed = $order['pago_completado'] ? '✓ Yes' : '✗ No';
                        $class = $order['pago_completado'] ? 'success' : 'error';
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($order['id_pedido']) . '</td>';
                        echo '<td>' . htmlspecialchars($order['fecha_compra']) . '</td>';
                        echo '<td class="' . $class . '">' . $completed . '</td>';
                        echo '<td>' . htmlspecialchars($order['total_items']) . '</td>';
                        echo '<td>' . htmlspecialchars($order['total_quantity']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            } catch (Exception $e) {
                echo '<p class="error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>

        <!-- Check Purchased Products -->
        <div class="section">
            <h2>3. Purchased Products & Categories</h2>
            <?php
            try {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT
                        p.id_producto,
                        p.nombre,
                        p.id_categoria,
                        pc.nombre_categoria
                    FROM pedido pe
                    JOIN pedido_item pi ON pe.id_pedido = pi.id_pedido
                    JOIN producto p ON pi.id_producto = p.id_producto
                    LEFT JOIN producto_categoria pc ON p.id_categoria = pc.id_categoria
                    WHERE pe.id_usuario = :userId
                      AND pe.pago_completado = TRUE
                    ORDER BY pc.nombre_categoria, p.nombre
                ");
                $stmt->execute([':userId' => $userId]);
                $purchased = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($purchased)) {
                    echo '<p class="warning">⚠ No completed purchases found</p>';
                } else {
                    echo '<p class="success">✓ User has purchased ' . count($purchased) . ' different product(s)</p>';
                    echo '<table>';
                    echo '<tr><th>Product ID</th><th>Name</th><th>Category ID</th><th>Category Name</th></tr>';
                    foreach ($purchased as $item) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($item['id_producto']) . '</td>';
                        echo '<td>' . htmlspecialchars($item['nombre']) . '</td>';
                        echo '<td>' . htmlspecialchars($item['id_categoria'] ?? 'NULL') . '</td>';
                        echo '<td>' . htmlspecialchars($item['nombre_categoria'] ?? 'N/A') . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            } catch (Exception $e) {
                echo '<p class="error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>

        <!-- Test Recommendation Function -->
        <div class="section">
            <h2>4. Recommendation Query Test</h2>
            <?php
            try {
                $recommendations = getRecommendedProductsForUser((int)$userId, 8);
                
                if (empty($recommendations)) {
                    echo '<p class="warning">⚠ No recommendations found</p>';
                    echo '<h3>Possible reasons:</h3>';
                    echo '<ul>';
                    echo '<li>User has no completed orders (pago_completado = FALSE)</li>';
                    echo '<li>Products have no category assigned (id_categoria IS NULL)</li>';
                    echo '<li>User has already bought all products in their categories</li>';
                    echo '<li>No other products exist in the purchased categories</li>';
                    echo '</ul>';
                    
                    // Debug: Show raw SQL results
                    echo '<h3>Debug: Check available products in user categories</h3>';
                    $stmt = $pdo->prepare("
                        SELECT 
                            p.id_producto,
                            p.nombre,
                            p.id_categoria,
                            pc.nombre_categoria,
                            p.stock,
                            p.precio
                        FROM producto p
                        LEFT JOIN producto_categoria pc ON p.id_categoria = pc.id_categoria
                        WHERE p.id_categoria IN (
                            SELECT DISTINCT p_cat.id_categoria
                            FROM pedido pe_cat
                            JOIN pedido_item pi_cat ON pe_cat.id_pedido = pi_cat.id_pedido
                            JOIN producto p_cat ON pi_cat.id_producto = p_cat.id_producto
                            WHERE pe_cat.id_usuario = :userId
                              AND pe_cat.pago_completado = TRUE
                              AND p_cat.id_categoria IS NOT NULL
                        )
                        AND p.id_producto NOT IN (
                            SELECT DISTINCT pi_ex.id_producto
                            FROM pedido_item pi_ex
                            JOIN pedido pe_ex ON pi_ex.id_pedido = pe_ex.id_pedido
                            WHERE pe_ex.id_usuario = :userId
                        )
                        LIMIT 10
                    ");
                    $stmt->execute([':userId' => $userId]);
                    $available = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($available)) {
                        echo '<p class="error">No available products found in user\'s categories</p>';
                    } else {
                        echo '<p class="success">Found ' . count($available) . ' available product(s):</p>';
                        echo '<table>';
                        echo '<tr><th>ID</th><th>Name</th><th>Category</th><th>Stock</th><th>Price</th></tr>';
                        foreach ($available as $prod) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($prod['id_producto']) . '</td>';
                            echo '<td>' . htmlspecialchars($prod['nombre']) . '</td>';
                            echo '<td>' . htmlspecialchars($prod['nombre_categoria'] ?? 'N/A') . '</td>';
                            echo '<td>' . htmlspecialchars($prod['stock']) . '</td>';
                            echo '<td>$' . htmlspecialchars($prod['precio']) . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                    
                } else {
                    echo '<p class="success">✓ Found ' . count($recommendations) . ' recommendation(s)!</p>';
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Name</th><th>Price</th><th>Image</th><th>Description</th></tr>';
                    foreach ($recommendations as $rec) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($rec['id']) . '</td>';
                        echo '<td>' . htmlspecialchars($rec['name']) . '</td>';
                        echo '<td>$' . number_format($rec['price'], 2) . '</td>';
                        echo '<td>' . (empty($rec['image']) ? 'No image' : '✓ Has image') . '</td>';
                        echo '<td>' . htmlspecialchars(substr($rec['description'], 0, 50)) . '...</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                    
                    echo '<p class="success">→ Recommendations should appear on homepage!</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            }
            ?>
        </div>

        <!-- Check All Products -->
        <div class="section">
            <h2>5. All Products in Database</h2>
            <?php
            try {
                $stmt = $pdo->query("
                    SELECT 
                        p.id_producto,
                        p.nombre,
                        p.id_categoria,
                        pc.nombre_categoria,
                        p.stock
                    FROM producto p
                    LEFT JOIN producto_categoria pc ON p.id_categoria = pc.id_categoria
                    ORDER BY p.id_categoria, p.nombre
                    LIMIT 20
                ");
                $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<p>Showing first 20 products:</p>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Name</th><th>Category</th><th>Stock</th></tr>';
                foreach ($allProducts as $prod) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($prod['id_producto']) . '</td>';
                    echo '<td>' . htmlspecialchars($prod['nombre']) . '</td>';
                    echo '<td>' . htmlspecialchars($prod['nombre_categoria'] ?? 'NO CATEGORY') . '</td>';
                    echo '<td>' . htmlspecialchars($prod['stock']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } catch (Exception $e) {
                echo '<p class="error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>

    <?php endif; ?>

    <div class="section">
        <h2>6. Quick Fixes</h2>
        <h3>If no recommendations appear:</h3>
        <ol>
            <li><strong>Ensure user is logged in</strong> - Check session above</li>
            <li><strong>Create a test order</strong> - User needs at least one completed purchase</li>
            <li><strong>Assign categories to products</strong> - Products without category won't appear</li>
            <li><strong>Mark order as completed</strong> - Set pago_completado = TRUE in database</li>
            <li><strong>Check CSS is loaded</strong> - Add recommendations CSS to styles.css</li>
            <li><strong>Check JS is loaded</strong> - Create js/recommendations.js file</li>
        </ol>
        
        <h3>SQL to manually create test data:</h3>
        <pre>-- Mark an existing order as completed
UPDATE pedido SET pago_completado = TRUE WHERE id_usuario = <?php echo $userId ?? 'YOUR_USER_ID'; ?> LIMIT 1;

-- Assign categories to products (if missing)
UPDATE producto SET id_categoria = 1 WHERE id_categoria IS NULL LIMIT 5;</pre>
    </div>

    <p><a href="index.php" style="color: #00ffff;">← Back to Homepage</a></p>
</body>
</html>