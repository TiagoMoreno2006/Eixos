<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Erro</title>
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
            max-width: 180px;
            margin-bottom: 1rem;
        }
        h1 {
            color: #ED6102;
            margin-bottom: 1rem;
        }
        p {
            color: #444;
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
        .btn {
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
        .btn:hover {
            background-color: #c94f02;
        }
    </style>
</head>
<body>
    <div class="container">
        <img class="logo" src="https://eixos.pt/wp-content/uploads/2025/03/EIXOS_Fino_LINHA_i-removebg-preview-e1741613864870.png" alt="Logo">
        <div class="divider"></div>
        <h1>Ocorreu um erro!</h1>
        <p>Algo correu mal. Por favor, volte à página inicial e tente novamente.</p>
        <a href="../index.php" class="btn">Voltar ao Início</a>
    </div>
</body>
</html>
