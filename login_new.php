<?php
session_start();
require_once 'DB_new.php'; // استخدام ملف الاتصال الجديد
require_once 'functions.php'; // ملف الدوال المساعدة (سيتم إنشاؤه)

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password']; // لا تقم بعمل trim لكلمة المرور مباشرة

    if (empty($username) || empty($password)) {
        $error = "الرجاء إدخال اسم المستخدم وكلمة المرور.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role, school_id, full_name FROM users WHERE username = ? AND is_active = TRUE");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // تم التحقق من كلمة المرور بنجاح
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['school_id'] = $user['school_id']; // قد تكون NULL لـ system_admin

                // توجيه المستخدم بناءً على دوره
                switch ($user['role']) {
                    case 'system_admin':
                        header("Location: system_admin/dashboard.php");
                        break;
                    case 'school_admin':
                        header("Location: school_admin/dashboard.php");
                        break;
                    case 'teacher':
                        header("Location: teacher/dashboard.php");
                        break;
                    default:
                        // دور غير معروف أو غير مصرح له بالدخول من هنا
                        $error = "الدور المحدد غير مصرح له بالدخول.";
                        session_destroy(); // إنهاء الجلسة
                        break;
                }
                exit();
            } else {
                $error = "اسم المستخدم أو كلمة المرور غير صحيحة، أو الحساب غير مفعل.";
            }
        } catch (PDOException $e) {
            $error = "حدث خطأ أثناء محاولة تسجيل الدخول. يرجى المحاولة مرة أخرى.";
            // تسجيل الخطأ للمطورين
            error_log("Login PDOException: " . $e->getMessage());
        }
    }
}

// إذا كان المستخدم مسجلاً دخوله بالفعل، يتم توجيهه
if (is_logged_in()) {
    redirect_to_dashboard($_SESSION['role']);
    exit();
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام الردفاني التعليمي</title>
    <link rel="stylesheet" href="../styles.css"> <!- افترض أن ملف الأنماط في مجلد أعلى أو في نفس المجلد ->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #fff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-container h2 {
            color: #333;
            margin-bottom: 25px;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: calc(100% - 20px);
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            text-align: right;
        }
        .login-container button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        .login-container button[type="submit"]:hover {
            background-color: #0056b3;
        }
        .error-message {
            color: #d9534f;
            background-color: #f2dede;
            border: 1px solid #ebccd1;
            padding: 10px;
            border-radius: 4px;
            margin-top: 15px;
            font-size: 0.9em;
        }
        .search-link {
            display: block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            font-size: 0.9em;
        }
        .search-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>تسجيل الدخول إلى النظام</h2>
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div>
                <input type="text" name="username" placeholder="اسم المستخدم" required autofocus>
            </div>
            <div>
                <input type="password" name="password" placeholder="كلمة المرور" required>
            </div>
            <div>
                <button type="submit">دخول</button>
            </div>
        </form>
        <a href="search.php" class="search-link">البحث عن نتائج الطلاب</a>
    </div>
</body>
</html>
