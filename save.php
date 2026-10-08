<?php
/**
 * Guardado en disco del servidor (mismo sitio que el HTML).
 * Sube este archivo JUNTO al HTML. Los datos quedan en data.json
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

// CORS por si abres desde otro origen (normalmente misma carpeta)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$file = __DIR__ . DIRECTORY_SEPARATOR . 'data.json';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!is_file($file)) {
        echo json_encode(null);
        exit;
    }
    readfile($file);
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Cuerpo vacío']);
        exit;
    }
    // Validar JSON
    $decoded = json_decode($raw);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
        exit;
    }
    $tmp = $file . '.tmp';
    if (file_put_contents($tmp, $raw, LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'No se pudo escribir (permisos?)']);
        exit;
    }
    if (!rename($tmp, $file)) {
        @unlink($tmp);
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'No se pudo renombrar data.json']);
        exit;
    }
    echo json_encode(['ok' => true, 'bytes' => strlen($raw), 'savedAt' => time()]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
