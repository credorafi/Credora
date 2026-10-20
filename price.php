
<?php
header('Content-Type: application/json; charset=utf-8');

$allowedSymbols = [
    'NVDA', 'AAPL', 'SPY', 'TSLA', 'MSFT',
    'AMD', 'AMZN', 'GOOGL', 'META', 'COIN', 'TSM', 'MSTR', 'MU',
    'META', 'PLTR', 'QQQ', 'NFLX', 'GLD', 'SPCX', 'SGOV', 'AMC',
    'HIMS', 'RDDT', 'GME', 'LLY', 'DJT', 'COST', 'CRCL', 'USO',
    'INTC', 'IBM', 'RBLX', 'SLV', 'TTWO', 'SNDK', 'DELL', 'RIVN',
    'SNAP', 'BABA', 'NET', 'LULU', 'SHOP', 'MRNA', 'FIG', 'QUBT',
    'SNOW', 'INDA','BE', 'GLXY', 'NU', 'DFE', 'RCAT', 'LMT', 'JNJ',
    'BA', 'USAR', 'BB', 'FLY', 'WYFI', 'MRVL', 'VOXEL', 'USDG', 'WETH'
];

$symbol = strtoupper($_GET['symbol'] ?? 'NVDA');

if (!in_array($symbol, $allowedSymbols, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Symbol Not Allowed']);
    exit;
}

$url = 'https://api.robinhood.com/rhj/prices/' . rawurlencode($symbol);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_USERAGENT => 'SwapPriceDemo/1.0'
]);

$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false || $status < 200 || $status >= 300) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Failed to Fetch Price from Source',
        'status' => $status,
        'detail' => $error
    ]);
    exit;
}


$data = json_decode($response, true);
$quote = $data['quotes'][0] ?? null;

if (!$quote) {
    http_response_code(502);
    echo json_encode(['error' => 'Price Not Found']);
    exit;
}

echo json_encode([
    'symbol' => $quote['tokenSymbol'],
    'bid' => (float) $quote['bid'],
    'ask' => (float) $quote['ask'],
    'tokenBid' => (float) $quote['tokenBid'],
    'tokenAsk' => (float) $quote['tokenAsk'],
    'currency' => $quote['currency'],
    'updatedAt' => $quote['generatedAt']
]);
