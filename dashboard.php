<?php
require_once '../functions.php'; // المسار النسبي لملف الدوال
require_once '../DB_new.php'; // المسار النسبي لملف الاتصال بقاعدة البيانات

require_role(["system_admin"], '../'); // تحديد الأدوار المسموح لها بالوصول لهذه الصفحة

$page_title = "لوحة تحكم مدير النظام";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($page_title); ?> - نظام الردفاني التعليمي</title>
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
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .dashboard-menu ul li a:hover {
            background-color: #0056b3;
        }
        .welcome-message {
            margin-bottom: 20px;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header style="display: flex; justify-content: space-between; align-items: center; padding-bottom:10px; border-bottom: 1px solid #ccc;">
            <h1><?php echo esc_html($page_title); ?></h1>
            <div>
                <span style="margin-left: 15px;">مرحباً, <?php echo esc_html($_SESSION["full_name"]); ?>!</span>
                <a href="../logout_new.php" class="button-logout" style="text-decoration:none; color:white; background-color: #dc3545; padding: 8px 12px; border-radius:4px;">تسجيل الخروج</a>
            </div>
        </header>
        
        <p class="welcome-message">أهلاً بك في لوحة تحكم مدير النظام. من هنا يمكنك إدارة كافة جوانب النظام.</p>

        <nav class="dashboard-menu">
            <ul>
                <li><a href="manage_schools.php">إدارة المدارس</a></li>
                <li><a href="manage_school_admins.php">إدارة مدراء المدارس</a></li>
                <li><a href="manage_system_settings.php">إعدادات النظام (قيد الإنشاء)</a></li>
                <li><a href="view_system_logs.php">سجلات النظام (قيد الإنشاء)</a></li>
            </ul>
        </nav>

        <main>
            <p>يرجى اختيار أحد الخيارات من القائمة أعلاه للبدء.</p>
            <!- يمكن إضافة محتوى إضافي هنا مثل إحصائيات سريعة أو إشعارات ->
        </main>

        <footer>
            <p>&copy; <?php echo date("Y"); ?> نظام الردفاني التعليمي. جميع الحقوق محفوظة.</p>
        </footer>
    </div>
</body>
</html>
