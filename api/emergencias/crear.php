<?php

header(
    "Content-Type: application/json; charset=utf-8"
);


require_once
    __DIR__ .
    "/../config/database.php";


/*
=========================================
RECIBIR JSON DEL MÓVIL
=========================================
*/

$contenido =
    file_get_contents(
        "php://input"
    );


$datos =
    json_decode(
        $contenido,
        true
    );


if (!$datos) {

    http_response_code(400);


    echo json_encode([

        "ok" => false,

        "mensaje" =>
            "No se recibieron datos"

    ]);


    exit;

}


/*
=========================================
DATOS
=========================================
*/

$origen =
    $datos["origen"] ?? "MOVIL";


$tipo =
    $datos["tipo"] ?? "EMERGENCIA";


$latitud =
    $datos["latitud"] ?? null;


$longitud =
    $datos["longitud"] ?? null;


$precision =
    $datos["precision"] ?? null;


/*
=========================================
VALIDACIÓN
=========================================
*/

if (
    !is_numeric($latitud) ||
    !is_numeric($longitud)
) {

    http_response_code(400);


    echo json_encode([

        "ok" => false,

        "mensaje" =>
            "Latitud o longitud inválida"

    ]);


    exit;

}


if (
    $latitud < -90 ||
    $latitud > 90 ||
    $longitud < -180 ||
    $longitud > 180
) {

    http_response_code(400);


    echo json_encode([

        "ok" => false,

        "mensaje" =>
            "Coordenadas fuera de rango"

    ]);


    exit;

}


/*
=========================================
INSERTAR
=========================================
*/

$sql = "

    INSERT INTO emergencias
    (
        origen,
        tipo,
        latitud,
        longitud,
        precision_gps,
        estado,
        fecha_inicio,
        ultima_actualizacion
    )

    VALUES
    (
        :origen,
        :tipo,
        :latitud,
        :longitud,
        :precision,
        'ACTIVA',
        NOW(),
        NOW()
    )

";


$stmt =
    $pdo->prepare(
        $sql
    );


$stmt->execute([

    ":origen" =>
        $origen,

    ":tipo" =>
        $tipo,

    ":latitud" =>
        $latitud,

    ":longitud" =>
        $longitud,

    ":precision" =>
        $precision

]);


/*
=========================================
RESPUESTA
=========================================
*/

$id =
    $pdo->lastInsertId();


echo json_encode([

    "ok" => true,

    "id" => (int)$id,

    "mensaje" =>
        "Emergencia registrada correctamente"

]);