<?php
session_start();

// Ensure user session exists
if (!isset($_SESSION['utilizador'])) {
    header("Location: ../html/error.php");
    exit();
}

// Get etapa from session
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
        break;
    case 4:
        break;
    default:
        // Do nothing
}

$utilizador = $_SESSION['utilizador'];

// Detect first and last name keys
$firstNameKeys = ['pn', 'first_name', 'nome', 'fname'];
$lastNameKeys = ['un', 'last_name', 'sobrenome', 'lname'];

$firstName = 'Nome';
$lastName = 'Sobrenome';

foreach ($firstNameKeys as $key) {
    if (!empty($utilizador[$key])) {
        $firstName = $utilizador[$key];
        break;
    }
}

foreach ($lastNameKeys as $key) {
    if (!empty($utilizador[$key])) {
        $lastName = $utilizador[$key];
        break;
    }
}

// Profile picture
$profilePic = $utilizador['pfp'] ?? '../img/pfp.png';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Inicial</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="icon" type="image/png" href="../img/icon.png">

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 20vw;
            background-color: white;
            color: black;
            height: 100vh;
            padding: 0;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            box-shadow: rgba(14, 30, 37, 0.12) 0px 2px 4px 0px, rgba(14, 30, 37, 0.32) 0px 2px 16px 0px; /* allow pushing items */
        }

        /* Profile (not clickable anymore) */
        .profile {
            display: flex;
            align-items: center;
            padding: 2vh 2vw;
            text-decoration: none;
            color: black;
            cursor: default;
        }

        .profile img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .profile .info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            margin-left: 1vw;
        }

        .profile .name,
        .profile .surname {
            margin: 0;
            padding: 0;
            text-align: left;
        }

        .profile .name {
            font-weight: bold;
        }

        .profile .surname {
            font-size: 0.9em;
            color: #555;
        }

        /* Menu links */
        .menu {
            flex-grow: 1; /* takes all remaining space */
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .sidebar ul li {
            margin: 0;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 2vh 2vw;
            color: black;
            text-decoration: none;
            box-sizing: border-box;
            transition: background 0.3s, color 0.3s;
            text-align: left;
        }

        .sidebar ul li a i {
            width: 20px;
            text-align: center;
        }

        .sidebar ul li a:hover {
            background-color: #ED6102;
            color: white;
        }

        /* Active link */
        .sidebar ul li a.active {
            background-color: #ED6102;
            color: white;
        }

        /* Logout at bottom */
        .logout {
            margin-top: auto;
        }

        /* Main content */
        .main-content {
            padding: 2vh 2vw;
            height: 85vh;
            width: 70vw;
            margin-left: 3vw;
            margin-right: 3vw;
            background-color: white;
            box-shadow: rgba(14, 30, 37, 0.12) 0px 2px 4px 0px, rgba(14, 30, 37, 0.32) 0px 2px 16px 0px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Profile (not clickable) -->
        <div class="profile">
            <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture">
            <div class="info">
                <span class="name"><?= htmlspecialchars($firstName) ?></span>
                <span class="surname"><?= htmlspecialchars($lastName) ?></span>
            </div>
        </div>

        <!-- Menu Links -->
        <div class="menu">
            <ul>
                <li><a href="main.php" class="active"><i class="fa-solid fa-house"></i> Página Inicial</a></li>
                <li><a href="upload.php"><i class="fa-solid fa-file-arrow-up"></i> Enviar Ficheiros</a></li>
                <li><a href="settings.php"><i class="fa-solid fa-gear"></i> Configurações</a></li>
            </ul>
        </div>

        <!-- Logout at bottom -->
        <ul class="logout">
            <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Bem-vindo!</h1>
        <p>Conteúdo principal da página aqui.</p>
    </div>
</body>
</html>
