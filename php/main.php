<?php
session_start();

// Check if session is active
if (!isset($_SESSION['formulario'])) {
    header("Location: erro.php"); // Redirect if no session
    exit();
}

// Etapa control
$etapa = $_SESSION['formulario']['etapa'] ?? 1;

// Redirect based on etapa
switch ($etapa) {
    case 1:
        header("Location: info/forms.php");
        exit();
    case 2:
        header("Location: info/segundo-forms.php");
        exit();
    case 3:
        // Stay on this page
        break;
    case 4:
        // Stay on this page (or add another redirect)
        break;
    default:
        // Do nothing
}

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Registro Recebido</title>
    <link rel="icon" type="image/png" href="../img/icon.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9f9f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: #fff;
            padding: 2rem 3rem;
            border-radius: 16px;
            box-shadow: 0 6px 14px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
        }
        .logo {
            max-width: 200px;
            margin-bottom: 1rem;
        }
        h1 {
            color: #ED6102;
            margin-bottom: 1rem;
        }
        p {
            color: #333;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        .divider {
            width: 60px;
            height: 4px;
            background-color: #ED6102;
            border-radius: 2px;
            margin: 1rem auto;
        }
        .logout-btn {
            display: inline-block;
            padding: 0.7rem 1.4rem;
            background-color: #ED6102;
            color: #fff;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        .logout-btn:hover {
            background-color: #c94f02;
        }
    </style>
</head>
<body>
    <div class="container">
        <img class="logo" src="https://eixos.pt/wp-content/uploads/2025/03/EIXOS_Fino_LINHA_i-removebg-preview-e1741613864870.png" alt="Logo">  
        <h1>Obrigado pelo seu registro!</h1>
        <p>Vamos contactar o seu email em breve com atualizações sobre o seu registro.</p>
        <a href="?logout=true" class="logout-btn">Sair</a>
    </div>
</body>
</html>
