<?php
// api/index.php
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

// ======================== 1. GET: Public akses berdasarkan KTA tanpa login (opsional) ===//
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['kta'])) {
    $kta = trim($_GET['kta']);
    if (!preg_match('/^kta\d+$/i', $kta)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid KTA format']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT data FROM anggota WHERE kta = ?");
    $stmt->execute([$kta]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }

    echo $row['data'];
    exit;
}

// ===================== 2. POST: Simpan data dengan Bearer token ====================//
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ---- Wajib ada Bearer token ----
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing or invalid Authorization header']);
    exit;
}

$token = $matches[1];

// ---- Validate token dan rate limit ----
$stmt = $pdo->prepare("SELECT id, name, rate_limit FROM api_keys WHERE token = ?");
$stmt->execute([$token]);
$apiKey = $stmt->fetch();

if (!$apiKey) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API token']);
    exit;
}

// Rate limiting: max N per jam
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS cnt 
    FROM anggota 
    WHERE ip_address = ? 
      AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
");
$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$stmt->execute([$ip]);
$count = $stmt->fetchColumn();

if ($count >= $apiKey['rate_limit']) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded. Try again in 1 hour.']);
    exit;
}

// ---- Hanya menyetujui JSON ----
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
    http_response_code(415);
    echo json_encode(['error' => 'Content-Type must be application/json']);
    exit;
}

// ---- Baca & validasi JSON ----
$rawInput = trim(file_get_contents('php://input'));
if ($rawInput === '' || json_decode($rawInput) === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or empty JSON']);
    exit;
}

if (strlen($rawInput) > 524288) { // 512 KB max
    http_response_code(413);
    echo json_encode(['error' => 'Payload too large']);
    exit;
}

// ---- Simpan data secara secure ----
try {
    $pdo->beginTransaction();

    // Input data tanpa KTA 
    $stmt = $pdo->prepare("
        INSERT INTO anggota (kta, data, ip_address, user_agent) 
        VALUES (NULL, ?, ?, ?)
    ");
    $stmt->execute([
        $rawInput,
        $ip,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);

    $autoId   = $pdo->lastInsertId();
    $ktaCode  = 'kta' . $autoId;

    // Update dengan data KTA
    $stmt = $pdo->prepare("UPDATE anggota SET kta = ? WHERE id = ?");
    $stmt->execute([$ktaCode, $autoId]);

    // Update last_used timestamp pada tabel api_keys
    $pdo->prepare("UPDATE api_keys SET last_used = NOW() WHERE id = ?")
        ->execute([$apiKey['id']]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Save failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save data']);
    exit;
}

// ===================== SUCCESS ====================//
http_response_code(201);
echo json_encode([
    'success'   => true,
    'message'   => 'Berhasil disimpan',
    'kta'       => $ktaCode,           // e.g. kta42
    'saved_at'  => date('c'),
    'tip'       => 'Simpan KTA ini dalam database'
]);