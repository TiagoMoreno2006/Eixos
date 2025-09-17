<?php
$servername = "localhost";
$username = "root";   
$password = "";      

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Criar base de dados
$sql = "CREATE DATABASE IF NOT EXISTS eixosdb";
if ($conn->query($sql) === TRUE) {
    echo "Base de dados criada com sucesso.<br>";
} else {
    echo "Erro ao criar base de dados: " . $conn->error;
}

$conn->select_db("eixosdb");

// Criar tabela utilizadores
$sql = "CREATE TABLE IF NOT EXISTS utilizadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    empresa VARCHAR(100) NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    admin tinyint(1),
    password VARCHAR(255) NOT NULL
)";
if ($conn->query($sql) === TRUE) {
    echo "Tabela 'utilizadores' criada com sucesso.<br>";
} else {
    echo "Erro ao criar tabela: " . $conn->error;
}

// Criar tabela formulario
$sql = "CREATE TABLE IF NOT EXISTS formulario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilizadores_id INT NOT NULL,
    etapa INT NOT NULL,
    FOREIGN KEY (utilizadores_id) REFERENCES utilizadores(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Tabela 'formulario' criada com sucesso.<br>";
} else {
    echo "Erro ao criar tabela 'formulario': " . $conn->error;
}

// Criar usuário admin
$adminEmail = "admin@eixos.pt";
$adminPassword = password_hash("Admin123!", PASSWORD_DEFAULT);
$adminFirstName = "Admin";
$adminLastName = "User";

$stmt = $conn->prepare("SELECT id FROM utilizadores WHERE email = ?");
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    $stmtInsert = $conn->prepare("INSERT INTO utilizadores (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
    $stmtInsert->bind_param("ssss", $adminFirstName, $adminLastName, $adminEmail, $adminPassword);

    if ($stmtInsert->execute()) {
        echo "Usuário admin criado com sucesso.<br>";
    } else {
        echo "Erro ao criar usuário admin: " . $stmtInsert->error;
    }
    
    $stmtInsert->close();
} else {
    echo "Usuário admin já existe.<br>";
}

$stmt->close();
$conn->close();
?>
