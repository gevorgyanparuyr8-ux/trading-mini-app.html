<?php
// IPN Secret Key
$ipnSecret = 'sqJWQM0kSm9kpOZY41sBpTck3G4AqtvV'; // ՓՈԽԵՔ ՍԱ!

$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data || $data['payment_status'] !== 'finished') {
    http_response_code(200);
    exit('OK');
}

// Ստորագրության ստուգում
$headers = getallheaders();
$receivedSig = $headers['X-Nowpayments-Sig'] ?? '';
$expectedSig = hash_hmac('sha512', $payload, $ipnSecret);

if (!hash_equals($expectedSig, $receivedSig)) {
    http_response_code(400);
    exit('Invalid signature');
}

$orderId = $data['order_id'];
$amount = $data['actually_paid'];

// Գտնում ենք user_id-ն
$orders = json_decode(file_get_contents('orders.json'), true);
foreach ($orders as &$order) {
    if ($order['order_id'] === $orderId && $order['status'] !== 'finished') {
        $order['status'] = 'finished';
        $userId = $order['user_id'];
        
        // Թարմացնում ենք բալանսը
        $users = json_decode(file_get_contents('users.json'), true);
        $users['users'][$userId]['balance'] += (float)$amount;
        file_put_contents('users.json', json_encode($users));
        
        break;
    }
}
file_put_contents('orders.json', json_encode($orders));

http_response_code(200);
echo 'OK';
?>
