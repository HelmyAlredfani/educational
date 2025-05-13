<?php
require_once '../functions.php';
require_once '../DB_new.php';

require_role(['system_admin'], '../');

$page_title = "إدارة المدارس";
$error_message = '';
$success_message = '';

// Handle POST requests (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = "خطأ في التحقق من الـ CSRF token. يرجى المحاولة مرة أخرى.";
    } else {
        // Add School
        if (isset($_POST['add_school'])) {
            $name = trim($_POST['name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if (empty($name)) {
                $error_message = "اسم المدرسة مطلوب.";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO schools (name, address, phone) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $address, $phone]);
                    $success_message = "تمت إضافة المدرسة بنجاح.";
                } catch (PDOException $e) {
                    $error_message = "فشل في إضافة المدرسة: " . $e->getMessage();
                    error_log($error_message);
                }
            }
        }
        // Edit School (Placeholder - more complex, often done on a separate edit page)
        // For simplicity, we'll just show a message here. A full edit would involve fetching by ID.
        elseif (isset($_POST['edit_school'])) {
            // $school_id_to_edit = $_POST['school_id'];
            // $new_name = $_POST['new_name'];
            // ... (fetch current, update, save)
            $success_message = "وظيفة تعديل المدرسة لم تنفذ بالكامل بعد في هذا المثال.";
        }
        // Delete School
        elseif (isset($_POST['delete_school'])) {
            $school_id_to_delete = filter_input(INPUT_POST, 'school_id', FILTER_VALIDATE_INT);
            if ($school_id_to_delete) {
                try {
                    // Check if school has associated users (school_admins, teachers) or classes before deleting
                    // For simplicity, direct delete here. In a real app, add checks or use ON DELETE SET NULL/CASCADE appropriately.
                    $stmt_check_users = $pdo->prepare("SELECT COUNT(*) FROM users WHERE school_id = ?");
                    $stmt_check_users->execute([$school_id_to_delete]);
                    $user_count = $stmt_check_users->fetchColumn();

                    $stmt_check_classes = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE school_id = ?");
                    $stmt_check_classes->execute([$school_id_to_delete]);
                    $class_count = $stmt_check_classes->fetchColumn();

                    if ($user_count > 0 || $class_count > 0) {
                        $error_message = "لا يمكن حذف المدرسة لوجود مستخدمين أو صفوف مرتبطة بها. قم بإزالة الارتباطات أولاً.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM schools WHERE id = ?");
                        $stmt->execute([$school_id_to_delete]);
                        $success_message = "تم حذف المدرسة بنجاح.";
                    }
                } catch (PDOException $e) {
                    $error_message = "فشل في حذف المدرسة: " . $e->getMessage();
                    error_log($error_message);
                }
            } else {
                $error_message = "معرف المدرسة غير صالح للحذف.";
            }
        }
    }
}

// Fetch all schools to display
$schools = [];
try {
    $stmt_schools = $pdo->query("SELECT id, name, address, phone FROM schools ORDER BY name ASC");
    $schools = $stmt_schools->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "فشل في جلب قائمة المدارس: " . $e->getMessage();
    error_log($error_message);
}

$csrf_token = generate_csrf_token();

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($page_title); ?> - نظام الردفاني التعليمي</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        .container {
            padding: 20px;
        }
        .form-section, .table-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-section h2, .table-section h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        label, input, button, textarea {
            display: block;
            width: calc(100% - 22px); /* Account for padding/border */
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }
        button {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            border: none;
        }
        button:hover {
            background-color: #0056b3;
        }
        button.delete-btn {
            background-color: #dc3545;
            width: auto;
            padding: 5px 10px;
            font-size: 0.9em;
            display: inline-block;
        }
        button.delete-btn:hover {
            background-color: #c82333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #f2f2f2;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <header style="display: flex; justify-content: space-between; align-items: center; padding-bottom:10px; border-bottom: 1px solid #ccc; margin-bottom: 20px;">
            <h1><?php echo esc_html($page_title); ?></h1>
            <div>
                <a href="dashboard.php" style="margin-left: 10px;">العودة إلى لوحة التحكم</a>
                <a href="../logout_new.php" class="button-logout" style="text-decoration:none; color:white; background-color: #dc3545; padding: 8px 12px; border-radius:4px;">تسجيل الخروج</a>
            </div>
        </header>

        <?php if ($success_message): ?>
            <div class="message success"><?php echo esc_html($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error"><?php echo esc_html($error_message); ?></div>
        <?php endif; ?>

        <div class="form-section">
            <h2>إضافة مدرسة جديدة</h2>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo esc_html($csrf_token); ?>">
                <label for="name">اسم المدرسة:</label>
                <input type="text" id="name" name="name" required>
                
                <label for="address">العنوان (اختياري):</label>
                <textarea id="address" name="address" rows="3"></textarea>
                
                <label for="phone">الهاتف (اختياري):</label>
                <input type="text" id="phone" name="phone">
                
                <button type="submit" name="add_school">إضافة المدرسة</button>
            </form>
        </div>

        <div class="table-section">
            <h2>قائمة المدارس الحالية</h2>
            <?php if (empty($schools)): ?>
                <p>لا توجد مدارس مضافة حالياً.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>الرقم التعريفي</th>
                            <th>اسم المدرسة</th>
                            <th>العنوان</th>
                            <th>الهاتف</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schools as $school): ?>
                        <tr>
                            <td><?php echo esc_html($school['id']); ?></td>
                            <td><?php echo esc_html($school['name']); ?></td>
                            <td><?php echo esc_html($school['address']); ?></td>
                            <td><?php echo esc_html($school['phone']); ?></td>
                            <td>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo esc_html($csrf_token); ?>">
                                    <input type="hidden" name="school_id" value="<?php echo esc_html($school['id']); ?>">
                                    <!-- Edit button can link to an edit page: manage_schools.php?action=edit&id=X -->
                                    <button type="button" onclick="alert('وظيفة التعديل الكاملة تتطلب صفحة منفصلة أو JavaScript أكثر تعقيدًا.')" style="background-color: #ffc107; margin-left:5px;">تعديل</button>
                                    <button type="submit" name="delete_school" class="delete-btn" onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذه المدرسة؟ هذا الإجراء لا يمكن التراجع عنه.');">حذف</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <footer>
            <p>&copy; <?php echo date("Y"); ?> نظام الردفاني التعليمي. جميع الحقوق محفوظة.</p>
        </footer>
    </div>
</body>
</html>
