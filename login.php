<?php
session_start();
include("db.php"); // تأكد أن هذا الملف اسمه db.php (أحرف صغيرة) وموجود في htdocs

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // تعديل هنا: استخدام role = ? وتمرير 'admin' كمعامل
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ? AND role = ?");
    $stmt->execute([$username, $password, 'admin']);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "اسم المستخدم أو كلمة المرور غير صحيحة.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>دخول الأدمن</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-form">
        <h2>دخول الإدارة</h2>
        <form method="post" action="login.php">
            <input type="text" name="username" placeholder="اسم المستخدم" required><br>
            <input type="password" name="password" placeholder="كلمة المرور" required><br>
            <button type="submit">دخول</button>
            <?php if($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
        </form>
        <p><a href="search.php">الذهاب إلى صفحة البحث عن نتائج الطلاب</a></p>
    </div>
</body>
</html>
