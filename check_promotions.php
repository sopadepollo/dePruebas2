<?php
// check_promotions.php - Quick database check
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/db.php';

use function App\Lib\getPDO;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Check Promotions</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .section { background: #000; border: 2px solid #0f0; padding: 20px; margin: 20px 0; }
        h2 { color: #0ff; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background: #1a1a1a; color: #0ff; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
    </style>
</head>
<body>
    <h1>Promotions Database Check</h1>

    <?php
    try {
        $pdo = getPDO();
        
        // 1. Check all promotions
        echo '<div class="section">';
        echo '<h2>1. All Promotions in Database</h2>';
        
        $stmt = $pdo->query("SELECT * FROM promociones ORDER BY fecha_inicio DESC");
        $allPromos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<p class="success">Total promotions: ' . count($allPromos) . '</p>';
        
        if (empty($allPromos)) {
            echo '<p class="error">No promotions found in database!</p>';
        } else {
            echo '<table>';
            echo '<tr>';
            echo '<th>ID</th><th>Name</th><th>Activa</th><th>Inicio</th><th>Final</th>';
            echo '<th>Producto</th><th>Categoría</th><th>Imagen</th>';
            echo '</tr>';
            
            foreach ($allPromos as $promo) {
                echo '<tr>';
                echo '<td>' . $promo['id_promocion'] . '</td>';
                echo '<td>' . htmlspecialchars($promo['nombre_promo']) . '</td>';
                
                $activaClass = $promo['activa'] ? 'success' : 'error';
                echo '<td class="' . $activaClass . '">' . ($promo['activa'] ? 'TRUE' : 'FALSE') . '</td>';
                
                echo '<td>' . $promo['fecha_inicio'] . '</td>';
                echo '<td>' . $promo['fecha_final'] . '</td>';
                echo '<td>' . ($promo['id_producto_asociado'] ?? 'NULL') . '</td>';
                echo '<td>' . ($promo['id_categoria_asociada'] ?? 'NULL') . '</td>';
                echo '<td>' . ($promo['imagen_url'] ? '✓ Has image' : '✗ No image') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';
        
        // 2. Check active promotions (the query used in homepage)
        echo '<div class="section">';
        echo '<h2>2. Active Promotions (Used on Homepage)</h2>';
        echo '<p>Query: <code>WHERE activa = TRUE AND NOW() BETWEEN fecha_inicio AND fecha_final</code></p>';
        
        $stmt = $pdo->query("
            SELECT
                promo.id_promocion,
                promo.nombre_promo,
                promo.descripcion,
                promo.valor_descuento,
                promo.imagen_url,
                promo.id_producto_asociado,
                promo.id_categoria_asociada,
                promo.fecha_inicio,
                promo.fecha_final,
                promo.activa,
                p.nombre as producto_nombre,
                p.precio as producto_precio,
                c.nombre_categoria as categoria_nombre
            FROM promociones promo
            LEFT JOIN producto p ON promo.id_producto_asociado = p.id_producto
            LEFT JOIN producto_categoria c ON promo.id_categoria_asociada = c.id_categoria
            WHERE promo.activa = TRUE
              AND NOW() BETWEEN promo.fecha_inicio AND promo.fecha_final
            ORDER BY promo.fecha_inicio DESC
        ");
        $activePromos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<p>Current DateTime (NOW()): <strong>' . date('Y-m-d H:i:s') . '</strong></p>';
        
        if (empty($activePromos)) {
            echo '<p class="error">No active promotions found!</p>';
            echo '<p class="warning">Possible reasons:</p>';
            echo '<ul>';
            echo '<li>activa = FALSE</li>';
            echo '<li>fecha_inicio is in the future</li>';
            echo '<li>fecha_final is in the past</li>';
            echo '</ul>';
        } else {
            echo '<p class="success">✓ Found ' . count($activePromos) . ' active promotion(s)</p>';
            echo '<table>';
            echo '<tr>';
            echo '<th>ID</th><th>Name</th><th>Description</th><th>Discount</th>';
            echo '<th>Product</th><th>Category</th><th>Image</th>';
            echo '</tr>';
            
            foreach ($activePromos as $promo) {
                echo '<tr>';
                echo '<td>' . $promo['id_promocion'] . '</td>';
                echo '<td>' . htmlspecialchars($promo['nombre_promo']) . '</td>';
                echo '<td>' . htmlspecialchars(substr($promo['descripcion'], 0, 50)) . '...</td>';
                echo '<td>$' . number_format($promo['valor_descuento'], 2) . '</td>';
                echo '<td>' . ($promo['producto_nombre'] ?? 'N/A') . '</td>';
                echo '<td>' . ($promo['categoria_nombre'] ?? 'N/A') . '</td>';
                
                $imageStatus = $promo['imagen_url'] ? '✓ ' . $promo['imagen_url'] : '✗ No image';
                echo '<td>' . htmlspecialchars($imageStatus) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';
        
        // 3. Date range check
        echo '<div class="section">';
        echo '<h2>3. Date Range Analysis</h2>';
        
        $stmt = $pdo->query("
            SELECT 
                id_promocion,
                nombre_promo,
                fecha_inicio,
                fecha_final,
                CASE 
                    WHEN NOW() < fecha_inicio THEN 'FUTURE'
                    WHEN NOW() > fecha_final THEN 'EXPIRED'
                    ELSE 'ACTIVE'
                END as status
            FROM promociones
            WHERE activa = TRUE
            ORDER BY fecha_inicio DESC
        ");
        $dateCheck = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<table>';
        echo '<tr><th>ID</th><th>Name</th><th>Start</th><th>End</th><th>Status</th></tr>';
        
        foreach ($dateCheck as $promo) {
            $statusClass = '';
            switch($promo['status']) {
                case 'ACTIVE': $statusClass = 'success'; break;
                case 'FUTURE': $statusClass = 'warning'; break;
                case 'EXPIRED': $statusClass = 'error'; break;
            }
            
            echo '<tr>';
            echo '<td>' . $promo['id_promocion'] . '</td>';
            echo '<td>' . htmlspecialchars($promo['nombre_promo']) . '</td>';
            echo '<td>' . $promo['fecha_inicio'] . '</td>';
            echo '<td>' . $promo['fecha_final'] . '</td>';
            echo '<td class="' . $statusClass . '">' . $promo['status'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';
        
        // 4. Quick fixes
        echo '<div class="section">';
        echo '<h2>4. Quick Fixes</h2>';
        
        if (empty($activePromos)) {
            echo '<h3>SQL Commands to Fix:</h3>';
            echo '<pre style="background: #0a0a0a; padding: 10px; border: 1px solid #333;">';
            echo '-- Update all promotions to be active now
                    UPDATE promociones 
                    SET fecha_inicio = NOW(), 
                    fecha_final = DATE_ADD(NOW(), INTERVAL 30 DAY),
                    activa = TRUE;

                -- Or create a new test promotion
                    INSERT INTO promociones (nombre_promo, descripcion, valor_descuento, imagen_url, fecha_inicio, fecha_final, activa) 
                    VALUES (
                        \'Test Promo\',
                        \'This is a test promotion\',
                        20.00,
                        \'uploads/test-promo.jpg\',
                        NOW(),
                        DATE_ADD(NOW(), INTERVAL 30 DAY),
                        TRUE
                    );';
            echo '</pre>';
        }
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="section">';
        echo '<p class="error">Database Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</div>';
    }
    ?>

    <p><a href="index.php" style="color: #0ff;">→ Go to Homepage</a></p>
</body>
</html>