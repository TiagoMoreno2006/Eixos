<?php
function con() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "eixosdb";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Falha na conexão: " . $conn->connect_error);
    }

    $conn->set_charset("utf8");
    return $conn;
}
?>
