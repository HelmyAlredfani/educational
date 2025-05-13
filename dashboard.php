<?php
require_once '../functions.php'; // المسار النسبي لملف الدوال
require_once '../DB_new.php';    // المسار النسبي لملف الاتصال بقاعدة البيانات

require_role(["school_admin"], '../'); // تحديد الأدوار المسموح لها بالوصول لهذه الصفحة

// التأكد من أن مدير المدرسة مرتبط بمدرسة
if (empty($_SESSION['school_id'])) {
    // هذا يجب ألا يحدث إذا كان تسجيل الدخول والبيانات صحيحة
    error_log("School admin user {$_SESSION['user_id']} has no school_id.");
    logout_user('../'); // تسجيل الخروج كإجراء أمان
    exit;
}

$school_id = $_SESSION['school_id'];
$page_title = "لوحة تحكم مدير المدرسة";

// جلب اسم المدرسة لعرضه
$school_name = "مدرستي"; // قيمة افتراضية
try {
    $stmt = $pdo->prepare("SELECT name FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
    if ($school) {
        $school_name = $school['name'];
    }
} catch (PDOException $e) {
    error_log("Error fetching school name: " . $e->getMessage());
    // يمكن ترك الاسم الافتراضي أو عرض رسالة خطأ بسيطة
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($page_title); ?> - <?php echo esc_html($school_name); ?> - نظام الردفاني</title>
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
            background-color: #28a745; /* لون مختلف لمدير المدرسة */
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .dashboard-menu ul li a:hover {
            background-color: #218838;
        }
        .welcome-message {
            margin-bottom: 20px;
            font-size: 1.2em;
        }
         .school-name-header {
            font-size: 1.5em;
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
                <div class="school-name-header">مدرسة: <?php echo esc_html($school_name); ?></div>
            </div>
            <div>
                <span style="margin-left: 15px;">مرحباً, <?php echo esc_html($_SESSION["full_name"]); ?>!</span>
                <a href="../logout_new.php" class="button-logout" style="text-decoration:none; color:white; background-color: #dc3545; padding: 8px 12px; border-radius:4px;">تسجيل الخروج</a>
            </div>
        </header>
        
        <p class="welcome-message">أهلاً بك في لوحة تحكم مدير المدرسة. من هنا يمكنك إدارة شؤون مدرستك.</p>

        <nav class="dashboard-menu">
            <ul>
                <li><a href="manage_teachers.php">إدارة المعلمين</a></li>
                <li><a href="manage_students.php">إدارة الطلاب</a></li>
                <li><a href="manage_classes.php">إدارة الصفوف الدراسية</a></li>
                <li><a href="manage_class_subjects.php">إدارة مواد الصفوف وتعيين المعلمين</a></li>
                <li><a href="view_school_reports.php">عرض تقارير المدرسة (قيد الإنشاء)</a></li>
                <li><a href="school_settings.php">إعدادات المدرسة (قيد الإنشاء)</a></li>
            </ul>
        </nav>

        <main>
            <p>يرجى اختيار أحد الخيارات من القائمة أعلاه للبدء.</p>
            <!- يمكن إضافة محتوى إضافي هنا مثل إحصائيات سريعة خاصة بالمدرسة ->
        </main>

        <footer>
            <p>&copy; <?php echo date("Y"); ?> نظام الردفاني التعليمي. جميع الحقوق محفوظة.</p>
        </footer>
    </div>
</body>
</html>
