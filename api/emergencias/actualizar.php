<?php

header(
    "Content-Type: application/json; charset=utf-8"
);


require_once
    __DIR__ .
    "/../config/database.php";


/*
=========================================
RECIBIR DATOS
=========================================
*/

$datos =
    json_decode(

        file_get_contents(
            "php://input"
        ),

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
VARIABLES
=========================================
*/

$id =
    $datos["id"] ?? null;


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
    !is_numeric($id) ||
    !is_numeric($latitud) ||
    !is_numeric($longitud)
) {

    http_response_code(400);


    echo json_encode([

        "ok" => false,

        "mensaje" =>
            "Datos inválidos"

    ]);


    exit;

}


/*
=========================================
ACTUALIZAR
=========================================
*/

$sql = "

    UPDATE emergencias

    SET

        latitud =
            :latitud,

        longitud =
            :longitud,

        precision_gps =
            :precision,

        ultima_actualizacion =
            NOW()

    WHERE id =
        :id

    AND estado IN (
        'ACTIVA',
        'ATENDIENDO'
    )

";


$stmt =
    $pdo->prepare(
        $sql
    );


$stmt->execute([

    ":latitud" =>
        $latitud,

    ":longitud" =>
        $longitud,

    ":precision" =>
        $precision,

    ":id" =>
        $id

]);


/*
=========================================
RESPUESTA
=========================================
*/

echo json_encode([

    "ok" => true,

    "id" => (int)$id,

    "actualizados" =>
        $stmt->rowCount()

]);