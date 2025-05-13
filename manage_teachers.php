<?php
require_once '../functions.php';
require_once '../DB_new.php';

require_role(['school_admin'], '../');

$page_title = "إدارة المعلمين";
$error_message = '';
$success_message = '';
$school_id = $_SESSION['school_id'];

if (!$school_id) {
    // Should not happen due to require_role and session checks
    logout_user('../');
    exit;
}

// Handle POST requests (Add, Edit, Delete Teacher)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = "خطأ في التحقق من الـ CSRF token. يرجى المحاولة مرة أخرى.";
    } else {
        // Add Teacher
        if (isset($_POST['add_teacher'])) {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            // $subject_specialization = trim($_POST['subject_specialization'] ?? ''); // Example field, not in current users table directly

            if (empty($username) || empty($password) || empty($full_name)) {
                $error_message = "اسم المستخدم، كلمة المرور، والاسم الكامل مطلوبون.";
            } elseif (strlen($password) < 8) {
                $error_message = "يجب أن تكون كلمة المرور 8 أحرف على الأقل.";
            } else {
                try {
                    $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR (email = ? AND email IS NOT NULL AND email != '')");
                    $stmt_check->execute([$username, $email]);
                    if ($stmt_check->fetch()) {
                        $error_message = "اسم المستخدم أو البريد الإلكتروني موجود بالفعل.";
                    } else {
                        $hashed_password = hash_password($password);
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, school_id, is_active) VALUES (?, ?, ?, ?, 'teacher', ?, TRUE)");
                        $stmt->execute([$username, $hashed_password, $full_name, $email ?: null, $school_id]);
                        $success_message = "تمت إضافة المعلم بنجاح.";
                    }
                } catch (PDOException $e) {
                    $error_message = "فشل في إضافة المعلم: " . $e->getMessage();
                     if (str_contains($e->getMessage(), 'Duplicate entry')) {
                         $error_message = "فشل في إضافة المعلم: اسم المستخدم أو البريد الإلكتروني موجود بالفعل.";
                    }
                    error_log("Add Teacher Error (School ID: {$school_id}): " . $e->getMessage());
                }
            }
        }
        // Edit Teacher (Placeholder - would typically be a separate page or modal)
        elseif (isset($_POST['edit_teacher_action'])) { // Renamed to avoid conflict if we add edit form fields
            $teacher_id_to_edit = filter_input(INPUT_POST, 'teacher_id', FILTER_VALIDATE_INT);
            $new_full_name = trim($_POST['edit_full_name'] ?? '');
            $new_email = trim($_POST['edit_email'] ?? '');
            $is_active = isset($_POST['edit_is_active']) ? 1 : 0;

            if ($teacher_id_to_edit && !empty($new_full_name)) {
                try {
                    // Check if new email (if provided) conflicts with another user, excluding the current teacher
                    if (!empty($new_email)) {
                        $stmt_email_check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                        $stmt_email_check->execute([$new_email, $teacher_id_to_edit]);
                        if ($stmt_email_check->fetch()) {
                            $error_message = "البريد الإلكتروني الجديد مستخدم بالفعل من قبل مستخدم آخر.";
                        }
                    }
                    if (empty($error_message)) { // Proceed if no email conflict
                        $stmt_update = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, is_active = ? WHERE id = ? AND school_id = ? AND role = 'teacher'");
                        $stmt_update->execute([$new_full_name, $new_email ?: null, $is_active, $teacher_id_to_edit, $school_id]);
                        $success_message = "تم تحديث بيانات المعلم بنجاح.";
                    }
                } catch (PDOException $e) {
                    $error_message = "فشل في تحديث بيانات المعلم: " . $e->getMessage();
                    error_log("Edit Teacher Error (School ID: {$school_id}, Teacher ID: {$teacher_id_to_edit}): " . $e->getMessage());
                }
            } else {
                $error_message = "بيانات غير كافية لتعديل المعلم.";
            }
        }
        // Delete Teacher
        elseif (isset($_POST['delete_teacher'])) {
            $teacher_id_to_delete = filter_input(INPUT_POST, 'teacher_id', FILTER_VALIDATE_INT);
            if ($teacher_id_to_delete) {
                try {
                    // Check if teacher is assigned to any class_subjects
                    $stmt_check_cs = $pdo->prepare("SELECT COUNT(*) FROM class_subjects WHERE teacher_id = ?");
                    $stmt_check_cs->execute([$teacher_id_to_delete]);
                    $assignment_count = $stmt_check_cs->fetchColumn();

                    if ($assignment_count > 0) {
                        $error_message = "لا يمكن حذف المعلم لأنه مسند إلى مواد دراسية. يرجى إزالة الإسنادات أولاً.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND school_id = ? AND role = 'teacher'");
                        $stmt->execute([$teacher_id_to_delete, $school_id]);
                        $success_message = "تم حذف المعلم بنجاح.";
                    }
                } catch (PDOException $e) {
                    $error_message = "فشل في حذف المعلم: " . $e->getMessage();
                    error_log("Delete Teacher Error (School ID: {$school_id}, Teacher ID: {$teacher_id_to_delete}): " . $e->getMessage());
                }
            }
        }
    }
}

// Fetch teachers for the current school
$teachers = [];
try {
    $stmt_teachers = $pdo->prepare("SELECT id, username, full_name, email, is_active FROM users WHERE school_id = ? AND role = 'teacher' ORDER BY full_name ASC");
    $stmt_teachers->execute([$school_id]);
    $teachers = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "فشل في جلب قائمة المعلمين: " . $e->getMessage();
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
        .container { padding: 20px; }
        .form-section, .table-section { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .form-section h2, .table-section h2 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        label, input, button { display: block; width: calc(100% - 22px); margin-bottom: 10px; padding: 10px; border-radius: 4px; border: 1px solid #ddd; box-sizing: border-box; }
        button { background-color: #28a745; color: white; cursor: pointer; border: none; }
        button:hover { background-color: #218838; }
        button.action-btn { width: auto; padding: 5px 10px; font-size: 0.9em; display: inline-block; margin-left: 5px; }
        button.edit-btn { background-color: #ffc107; }
        button.delete-btn { background-color: #dc3545; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        th { background-color: #f2f2f2; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
        .close-btn { color: #aaa; float: left; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-btn:hover, .close-btn:focus { color: black; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <header style="display: flex; justify-content: space-between; align-items: center; padding-bottom:10px; border-bottom: 1px solid #ccc; margin-bottom: 20px;">
            <h1><?php echo esc_html($page_title); ?> (مدرسة: <?php echo esc_html($_SESSION['school_name'] ?? 'غير محدد'); ?>)</h1>
            <div>
                <a href="dashboard.php" style="margin-left: 10px;">العودة إلى لوحة التحكم</a>
                <a href="../logout_new.php" class="button-logout">تسجيل الخروج</a>
            </div>
        </header>

        <?php if ($success_message): ?><div class="message success"><?php echo esc_html($success_message); ?></div><?php endif; ?>
        <?php if ($error_message): ?><div class="message error"><?php echo esc_html($error_message); ?></div><?php endif; ?>

        <div class="form-section">
            <h2>إضافة معلم جديد</h2>
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
                <button type="submit" name="add_teacher">إضافة المعلم</button>
            </form>
        </div>

        <div class="table-section">
            <h2>قائمة المعلمين</h2>
            <?php if (empty($teachers)): ?>
                <p>لا يوجد معلمون مضافون حالياً لهذه المدرسة.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>الاسم الكامل</th><th>اسم المستخدم</th><th>البريد الإلكتروني</th><th>الحالة</th><th>إجراءات</th></tr></thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                        <tr>
                            <td><?php echo esc_html($teacher['full_name']); ?></td>
                            <td><?php echo esc_html($teacher['username']); ?></td>
                            <td><?php echo esc_html($teacher['email'] ?? 'N/A'); ?></td>
                            <td><?php echo $teacher['is_active'] ? 'نشط' : 'غير نشط'; ?></td>
                            <td>
                                <button class="action-btn edit-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($teacher), ENT_QUOTES, 'UTF-8'); ?>)">تعديل</button>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo esc_html($csrf_token); ?>">
                                    <input type="hidden" name="teacher_id" value="<?php echo esc_html($teacher['id']); ?>">
                                    <button type="submit" name="delete_teacher" class="action-btn delete-btn" onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا المعلم؟');">حذف</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Edit Teacher Modal -->
        <div id="editTeacherModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeEditModal()">&times;</span>
                <h2>تعديل بيانات المعلم</h2>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo esc_html($csrf_token); ?>">
                    <input type="hidden" id="edit_teacher_id" name="teacher_id">
                    <label for="edit_full_name">الاسم الكامل:</label>
                    <input type="text" id="edit_full_name" name="edit_full_name" required>
                    <label for="edit_email">البريد الإلكتروني (اختياري):</label>
                    <input type="email" id="edit_email" name="edit_email">
                    <label for="edit_is_active">الحالة:</label>
                    <select id="edit_is_active" name="edit_is_active">
                        <option value="1">نشط</option>
                        <option value="0">غير نشط</option>
                    </select>
                    <p style="font-size:0.9em; color: #555;">ملاحظة: لا يمكن تعديل اسم المستخدم أو كلمة المرور من هنا. يمكن للمعلم تغيير كلمة المرور الخاصة به، أو يمكن لمدير النظام المساعدة في حالات استثنائية.</p>
                    <button type="submit" name="edit_teacher_action">حفظ التعديلات</button>
                </form>
            </div>
        </div>

        <footer><p>&copy; <?php echo date("Y"); ?> نظام الردفاني التعليمي.</p></footer>
    </div>

    <script>
        const modal = document.getElementById('editTeacherModal');
        const closeBtn = modal.querySelector('.close-btn');

        function openEditModal(teacher) {
            document.getElementById('edit_teacher_id').value = teacher.id;
            document.getElementById('edit_full_name').value = teacher.full_name;
            document.getElementById('edit_email').value = teacher.email || '';
            document.getElementById('edit_is_active').value = teacher.is_active ? '1' : '0';
            modal.style.display = 'block';
        }

        function closeEditModal() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeEditModal();
            }
        }
        // Add school name to session for display in header
        <?php if (!isset($_SESSION['school_name']) && $school_id): ?>
        <?php 
            try {
                $stmt_school_name_fetch = $pdo->prepare("SELECT name FROM schools WHERE id = ?");
                $stmt_school_name_fetch->execute([$school_id]);
                $school_data = $stmt_school_name_fetch->fetch();
                if ($school_data) {
                    $_SESSION['school_name'] = $school_data['name'];
                }
            } catch (PDOException $e) { /* ignore error, name won't be set */ }
        ?>
        <?php endif; ?>
    </script>
</body>
</html>
