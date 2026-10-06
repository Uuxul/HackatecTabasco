<?php

$host = "nombre base de datos";
$db   = "cityfix";
$user = "root";
$pass = "";

try {

    $pdo = new PDO(

        "mysql:host=$host;dbname=$db;charset=utf8mb4",

        $user,

        $pass,

        [
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC
        ]

    );

}
catch (PDOException $e) {

    http_response_code(500);

    header("Content-Type: application/json");

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error de conexión"
    ]);

    exit;

}