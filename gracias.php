<?php
require_once __DIR__ . '/lib/auth_usr.php'; 
require_once __DIR__ . '/lib/receipt.php'; 

use function App\Lib\startSecureSession;
use function App\Lib\findOrderById;
use function App\Lib\findOrderItemsById;

startSecureSession();

$orderId = $_SESSION['last_order_id'] ?? null;
$order = null;
$items = [];

if ($orderId) {
    //
    $order = findOrderById((int)$orderId);
    $items = findOrderItemsById((int)$orderId);
    // Limpiamos el ID de la sesión para que no se muestre en futuras visitas
    unset($_SESSION['last_order_id']);
}

// Si no encontramos el pedido, redirigimos al inicio
if (!$order) {
    header('Location: index.php');
    exit;
}

// Incluimos el header
require_once __DIR__ . '/templates/header.php';
?>

<main class="container" class="section" style="padding-top: 40px; min-height: 70vh;">
    <header class="section-header" style="max-width: 800px; margin: auto;">
        <h1>¡Gracias por tu compra, <?php echo htmlspecialchars($order['nom_cliente']); ?>!</h1>
        <p class.lead">Tu pedido ha sido confirmado.</p>
    </header>

    <div class="order-summary-card" style="max-width: 800px; margin: 40px auto; padding: 24px; border: 1px solid var(--color-border); border-radius: var(--radius-base);">
        
        <h2>Resumen del Pedido #<?php echo htmlspecialchars($order['id_pedido']); ?></h2>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <h3>Detalles del Cliente</h3>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email_cliente']); ?></p>
                <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($order['num_cel']); ?></p>
            </div>
            <div>
                <h3>Dirección de Envío</h3>
                <p><?php echo htmlspecialchars($order['direccion']); ?></p>
                <p>CP: <?php echo htmlspecialchars($order['cod_post']); ?></p>
            </div>
        </div>

        <h3>Productos Comprados</h3>
        <ul style="list-style: none; padding: 0; margin-top: 20px;">
            <?php foreach ($items as $item): ?>
                <li style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--color-border-light);">
                    <span>
                        <?php echo htmlspecialchars($item['nombre_producto']); ?> 
                        (x <?php echo htmlspecialchars($item['cantidad']); ?>)
                    </span>
                    <strong>$<?php echo number_format($item['precio_unitario'] * $item['cantidad'], 2); ?></strong>
                </li>
            <?php endforeach; ?>
        </ul>

        <div style="text-align: right; margin-top: 20px; font-size: 1.1rem;">
            <p><strong>Subtotal:</strong> $<?php echo number_format($order['precio_subtotal'], 2); ?></p>
            <?php if ($order['descuento_aplicado'] > 0): ?>
                <p style="color: var(--color-success);"><strong>Descuento:</strong> -$<?php echo number_format($order['descuento_aplicado'], 2); ?></p>
            <?php endif; ?>
            <h3 style="margin-top: 10px;"><strong>Total Pagado: $<?php echo number_format($order['precio_total'], 2); ?></strong></h3>
        </div>

        <p style="text-align: center; margin-top: 30px;">
            Hemos enviado una confirmación y tu recibo a <strong><?php echo htmlspecialchars($order['email_cliente']); ?></strong>.
        </p>
        <div style="text-align: center; margin-top: 20px;">
            <a href="index.php" class="primary button">Volver al inicio</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/templates/footer.php'; ?>