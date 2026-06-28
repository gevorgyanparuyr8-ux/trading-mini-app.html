<?php
session_start();

// Պարզ "բազա" (իրականում JSON ֆայլ, test-ի համար հերիք է)
if (!file_exists('users.json')) {
    file_put_contents('users.json', json_encode(['users' => []]));
}

// Թեստի համար ավտոմատ լոգին, եթե չկա
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = rand(100, 999);
    $_SESSION['username'] = 'User_' . $_SESSION['user_id'];
    
    // Պահենք users.json-ում
    $data = json_decode(file_get_contents('users.json'), true);
    $data['users'][$_SESSION['user_id']] = [
        'username' => $_SESSION['username'],
        'balance' => 0
    ];
    file_put_contents('users.json', json_encode($data));
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Ստանանք բալանսը
$data = json_decode(file_get_contents('users.json'), true);
$balance = $data['users'][$userId]['balance'] ?? 0;
?>

<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Կրիպտո Համալրում</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #0d1117;
            color: #c9d1d9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 12px;
            padding: 30px;
            width: 100%;
            max-width: 500px;
        }
        h1 {
            color: #58a6ff;
            text-align: center;
            margin-bottom: 10px;
        }
        .balance {
            text-align: center;
            font-size: 28px;
            color: #3fb950;
            margin: 20px 0;
            padding: 15px;
            background: #0d1117;
            border-radius: 8px;
        }
        .user-info {
            text-align: center;
            color: #8b949e;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin: 15px 0 5px;
            color: #8b949e;
        }
        input, select {
            width: 100%;
            padding: 12px;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 8px;
            color: #c9d1d9;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 14px;
            background: #238636;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        }
        button:hover { background: #2ea043; }
        
        .payment-box {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 8px;
            text-align: center;
        }
        .payment-box.active { display: block; }
        
        .address {
            background: #1a1f2b;
            padding: 12px;
            border-radius: 6px;
            font-family: monospace;
            word-break: break-all;
            color: #58a6ff;
            margin: 15px 0;
        }
        .amount-pay {
            font-size: 24px;
            color: #d2991d;
            margin: 10px 0;
        }
        .status {
            color: #8b949e;
            font-size: 14px;
            margin-top: 10px;
        }
        .note {
            background: #1a1f2b;
            border-left: 3px solid #d2991d;
            padding: 10px;
            margin-top: 15px;
            font-size: 13px;
            color: #8b949e;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚡ Կրիպտո Համալրում</h1>
        <div class="user-info">Բարև, <b><?php echo $username; ?></b> (ID: <?php echo $userId; ?>)</div>
        <div class="balance">💰 Բալանս: <span id="balance"><?php echo number_format($balance, 2); ?></span> USDT</div>
        
        <form id="topupForm">
            <label>Գումար (USD)</label>
            <input type="number" id="amount" value="10" min="1" step="1" required>
            
            <label>Ցանց / Արժույթ</label>
            <select id="currency">
                <option value="usdttrc20">USDT TRC20 (Tron)</option>
                <option value="usdtbsc">USDT BEP20 (BSC)</option>
                <option value="usdterc20">USDT ERC20 (Ethereum)</option>
                <option value="btc">Bitcoin (BTC)</option>
                <option value="eth">Ethereum (ETH)</option>
                <option value="ltc">Litecoin (LTC)</option>
                <option value="doge">Dogecoin (DOGE)</option>
                <option value="trx">TRON (TRX)</option>
                <option value="bnb">BNB (BSC)</option>
                <option value="matic">Polygon (MATIC)</option>
                <option value="sol">Solana (SOL)</option>
                <option value="ton">TON</option>
                <option value="xrp">XRP</option>
                <option value="ada">Cardano (ADA)</option>
                <option value="avax">Avalanche (AVAX)</option>
                <option value="dot">Polkadot (DOT)</option>
                <option value="near">NEAR</option>
                <option value="ftm">Fantom (FTM)</option>
                <option value="xmr">Monero (XMR)</option>
                <option value="zec">Zcash (ZEC)</option>
            </select>
            
            <button type="submit">🔄 Ստեղծել վճարման հասցե</button>
        </form>
        
        <div class="payment-box" id="paymentBox">
            <h3>📤 Ուղարկեք այս հասցեին</h3>
            <div class="amount-pay" id="payAmount"></div>
            <div class="address" id="payAddress"></div>
            <div class="status" id="paymentStatus">⏳ Սպասում ենք վճարմանը...</div>
            <div class="note">
                ⚠️ Էջը կարող եք փակել: Վճարումից հետո բալանսը կհամալրվի ավտոմատ:<br>
                Միայն ուղարկեք ճիշտ ցանցով (TRC20/BEP20):
            </div>
        </div>
    </div>

    <script>
        document.getElementById('topupForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const amount = document.getElementById('amount').value;
            const currency = document.getElementById('currency').value;
            
            try {
                const response = await fetch('create_invoice.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ amount, currency })
                });
                
                const data = await response.json();
                
                if (data.error) {
                    alert('Սխալ: ' + data.error);
                    return;
                }
                
                document.getElementById('payAmount').textContent = 
                    `Ուղարկեք ${data.pay_amount} ${currency}`;
                document.getElementById('payAddress').textContent = data.pay_address;
                document.getElementById('paymentBox').classList.add('active');
                document.getElementById('paymentStatus').textContent = 
                    '⏳ Սպասում ենք վճարմանը...';
                
                // Սկսում ենք ստուգել կարգավիճակը
                checkPaymentStatus(data.invoice_id);
                
            } catch (err) {
                alert('Սխալ: ' + err.message);
            }
        });
        
        async function checkPaymentStatus(invoiceId) {
            const checkInterval = setInterval(async () => {
                try {
                    const response = await fetch(`check_invoice.php?invoice_id=${invoiceId}`);
                    const data = await response.json();
                    
                    if (data.status === 'finished') {
                        document.getElementById('paymentStatus').textContent = 
                            '✅ Վճարումը ստացված է!';
                        document.getElementById('paymentStatus').style.color = '#3fb950';
                        document.getElementById('balance').textContent = 
                            parseFloat(data.new_balance).toFixed(2);
                        clearInterval(checkInterval);
                    } else if (data.status === 'waiting' || data.status === 'confirming') {
                        document.getElementById('paymentStatus').textContent = 
                            '🟡 Վճարումը սպասում է հաստատման...';
                    }
                } catch (err) {
                    console.log(err);
                }
            }, 5000); // 5 վայրկյանը մեկ
        }
    </script>
</body>
</html>
