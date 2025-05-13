<?php
require_once '../functions.php';
require_once '../DB_new.php';

require_role(['system_admin'], '../');

$page_title = "إدارة مدراء المدارس";
$error_message = '';
$success_message = '';

// Fetch schools for dropdown
$schools = [];
try {
    $stmt_schools_list = $pdo->query("SELECT id, name FROM schools ORDER BY name ASC");
    $schools = $stmt_schools_list->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "فشل في جلب قائمة المدارس: " . $e->getMessage();
    error_log($error_message);
}


// Handle POST requests (Add School Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = "خطأ في التحقق من الـ CSRF token. يرجى المحاولة مرة أخرى.";
    } else {
        if (isset($_POST['add_school_admin'])) {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $school_id = filter_input(INPUT_POST, 'school_id', FILTER_VALIDATE_INT);

            if (empty($username) || empty($password) || empty($full_name) || !$school_id) {
                $error_message = "جميع الحقول (اسم المستخدم، كلمة المرور، الاسم الكامل، المدرسة) مطلوبة.";
            } elseif (strlen($password) < 8) {
                $error_message = "يجب أن تكون كلمة المرور 8 أحرف على الأقل.";
            } else {
                try {
                    // Check if username or email already exists
                    $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR (email = ? AND email IS NOT NULL AND email != '')");
                    $stmt_check->execute([$username, $email]);
                    if ($stmt_check->fetch()) {
                        $error_message = "اسم المستخدم أو البريد الإلكتروني موجود بالفعل.";
                    } else {
                        $hashed_password = hash_password($password);
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, school_id, is_active) VALUES (?, ?, ?, ?, 'school_admin', ?, TRUE)");
                        $stmt->execute([$username, $hashed_password, $full_name, $email ?: null, $school_id]);
                        $success_message = "تمت إضافة مدير المدرسة بنجاح.";
                    }
                } catch (PDOException $e) {
                    $error_message = "فشل في إضافة مدير المدرسة: " . $e->getMessage();
                    if (str_contains($e->getMessage(), 'Duplicate entry')) {
                         $error_message = "فشل في إضافة مدير المدرسة: اسم المستخدم أو البريد الإلكتروني موجود بالفعل.";
                    }
                    error_log("Add School Admin Error: " . $e->getMessage());
                }
            }
        }
        // Implement Edit/Delete later if needed, typically on separate pages or via AJAX for better UX
    }
}

// Fetch all school admins to display
$school_admins = [];
try {
    $stmt_admins = $pdo->prepare("SELECT u.id, u.username, u.full_name, u.email, u.is_active, s.name AS school_name FROM users u JOIN schools s ON u.school_id = s.id WHERE u.role = 'school_admin' ORDER BY s.name ASC, u.full_name ASC");
    $stmt_admins->execute();
    $school_admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "فشل في جلب قائمة مدراء المدارس: " . $e->getMessage();
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
        label, input, select, button {
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
        /* Add styles for action buttons in table if needed */
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
        <?php if ($error_message && !str_contains($error_message, "فشل في جلب قائمة المدارس") && !str_contains($error_message, "فشل في جلب قائمة مدراء المدارس")): // Do not show general fetch errors here, they are logged ?>
            <div class="message error"><?php echo esc_html($error_message); ?></div>
        <?php endif; ?>

        <div class="form-section">
            <h2>إضافة مدير مدرسة جديد</h2>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo esc_html($csrf_token); ?>">
                
                <label for="full_name">الاسم الكامل:</label>
                <input type="text" id="full_name" name="full_name" required>

                <label for="username">اسم المستخدم:</label>
                <input type="text" id="username" name="username" required>
                
                <label for="password">كلمة المرور (8 أحرف على الأقل):</label>
                <input type="password" id="password" name="password" required>

                <label for="email">البريد الإلكتروني (اختياري):</label>
                <input type="email" id="email" name="email">
                
                <label for="school_id">المدرسة:</label>
                <select id="school_id" name="school_id" required>
                    <option value="">-- اختر المدرسة --</option>
                    <?php foreach ($schools as $school): ?>
                        <option value="<?php echo esc_html($school['id']); ?>"><?php echo esc_html($school['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" name="add_school_admin">إضافة مدير المدرسة</button>
            </form>
        </div>

        <div class="table-section">
            <h2>قائمة مدراء المدارس الحاليين</h2>
            <?php if (empty($school_admins) && empty($error_message)): ?>
                <p>لا يوجد مدراء مدارس مضافون حالياً.</p>
            <?php elseif (!empty($error_message) && str_contains($error_message, "فشل في جلب قائمة مدراء المدارس")):
                 echo "<div class='message error'>" . esc_html($error_message) . "</div>"; ?>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>الاسم الكامل</th>
                            <th>اسم المستخدم</th>
                            <th>البريد الإلكتروني</th>
                            <th>المدرسة</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($school_admins as $admin): ?>
                        <tr>
                            <td><?php echo esc_html($admin['full_name']); ?></td>
                            <td><?php echo esc_html($admin['username']); ?></td>
                            <td><?php echo esc_html($admin['email'] ?? 'N/A'); ?></td>
                            <td><?php echo esc_html($admin['school_name']); ?></td>
                            <td><?php echo $admin['is_active'] ? 'نشط' : 'غير نشط'; ?></td>
                            <td>
                                <button type="button" onclick="alert(
                                    'وظيفة التعديل والحذف لمدراء المدارس تتطلب صفحات منفصلة أو JavaScript أكثر تعقيدًا للتعامل مع إعادة تعيين كلمة المرور، تغيير المدرسة، إلخ.\n                                    يمكن تنفيذها كـ manage_school_admins.php?action=edit&id=XYZ أو manage_school_admins.php?action=delete&id=XYZ.'
                                )" style="background-color: #ffc107; margin-left:5px; width:auto; padding: 5px 10px; font-size:0.9em; display:inline-block;">تعديل</button>
                                <button type="button" onclick="alert('راجع الملاحظة السابقة بخصوص التعديل/الحذف.')" class="delete-btn" style="width:auto; padding: 5px 10px; font-size:0.9em; display:inline-block;">حذف</button>
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
