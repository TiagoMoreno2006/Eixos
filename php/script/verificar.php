<?php
session_start();

// Initialize start flag for registration flow
$_SESSION['start'] = true;

include 'dbcon.php';

$conn = con();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = trim($_POST['pn'] ?? '');
    $lastName  = trim($_POST['un'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';

    // Basic input validation
    if (!$firstName || !$lastName || !$email || !$password) {
        $_SESSION['registration_error'] = "Por favor, preencha todos os campos.";
        $_SESSION['active_tab'] = "register";
        header("Location: ../../index.php");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['registration_error'] = "Email inválido.";
        $_SESSION['active_tab'] = "register";
        header("Location: ../../index.php");
        exit;
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM utilizadores WHERE email = ?");
    if (!$stmt) {
        $_SESSION['registration_error'] = "Erro interno: " . $conn->error;
        $_SESSION['active_tab'] = "register";
        header("Location: ../../index.php");
        exit;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['registration_error'] = "Este email já está registado.";
        $_SESSION['active_tab'] = "register";
        $stmt->close();
        $conn->close();
        header("Location: ../../index.php");
        exit;
    }

    $stmt->close();

    // Store user info in session
    $_SESSION['pn'] = $firstName;
    $_SESSION['un'] = $lastName;
    $_SESSION['email'] = $email;

    // Hash the password immediately before storing in session
    $_SESSION['password'] = password_hash($password, PASSWORD_DEFAULT);

    // Generate verification code
    $_SESSION['verification_code'] = rand(100000, 999999);

    // Send data to Zapier (keeping your working format)
    $zapierData = $_SESSION['email'] . "," . $_SESSION['verification_code'];

    $ch = curl_init('https://hooks.zapier.com/hooks/catch/24496736/um3hauh/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $zapierData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain']);
    curl_exec($ch);
    curl_close($ch);

    $conn->close();

    // Redirect to verification page
    header("Location: ../codigo.php");
    exit;

} else {
    $_SESSION['registration_error'] = "Requisição inválida.";
    $_SESSION['active_tab'] = "register";
    header("Location: ../../index.php");
    exit;
}
?>
