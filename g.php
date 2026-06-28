<?php
session_start();

header('Content-Type: application/json');

// Ստանում ենք POST տվյալները
$input = json_decode(file_get_contents('php://input'), true);
$amount = $input['amount'] ?? 10;
$currency = $input['currency'] ?? 'usdttrc20';
$userId = $_SESSION['user_id'] ?? 0;

if (!$userId) {
    echo json_encode(['error' => 'Մուտք գործեք']);
    exit;
}

// Ստեղծում ենք order_id
$orderId = "user_{$userId}_" . time() . "_" . bin2hex(random_bytes(3));

// Պահում ենք
$orders = json_decode(file_get_contents('orders.json') ?: '[]', true);
$orders[] = [
    'order_id' => $orderId,
    'user_id' => $userId,
    'amount' => $amount,
    'currency' => $currency,
    'status' => 'pending',
    'created_at' => date('Y-m-d H:i:s')
];
file_put_contents('orders.json', json_encode($orders));

// NowPayments API կանչ
$apiKey = 'YOUR_NOWPAYMENTS_API_KEY'; // ՓՈԽԵՔ ՍԱ ՁԵՐ API KEY-ՈՎ!!!

$ch = curl_init('https://api.nowpayments.io/v1/invoice');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "x-api-key: $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'price_amount' => (float)$amount,
    'price_currency' => 'usd',
    'pay_currency' => $currency,
    'order_id' => $orderId,
    'ipn_callback_url' => 'https://yourdomain.com/ipn.php' // ՓՈԽԵՔ ՍԱ!
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 && $httpCode !== 201) {
    echo json_encode(['error' => 'API սխալ: ' . $response]);
    exit;
}

$result = json_decode($response, true);

// Վերադարձնում ենք հաճախորդին
echo json_encode([
    'invoice_id' => $result['id'],
    'pay_address' => $result['pay_address'],
    'pay_amount' => $result['pay_amount'],
    'invoice_url' => $result['invoice_url'],
    'order_id' => $orderId
]);
