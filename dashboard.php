<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8"><title>لوحة الإدارة</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<h2>لوحة تحكم الأدمن</h2>
<ul>
    <li><a href="add_teacher.php">إدارة المعلمين</a></li>
    <li><a href="add_subject.php">إدارة المواد</a></li>
    <li><a href="add_student.php">إدارة الطلاب</a></li>
    <li><a href="add_grade.php">إدارة الدرجات</a></li>
    <li><a href="search.php">بحث النتائج (واجهة الطالب/ولي الأمر)</a></li>
    <li><a href="logout.php">تسجيل خروج</a></li>
</ul>
</body>
</html>
