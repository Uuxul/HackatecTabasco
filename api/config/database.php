<?php
$host = 'sql309.infinityfree.com'; // el host que te da el panel
$db   = 'if0_43106266_db_uxul';    // nombre completo real
$user = 'if0_43106266';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error: " . $conn->connect_error);
}
?>