<?php
session_start();
include("db.php");
if(!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin"){ 
    header("Location: login.php"); exit(); 
}
if($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["name"])){
    $name = $_POST["name"];
    $subject_id = $_POST["subject_id"];
    $phone = $_POST["phone"];
    $pdo->prepare("INSERT INTO teachers (name, subject_id, phone) VALUES (?,?,?)")->execute([$name, $subject_id, $phone]);
}
$subjects = $pdo->query("SELECT * FROM subjects")->fetchAll();
$teachers = $pdo->query("SELECT t.*, s.name as subject FROM teachers t LEFT JOIN subjects s ON s.id = t.subject_id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8"><title>المعلمين</title><link rel="stylesheet" href="styles.css">
</head>
<body>
<h2>إدارة المعلمين</h2>
<form method="POST">
    <input name="name" placeholder="اسم المعلم" required>
    <select name="subject_id" required>
        <option value="">اختر المادة</option>
        <?php foreach($subjects as $s){ echo "<option value='".$s["id"]."'>".$s["name"]."</option>"; } ?>
    </select>
    <input name="phone" placeholder="الجوال">
    <button type="submit">إضافة</button>
</form>
<table>
    <tr><th>اسم المعلم</th><th>المادة</th><th>الجوال</th></tr>
    <?php foreach($teachers as $t): ?>
    <tr><td><?=$t["name"];?></td><td><?=$t["subject"];?></td><td><?=$t["phone"];?></td></tr>
    <?php endforeach; ?>
</table>
<a href="dashboard.php">عودة للوحة التحكم</a>
</body>
</html>
