<?php
include("db.php");
$result = null;
if($_SERVER["REQUEST_METHOD"]=="POST"){
    $name = $_POST["name"];
    $student_code = $_POST["student_code"];
    $q = $pdo->prepare("SELECT * FROM students WHERE name=? OR student_code=?");
    $q->execute([$name, $student_code]);
    $student = $q->fetch();
    if($student){
        $gradesq = $pdo->prepare("SELECT s.name AS subject, g.grade FROM grades g
                                  JOIN subjects s ON s.id = g.subject_id
                                  WHERE g.student_id=?");
        $gradesq->execute([$student["id"]]);
        $grades = $gradesq->fetchAll();
        $result = ["student"=>$student, "grades"=>$grades];
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>نتيجة الطالب</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<h2>بحث النتيجة</h2>
<form method="POST">
    <input name="name" placeholder="اسم الطالب">
    أو
    <input name="student_code" placeholder="الرقم السري">
    <button type="submit">بحث</button>
</form>
<?php if($result): ?>
<div class="result-card">
    <h3>شهادة بيان نتيجة الطالب</h3>
    <p>الاسم: <b><?= $result["student"]["name"] ?></b></p>
    <table>
        <tr><th>المادة</th><th>الدرجة</th></tr>
        <?php foreach($result["grades"] as $g): ?>
            <tr><td><?= $g["subject"] ?></td><td><?= $g["grade"] ?></td></tr>
        <?php endforeach; ?>
    </table>
    <button onclick="window.print()">طباعة الشهادة</button>
</div>
<?php elseif($_SERVER["REQUEST_METHOD"]=="POST"):?>
<div class="error">لا يوجد نتائج مطابقة.</div>
<?php endif;?>
</body>
</html>
