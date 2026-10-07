<?php
$servername = "sql309.infinityfree.com";
$username = "if0_43106266";
$password = "MT0bxAWHNzdTPw"; // La contraseña que muestra la imagen
$dbname = "if0_43106266_db_uxul"; // ¡OJO! Usa el nombre de la lista de abajo

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
echo "Conexión exitosa";
?>