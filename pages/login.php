<?php

require_once __DIR__ . '/../config/init.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["name"];

        header("Location: dashboard.php");
        exit;

    } else {
        echo "Identifiants incorrects";
    }
}
?>

<form method="POST">
    <h2>Login</h2>

    <input name="email" placeholder="Email" required>
    <input name="password" type="password" placeholder="Password" required>

    <button type="submit">Login</button>
</form>