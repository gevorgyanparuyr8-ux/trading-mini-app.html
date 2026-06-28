<?php
session_start();

header('Content-Type: application/json');

$invoiceId = $_GET['invoice_id'] ?? 0;
$apiKey = 'MEWGX85-J0Q4M0E-G08GHSR-XVGJARZ'; // ՓՈԽԵՔ ՍԱ!

$ch = curl_init("https://api.nowpayments.io/v1/invoice/{$invoiceId}");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-api-key: $apiKey"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
$status = $result['payment_status'] ?? 'unknown';

// Եթե վճարված է, թարմացնենք բալանսը
$newBalance = 0;
if ($status === 'finished') {
    $userId = $_SESSION['user_id'];
    $orderId = $result['order_id'];
    
    // Ստուգենք, որ արդեն չենք մշակել
    $orders = json_decode(file_get_contents('orders.json'), true);
    foreach ($orders as &$order) {
        if ($order['order_id'] === $orderId && $order['status'] !== 'finished') {
            $order['status'] = 'finished';
            
            // Թարմացնենք բալանսը
            $users = json_decode(file_get_contents('users.json'), true);
            $users['users'][$userId]['balance'] += (float)$order['amount'];
            $newBalance = $users['users'][$userId]['balance'];
            file_put_contents('users.json', json_encode($users));
            break;
        }
    }
    file_put_contents('orders.json', json_encode($orders));
}

echo json_encode([
    'status' => $status,
    'new_balance' => $newBalance ?: null
]);
