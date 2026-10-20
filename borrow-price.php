
<?php
header('Content-Type: application/json; charset=utf-8');

$allowedSymbols = [
    'NVDA', 'SPY', 'AAPL', 'TSM', 'MSTR', 'GOOGL',
    'COIN', 'MU', 'TSLA', 'MSFT', 'META', 'PLTR',
    'QQQ', 'AMD', 'NFLX', 'AMZN', 'GLD', 'SPCX',
    'SGOV', 'AMC', 'HIMS', 'RDDT', 'GME', 'LLY',
    'DJT', 'COST', 'CRCL', 'USO', 'INTC', 'IBM',
    'RBLX', 'SLV', 'TTWO', 'SNDK', 'DELL', 'RIVN',
    'SNAP', 'BABA', 'NET', 'LULU', 'SHOP', 'MRNA',
    'FIG', 'QUBT', 'SNOW', 'INDA', 'BE', 'GLXY',
    'NU', 'DFE', 'RCAT', 'LMT', 'JNJ', 'BA',
    'USAR', 'BB', 'FLY', 'WYFI', 'MRVL', 'VOXEL',
    'USDG', 'WETH'
];

$symbol = strtoupper($_GET['symbol'] ?? 'NVDA');

if (!in_array($symbol, $allowedSymbols, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Simbol tidak diizinkan']);
    exit;
}

function requestJson($url) {
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'CredoraBorrow/1.0'
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($response === false || $status < 200 || $status >= 300) {
        return [
            'ok' => false,
            'status' => $status,
            'detail' => $error
        ];
    }

    $data = json_decode($response, true);

    if (!is_array($data)) {
        return [
            'ok' => false,
            'status' => $status,
            'detail' => 'Respons API bukan JSON valid'
        ];
    }

    return ['ok' => true, 'data' => $data];
}

function failResponse($message, $status = 502, $detail = '') {
    http_response_code($status);
    echo json_encode([
        'error' => $message,
        'status' => $status,
        'detail' => $detail
    ]);
    exit;
}

$cryptoIds = [
    'WETH' => 'weth',
    'USDG' => 'global-dollar',
    'VOXEL' => 'voxies'
];

if (isset($cryptoIds[$symbol])) {
    $id = $cryptoIds[$symbol];

    $url = 'https://api.coingecko.com/api/v3/simple/price'
         . '?ids=' . rawurlencode($id)
         . '&vs_currencies=usd'
         . '&include_last_updated_at=true';

    $result = requestJson($url);

    if (!$result['ok']) {
        failResponse(
            'Gagal mengambil harga kripto',
            502,
            'HTTP ' . $result['status'] . ' ' . $result['detail']
        );
    }

    $coin = $result['data'][$id] ?? null;
    $price = isset($coin['usd']) ? (float) $coin['usd'] : 0;

    if ($price <= 0) {
        failResponse(
            'Crypto Price Unavailable or Asset ID Needs Verification',
            502,
            'CoinGecko ID: ' . $id
        );
    }

    echo json_encode([
        'symbol' => $symbol,
        'bid' => $price,
        'ask' => $price,
        'currency' => 'USD',
        'updatedAt' => isset($coin['last_updated_at'])
            ? gmdate('c', (int) $coin['last_updated_at'])
            : null
    ]);
    exit;
}


$url = 'https://api.robinhood.com/rhj/prices/'
     . rawurlencode($symbol);

$result = requestJson($url);

if (!$result['ok']) {
    failResponse(
        'Gagal mengambil harga dari Robinhood',
        502,
        'HTTP ' . $result['status'] . ' ' . $result['detail']
    );
}

$quote = $result['data']['quotes'][0] ?? null;

if (!$quote) {
    failResponse('Price Data Not Found');
}

$bid = isset($quote['bid']) ? (float) $quote['bid'] : 0;
$ask = isset($quote['ask']) ? (float) $quote['ask'] : 0;

if ($bid <= 0 || $ask <= 0) {
    failResponse('Invalid Bid or Ask Price');
}

echo json_encode([
    'symbol' => $quote['tokenSymbol'] ?? $symbol,
    'bid' => $bid,
    'ask' => $ask,
    'currency' => $quote['currency'] ?? 'USD',
    'updatedAt' => $quote['generatedAt'] ?? null
]);
