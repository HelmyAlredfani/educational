<?php
session_start();
include("db.php");
if(!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin"){ header("Location: login.php"); exit();}
if($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["name"])){
    $name = $_POST["name"];
    $student_code = $_POST["student_code"];
    $class = $_POST["class"];
    $parent_name = $_POST["parent_name"];
    $phone = $_POST["phone"];
    $pdo->prepare("INSERT INTO students (name, student_code, class, parent_name, phone)
                   VALUES (?,?,?,?,?)")->execute([$name, $student_code, $class, $parent_name, $phone]);
}
$students = $pdo->query("SELECT * FROM students")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8"><title>الطلاب</title><link rel="stylesheet" href="styles.css">
</head>
<body>
<h2>إدارة الطلاب</h2>
<form method="POST">
    <input name="name" placeholder="اسم الطالب" required>
    <input name="student_code" placeholder="الرقم السري للطالب" required>
    <input name="class" placeholder="الصف">
    <input name="parent_name" placeholder="اسم ولي الأمر">
    <input name="phone" placeholder="الجوال">
    <button type="submit">إضافة</button>
</form>
<table>
    <tr><th>الاسم</th><th>الرقم السري</th><th>الصف</th><th>ولي الأمر</th><th>الجوال</th></tr>
    <?php foreach($students as $s): ?>
    <tr>
      <td><?=$s["name"];?></td>
      <td><?=$s["student_code"];?></td>
      <td><?=$s["class"];?></td>
      <td><?=$s["parent_name"];?></td>
      <td><?=$s["phone"];?></td>
    </tr>
    <?php endforeach; ?>
</table>
<a href="dashboard.php">عودة للوحة التحكم</a>
</body>
</html>
