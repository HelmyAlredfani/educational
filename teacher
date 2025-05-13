<?php
require_once '../functions.php'; // المسار النسبي لملف الدوال
require_once '../DB_new.php';    // المسار النسبي لملف الاتصال بقاعدة البيانات

require_role(["teacher"], '../'); // تحديد الأدوار المسموح لها بالوصول لهذه الصفحة

// التأكد من أن المعلم مرتبط بمدرسة
if (empty($_SESSION['school_id'])) {
    error_log("Teacher user {$_SESSION['user_id']} has no school_id.");
    logout_user('../');
    exit;
}

$school_id = $_SESSION['school_id'];
$teacher_id = $_SESSION['user_id'];
$page_title = "لوحة تحكم المعلم";

// جلب اسم المدرسة لعرضه (اختياري، لكن جيد للتجربة)
$school_name = "مدرستي"; // قيمة افتراضية
try {
    $stmt_school = $pdo->prepare("SELECT name FROM schools WHERE id = ?");
    $stmt_school->execute([$school_id]);
    $school = $stmt_school->fetch();
    if ($school) {
        $school_name = $school['name'];
    }
} catch (PDOException $e) {
    error_log("Error fetching school name for teacher dashboard: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($page_title); ?> - <?php echo esc_html($_SESSION['full_name']); ?> - نظام الردفاني</title>
    <link rel="stylesheet" href="../styles.css"> <!- المسار إلى ملف الأنماط الرئيسي ->
    <style>
        /* يمكن إضافة أنماط خاصة بهذه الصفحة هنا */
        .dashboard-container {
            padding: 20px;
        }
        .dashboard-container h1 {
            color: #333;
        }
        .dashboard-menu ul {
            list-style-type: none;
            padding: 0;
        }
        .dashboard-menu ul li {
            margin-bottom: 10px;
        }
        .dashboard-menu ul li a {
            display: block;
            padding: 10px 15px;
            background-color: #17a2b8; /* لون مختلف للمعلم */
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .dashboard-menu ul li a:hover {
            background-color: #138496;
        }
        .welcome-message {
            margin-bottom: 20px;
            font-size: 1.2em;
        }
        .info-header {
            font-size: 1.1em;
            color: #555;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header style="display: flex; justify-content: space-between; align-items: center; padding-bottom:10px; border-bottom: 1px solid #ccc;">
            <div>
                <h1><?php echo esc_html($page_title); ?></h1>
                <div class="info-header">المعلم: <?php echo esc_html($_SESSION['full_name']); ?> | المدرسة: <?php echo esc_html($school_name); ?></div>
            </div>
            <div>
                <a href="../logout_new.php" class="button-logout" style="text-decoration:none; color:white; background-color: #dc3545; padding: 8px 12px; border-radius:4px;">تسجيل الخروج</a>
            </div>
        </header>
        
        <p class="welcome-message">أهلاً بك في لوحة تحكم المعلم. من هنا يمكنك إدارة المواد والطلاب والدرجات الخاصة بك.</p>

        <nav class="dashboard-menu">
            <ul>
                <li><a href="manage_my_subjects_classes.php">إدارة المواد والصفوف المسندة لي (قيد الإنشاء)</a></li>
                <li><a href="enter_grades.php">إدخال/تعديل درجات الطلاب (قيد الإنشاء)</a></li>
                <li><a href="view_my_student_reports.php">عرض تقارير طلابي (قيد الإنشاء)</a></li>
                <li><a href="my_profile.php">ملفي الشخصي (قيد الإنشاء)</a></li>
            </ul>
        </nav>

        <main>
            <p>يرجى اختيار أحد الخيارات من القائمة أعلاه للبدء.</p>
            <!- يمكن إضافة محتوى إضافي هنا مثل إشعارات خاصة بالمعلم ->
        </main>

        <footer>
            <p>&copy; <?php echo date("Y"); ?> نظام الردفاني التعليمي. جميع الحقوق محفوظة.</p>
        </footer>
    </div>
</body>
</html>
