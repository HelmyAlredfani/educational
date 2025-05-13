<?php
session_start();
include("db.php");
if(!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin"){ header("Location: login.php"); exit();}
if($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["name"])){
    $name = $_POST["name"];
    $pdo->prepare("INSERT INTO subjects (name) VALUES (?)")->execute([$name]);
}
$subjects = $pdo->query("SELECT * FROM subjects")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8"><title>المواد</title><link rel="stylesheet" href="styles.css">
</head>
<body>
<h2>إدارة المواد</h2>
<form method="POST">
    <input name="name" placeholder="اسم المادة" required>
    <button type="submit">إضافة</button>
</form>
<table>
    <tr><th>المادة</th></tr>
    <?php foreach($subjects as $s): ?>
    <tr><td><?=$s["name"];?></td></tr>
    <?php endforeach; ?>
</table>
<a href="dashboard.php">عودة للوحة التحكم</a>
</body>
</html>
