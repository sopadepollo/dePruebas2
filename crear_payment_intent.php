<?php
// === INICIO DE LA CORRECIÓN ===
// La ruta correcta para cargar el autoloader de Composer

use function App\Lib\errorResponse;
use function App\Lib\jsonResponse;

require_once __DIR__ . '/../vendor/autoload.php';
// === FIN DE LA CORRECIÓN ===

require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/order.php';    // Contiene 'calculateOrderAmount'
require_once __DIR__ . '/lib/response.php'; // Contiene 'sendError' y 'sendJson'

// Configurar Stripe
\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data === null || empty($data['cartItems'])) {
        // Llamada con namespace completo
        errorResponse('No se recibieron productos.', 400);
        exit;
    }

    $items = $data['cartItems'];
    $couponId = $data['coupon_id'] ?? null; 

    // Llamada con namespace completo
    $orderData = \App\Lib\calculateOrderAmount($items, $couponId);

    // Crear el Payment Intent con el monto total calculado
    $intent = \Stripe\PaymentIntent::create([
        'amount' => $orderData['totalInCents'], // <-- Usamos el valor del array
        'currency' => 'mxn',
        'automatic_payment_methods' => ['enabled' => true],
    ]);

    // Llamada con namespace completo
    jsonResponse([
        'clientSecret' => $intent->client_secret,
        'subtotal' => $orderData['subtotal'],
        'discount' => $orderData['discountAmount'],
        'total' => $orderData['total']
    ]);
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Asegurarnos de que lib/response.php se cargó para usar sendError
    if (function_exists('\App\Lib\sendError')) {
        errorResponse('Error de Stripe: ' . $e->getMessage(), 500);
    } else {
        echo json_encode(['error' => 'Error de Stripe (y response.php falló): ' . $e->getMessage()]);
    }
} catch (\Exception $e) {
    if (function_exists('\App\Lib\sendError')) {
        errorResponse('Error interno: ' . $e->getMessage(), 500);
    } else {
        echo json_encode(['error' => 'Error interno (y response.php falló): ' . $e->getMessage()]);
    }
}