<?php
session_start();
include("db.php");
if(!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin"){ header("Location: login.php"); exit();}
$students = $pdo->query("SELECT * FROM students")->fetchAll();
$subjects = $pdo->query("SELECT * FROM subjects")->fetchAll();
$teachers = $pdo->query("SELECT * FROM teachers")->fetchAll();

if($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["student_id"])
    && !empty($_POST["subject_id"]) && isset($_POST["grade"])){
    $student_id = $_POST["student_id"];
    $subject_id = $_POST["subject_id"];
    $teacher_id = $_POST["teacher_id"];
    $grade = $_POST["grade"];
    $semester = $_POST["semester"];
    $pdo->prepare("INSERT INTO grades (student_id, subject_id, teacher_id, grade, semester)
                   VALUES (?,?,?,?,?)")->execute([$student_id, $subject_id, $teacher_id, $grade, $semester]);
}
$grades = $pdo->query("SELECT g.*, st.name as student, sb.name as subject, t.name as teacher 
    FROM grades g 
    JOIN students st ON st.id=g.student_id 
    JOIN subjects sb ON sb.id=g.subject_id 
    JOIN teachers t ON t.id=g.teacher_id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8"><title>الدرجات</title><link rel="stylesheet" href="styles.css">
</head>
<body>
<h2>إدارة الدرجات</h2>
<form method="POST">
    <select name="student_id" required>
        <option value="">الطالب</option>
        <?php foreach($students as $s){ echo "<option value=\"".$s["id"]."\">".$s["name"]."</option>"; } ?>
    </select>
    <select name="subject_id" required>
        <option value="">المادة</option>
        <?php foreach($subjects as $sb){ echo "<option value=\"".$sb["id"]."\">".$sb["name"]."</option>"; } ?>
    </select>
    <select name="teacher_id" required>
        <option value="">المعلم</option>
        <?php foreach($teachers as $t){ echo "<option value=\"".$t["id"]."\">".$t["name"]."</option>"; } ?>
    </select>
    <input name="grade" type="number" step="0.01" placeholder="الدرجة" required>
    <input name="semester" placeholder="الفصل">
    <button type="submit">إضافة</button>
</form>
<table>
    <tr><th>الطالب</th><th>المادة</th><th>المعلم</th><th>الدرجة</th><th>الفصل</th></tr>
    <?php foreach($grades as $g): ?>
    <tr>
      <td><?=$g["student"];?></td>
      <td><?=$g["subject"];?></td>
      <td><?=$g["teacher"];?></td>
      <td><?=$g["grade"];?></td>
      <td><?=$g["semester"];?></td>
    </tr>
    <?php endforeach; ?>
</table>
<a href="dashboard.php">عودة للوحة التحكم</a>
</body>
</html>
