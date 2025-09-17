<?php
session_start();
include 'dbcon.php';

$conn = con();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['login_error'] = "Requisição inválida.";
    header("Location: ../../index.php");
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    $_SESSION['login_error'] = "Por favor, preencha todos os campos.";
    header("Location: ../../index.php");
    exit;
}

// Fetch user from 'utilizadores' table
$stmt = $conn->prepare("SELECT id, first_name, last_name, empresa, email, password, admin FROM utilizadores WHERE email = ?");
if (!$stmt) {
    $_SESSION['login_error'] = "Erro interno: " . $conn->error;
    header("Location: ../../index.php");
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($id, $first_name, $last_name, $empresa, $email_db, $hashed_password, $admin);
    $stmt->fetch();

    if (password_verify($password, $hashed_password)) {

        // Set session user
        $_SESSION['utilizador'] = [
            'id'         => $id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'empresa'    => $empresa,
            'email'      => $email_db,
            'admin'      => $admin
        ];

        // Fetch 'formulario' only with etapa
        $stmtForm = $conn->prepare("SELECT utilizadores_id, etapa FROM formulario WHERE utilizadores_id = ?");
        if ($stmtForm) {
            $stmtForm->bind_param("i", $id);
            $stmtForm->execute();
            $stmtForm->bind_result($utilizadores_id, $etapa);

            if ($stmtForm->fetch()) {
                $_SESSION['formulario'] = [
                    'utilizadores_id' => $utilizadores_id,
                    'etapa'           => $etapa
                ];
            }
            $stmtForm->close();
        }

        header("Location: ../main.php");
        exit;

    } else {
        $_SESSION['login_error'] = "Senha incorreta.";
    }
} else {
    $_SESSION['login_error'] = "Email não encontrado.";
}

$stmt->close();
$conn->close();

header("Location: ../../index.php");
exit;
