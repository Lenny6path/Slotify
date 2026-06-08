<?php

require_once __DIR__ . '/../config/init.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $password]);

    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>

<h2>Register</h2>

<form method="POST">

    <input name="name" placeholder="Name" required><br><br>
    <input name="email" placeholder="Email" required><br><br>
    <input name="password" type="password" placeholder="Password" required><br><br>

    <button type="submit">Create account</button>

</form>

</body>
</html>