<?php
declare(strict_types=1);

namespace App\Lib;
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PDO;

/**
 * Construye el HTML del recibo.
 * Esta función será usada tanto para el email como para el PDF.
 */
function buildReceiptHtml(array $order, array $items) : string {
    // Iniciar un buffer de salida para "capturar" el HTML
    ob_start();
    ?>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 20px; color: #333; }
        .container { width: 100%; max-width: 700px; margin: auto; border: 1px solid #eee; border-radius: 8px; }
        .header { background: #C60969; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; }
        .content h1 { margin-top: 0; color: #C60969; }
        .order-total { margin-top: 20px; padding-top: 10px; border-top: 1px solid #eee; }
        .order-total p { display: flex; justify-content: space-between; margin: 5px 0; }
        .cart-item { display: block; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .cart-item strong { color: #333; }
        .footer { text-align: center; font-size: 0.8em; color: #888; padding: 20px; }
    </style>
    
    <div class="container">
        <div class="header">
            <h2>Las Sevillanas (No Oficial)</h2>
            <p>Recibo del Pedido #<?= htmlspecialchars((string) $order['id_pedido']) ?></p>
        </div>
        <div class="content">
            <h1>¡Gracias por tu compra, <?= htmlspecialchars($order['nom_cliente']) ?>!</h1>
            <p>Hemos recibido tu pedido, aquí tienes los detalles:</p>
            
            <h3>Resumen de Artículos</h3>
            <?php foreach ($items as $item): ?>
                <div class="cart-item">
                    <strong><?= htmlspecialchars($item['nombre_producto'])?></strong><br>Cantidad: <?= htmlspecialchars((string)$item['cantidad'])?> x $<?= number_format((float)$item['precio_unitario'], 2)?> c/u
                    <span style="float: right;"><strong>$<?= number_format((float)($item['precio_unitario'] * $item['cantidad']), 2) ?></strong></span>                </div>
            <?php endforeach; ?>

            <div class="order-total">
                <p><span>Subtotal:</span> <span>$<?= number_format((float)$order['precio_subtotal'], 2) ?></span></p>
                <p><span>Descuento:</span> <span>-$<?= number_format((float)$order['descuento_aplicado'], 2) ?></span></p>
                <p><strong>Total Pagado:</strong> <strong>$<?= number_format((float)$order['precio_total'], 2) ?></strong></p>
            </div>

            <hr>
            <h4>Enviado a:</h4>
            <address style="font-style: normal;">
                <strong><?= htmlspecialchars($order['nom_cliente']) ?></strong><br>
                <?= htmlspecialchars($order['direccion']) ?><br>
                CP: <?= htmlspecialchars($order['cod_post']) ?><br>
                Tel: <?= htmlspecialchars($order['num_cel']) ?>
            </address>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> Las Sevillanas No Oficial. Todos los derechos reservados.</p>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * Genera el PDF y devuelve el stream de datos binarios.
 */
function generateOrderPdfStream(string $htmlContent): string
{
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans'); 
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($htmlContent);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    return $dompdf->output();
}

// ... (Código existente de generateOrderReceipt) ...

/**
 * NUEVA FUNCIÓN
 * Busca la cabecera de un pedido por su ID.
 * (Requerido por gracias.php)
 */
function findOrderById(int $orderId): ?array
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM pedido WHERE id_pedido = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        return $order ?: null;
    } catch (\PDOException $e) {
        error_log($e->getMessage());
        return null;
    }
}

/**
 * NUEVA FUNCIÓN
 * Busca los detalles (items) de un pedido por su ID.
 * (Requerido por gracias.php)
 */
function findOrderItemsById(int $orderId): array
{
    try {
        $pdo = getPDO();
        // Hacemos un JOIN con la tabla de productos para obtener el nombre
        $stmt = $pdo->prepare("
            SELECT pi.*, p.nombre AS nombre_producto
            FROM pedido_item pi
            JOIN producto p ON pi.id_producto = p.id_producto
            WHERE pi.id_pedido = ?
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
}