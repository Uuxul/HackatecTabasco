<?php
// admin/conexion.php
$host = 'sql309.infinityfree.com';
$db   = 'if0_43106266_db_uxul';   // ← CONFIRMA el nombre exacto en tu panel
$user = 'if0_43106266';
$pass = 'MT0bxAWHNzdTPw';         // ← tu contraseña real

try {
    $pdo = new PDO(
        "mysql:host=$host;port=3306;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Conexión fallida: ' . $e->getMessage()]));
}