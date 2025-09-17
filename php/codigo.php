<?php
session_start();
include 'script/dbcon.php';

if (!isset($_SESSION['start'])) {
    header("Location: erro.php");
    exit;
}

$conn = con();
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_SESSION['verification_code']) && isset($_POST['codigo'])) {
        $inputCode = trim($_POST['codigo']);

        if ($inputCode === strval($_SESSION['verification_code'])) {

            $firstName = $_SESSION['pn'] ?? '';
            $lastName  = $_SESSION['un'] ?? '';
            $email     = $_SESSION['email'] ?? '';
            $password  = $_SESSION['password'] ?? '';
            $admin     = 0;
            $empresa   = "null";

            // Check if email exists
            $check = $conn->prepare("SELECT id FROM utilizadores WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows > 0) {
                $error = "Este email já está registrado.";
            } else {
                // Insert user
                $stmt = $conn->prepare("INSERT INTO utilizadores (first_name, last_name, empresa, email, admin, password) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssis", $firstName, $lastName, $empresa, $email, $admin, $password);

                if ($stmt->execute()) {
                    $userId = $stmt->insert_id;

                    // Insert initial form record
                    $stmtForm = $conn->prepare("INSERT INTO formulario (utilizadores_id, etapa) VALUES (?, 1)");
                    $stmtForm->bind_param("i", $userId);
                    $stmtForm->execute();
                    $stmtForm->close();

                    // Create user session
                    $_SESSION['utilizador'] = [
                        'id'         => $userId,
                        'first_name' => $firstName,
                        'last_name'  => $lastName,
                        'empresa'    => $empresa,
                        'email'      => $email,
                        'admin'      => $admin
                    ];

                    $_SESSION['formulario'] = [
                        'utilizadores_id' => $userId,
                        'etapa'           => 1
                    ];

                    // --------------------------
                    // Monday.com integration
                    // --------------------------
                    $apiToken = "eyJhbGciOiJIUzI1NiJ9.eyJ0aWQiOjU1ODYyODI1MCwiYWFpIjoxMSwidWlkIjo4MDQ4NDkwNiwiaWFkIjoiMjAyNS0wOS0wNVQxMzo1MDoyNC4wMDBaIiwicGVyIjoibWU6d3JpdGUiLCJhY3RpZCI6MzA5NjU4OTYsInJnbiI6ImV1YzEifQ.rqT6eu5Hu5qn5Jt9hPwiQCYWe1x-rzzIlxbKLbSCHC4";
                    $boardId  = 2112141202;  // Your board
                    $groupId  = "topics";    // Internal group ID in Monday.com

                    // Sanitize full name: id_firstname_lastname
                    $fullName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $userId . '_' . $firstName . '_' . $lastName);

                    $mutation = <<<GRAPHQL
mutation {
  create_item (
    board_id: $boardId,
    group_id: "$groupId",
    item_name: "$fullName"
  ) {
    id
    name
  }
}
GRAPHQL;

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://api.monday.com/v2/");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Content-Type: application/json",
                        "Authorization: $apiToken"
                    ]);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $mutation]));
                    curl_exec($ch); // No saving
                    curl_close($ch);
                    // --------------------------

                    header("Location: info/forms.php");
                    exit;
                } else {
                    $error = "Erro ao registrar usuário: " . $stmt->error;
                }
                $stmt->close();
            }
            $check->close();
        } else {
            $error = "Código incorreto. Tente novamente.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verificação</title>
<link rel="stylesheet" href="../css/main.css">
<link rel="icon" type="image/png" href="../img/icon.png">
</head>
<body>
<div class="main-veri">
    <div class="box">
        <form method="POST" action="">
            <img class="icon" src="https://eixos.pt/wp-content/uploads/2025/03/EIXOS_Fino_LINHA_i-removebg-preview-e1741613864870.png" alt="">
            <p style="font-size:big;">Enviamos um código de verificação para o seu email. Insira-o abaixo para concluir o registro.</p>

            <div class="input-container" style="width:50%; margin-left:25%;">
                <input type="text" required name="codigo"/>
                <label>Código</label>		
            </div>

            <?php if($error) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>

            <button type="submit" class="btn"><b>Confirmar</b></button>
            <p class="login-link">
                Não recebeu? <a href="login.html">Reenviar</a>
            </p>
        </form>	
    </div>
</div>
</body>
</html>
