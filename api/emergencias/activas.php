<?php

header(
    "Content-Type: application/json; charset=utf-8"
);


header(
    "Cache-Control: no-store, no-cache, must-revalidate"
);


require_once
    __DIR__ .
    "/../config/database.php";


/*
=========================================
CONSULTAR EMERGENCIAS
=========================================
*/

$sql = "

    SELECT

        id,

        origen,

        tipo,

        latitud,

        longitud,

        precision_gps,

        estado,

        fecha_inicio,

        ultima_actualizacion

    FROM emergencias

    WHERE estado IN (
        'ACTIVA',
        'ATENDIENDO'
    )

    ORDER BY
        fecha_inicio DESC

";


$stmt =
    $pdo->query(
        $sql
    );


$emergencias =
    $stmt->fetchAll();


/*
=========================================
RESPUESTA
=========================================
*/

echo json_encode([

    "ok" => true,

    "total" =>
        count(
            $emergencias
        ),

    "emergencias" =>
        $emergencias

]);