<?php
// ¡Iniciamos la sesión ANTES de cualquier cosa!

use function App\Lib\buildReceiptHtml;
use function App\Lib\findOrderById;
use function App\Lib\findOrderItemsById;
use function App\Lib\generateOrderPdfStream;

session_start();

// === INICIO DE LA CORRECIÓN ===
// La ruta correcta para cargar el autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';
// === FIN DE LA CORRECIÓN ===

require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/order.php';    // Contiene 'createOrder'
require_once __DIR__ . '/lib/receipt.php';  // Contiene 'generateOrderReceipt'
require_once __DIR__ . '/lib/auth_usr.php'; // Contiene 'sendEmail'

// Configurar Stripe
\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data === null) {
        throw new \Exception("No se recibieron datos.");
    }

    // Validar datos básicos
    $formData = $data['formData'] ?? [];
    $paymentIntentId = $data['paymentIntentId'] ?? null;
    $name = $formData['nom_cliente'] ?? 'Cliente';
    $email = $formData['email'] ?? null;
    $phone = $formData['num_cel'] ?? null;
    $address = $formData['direccion'] ?? null;
    $zip = $formData['cod_post'] ?? null;
    $items = $data['cartItems'] ?? [];
    //$userId = $data['user_id'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;
    $couponId = $data['cuponId'] ?? null;


    if (!$paymentIntentId || !$email || empty($items)) {
        throw new \Exception("Datos incompletos para procesar el pedido.");
    }

    // Verificar el estado del Payment Intent en Stripe
    $intent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
    if ($intent->status !== 'succeeded') {
        throw new \Exception("El pago no ha sido exitoso. Estado: " . $intent->status);
    }

    // Llamamos a la función con su namespace completo
    $success = \App\Lib\createOrder(
        $name, $email, $phone,
        $address,
        $zip,
        $items,
        $paymentIntentId,
        $userId,
        $couponId ? (int)$couponId : null
    );

    if ($success && isset($_SESSION['last_order_id'])) {
        $orderId = $_SESSION['last_order_id'];
        $orderData = findOrderById((int)$orderId);
        $orderItems = findOrderItemsById((int)$orderId);

        if($orderData && $orderItems){
            // Generar el recibo en PDF (con namespace completo)
            $htmlContent = buildReceiptHtml($orderData, $orderItems);
            $pdfData = generateOrderPdfStream($htmlContent);
            $filename = "recibo_pedido_{$orderId}.pdf";

            // Enviar correo de confirmación (con namespace completo)
            $emailBody = "<h1>¡Gracias por tu compra!</h1>";
            $emailBody .= "<p>Hola {$name}, tu pedido #{$orderId} ha sido confirmado.</p>";
            $emailBody .= "<p>Adjuntamos tu recibo de compra. ¡Vuelve pronto!</p>";
        
            \App\Lib\sendEmail($email, 'Confirmación de tu pedido #' . $orderId, $emailBody, $pdfData, $filename);
        
        }
        
        // Respondemos al frontend con éxito
        echo json_encode(['success' => true, 'order_id' => $orderId]);
    } else {
        throw new \Exception("El pago fue exitoso, pero hubo un error al guardar tu pedido.");
    }

} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de Stripe: ' . $e->getMessage()]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}