<?php
// admin/api.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require 'conexxion.php';

$accion = $_GET['accion'] ?? 'listar';

try {
    if ($accion === 'listar') {
        $stmt = $pdo->query("SELECT * FROM emergencias ORDER BY fecha_inicio DESC LIMIT 50");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
    http_response_code(400);
    echo json_encode(['error' => 'Acción desconocida']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}