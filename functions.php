<?php
// functions.php - Utility functions for the Alredfani Educational System

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if a user is logged in.
 *
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in(): bool
{
    return isset($_SESSION["user_id"]) && isset($_SESSION["role"]);
}

/**
 * Redirects to the appropriate dashboard based on user role.
 * If not logged in, redirects to the login page.
 *
 * @param string|null $role The user's role.
 * @param string $base_path The base path for redirection (e.g., '../' or '').
 */
function redirect_to_dashboard(string $role = null, string $base_path = ''):
    void
{
    if (!is_logged_in() || $role === null) {
        header("Location: " . $base_path . "login_new.php");
        exit();
    }

    switch ($role) {
        case 'system_admin':
            header("Location: " . $base_path . "system_admin/dashboard.php");
            break;
        case 'school_admin':
            header("Location: " . $base_path . "school_admin/dashboard.php");
            break;
        case 'teacher':
            header("Location: " . $base_path . "teacher/dashboard.php");
            break;
        default:
            // If role is unknown or not permitted for a dashboard, redirect to login
            header("Location: " . $base_path . "login_new.php?error=unknown_role");
            break;
    }
    exit();
}

/**
 * Restricts access to a page based on allowed roles.
 * If the user is not logged in or their role is not allowed, they are redirected.
 *
 * @param array $allowed_roles An array of roles allowed to access the page.
 * @param string $base_path The base path for redirection if access is denied.
 */
function require_role(array $allowed_roles, string $base_path = '../'): void
{
    if (!is_logged_in()) {
        header("Location: " . $base_path . "login_new.php?error=not_logged_in");
        exit();
    }
    if (!in_array($_SESSION["role"], $allowed_roles, true)) {
        // Redirect to their own dashboard or a generic access denied page
        // For simplicity, redirecting to their dashboard might be less jarring
        // Or, create a specific access_denied.php page
        // For now, let's redirect to login with an error, or their dashboard if possible.
        // redirect_to_dashboard($_SESSION['role'], $base_path); // This might cause redirect loop if they land on wrong page
        header("Location: " . $base_path . "login_new.php?error=access_denied"); // Simpler approach
        // For a more sophisticated system, you might log this attempt
        // and show a specific "Access Denied" page.
        error_log("Access Denied: User {" . $_SESSION['username'] . "} with role {" . $_SESSION['role'] . "} tried to access a page restricted to [" . implode(', ', $allowed_roles) . "]");
        exit();
    }
    // If user is school_admin or teacher, ensure they have a school_id associated, unless it's a system_admin page
    if (($_SESSION['role'] === 'school_admin' || $_SESSION['role'] === 'teacher') && empty($_SESSION['school_id'])) {
        // This case should ideally not happen if data integrity is maintained
        // (i.e., school_admins and teachers always have a school_id)
        error_log("User {" . $_SESSION['username'] . "} role {" . $_SESSION['role'] . "} has no school_id.");
        // Potentially log them out or redirect to an error page
        header("Location: " . $base_path . "logout_new.php?error=missing_school_id");
        exit();
    }
}

/**
 * Hashes a password using PHP's password_hash function.
 *
 * @param string $password The password to hash.
 * @return string The hashed password.
 */
function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Generates a secure CSRF token and stores it in the session.
 * @return string The CSRF token.
 */
function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies a CSRF token.
 * @param string $token The token from the form.
 * @return bool True if valid, false otherwise.
 */
function verify_csrf_token(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitizes output to prevent XSS.
 * @param string|null $data The data to sanitize.
 * @return string The sanitized data.
 */
function esc_html(?string $data): string
{
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

/**
 * Logs out the current user and destroys the session.
 * @param string $base_path The base path for redirection after logout.
 */
function logout_user(string $base_path = ''): void {
    $_SESSION = array(); // Clear all session variables

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
    header("Location: " . $base_path . "login_new.php?status=logged_out");
    exit();
}

?>



/**
 * Calculates the student's grades summary including total marks, percentage, and evaluation.
 *
 * @param PDO $pdo PDO database connection object.
 * @param int $student_id The ID of the student.
 * @param string|null $semester Optional: The semester (e.g., 'الفصل الأول').
 * @param string|null $academic_year Optional: The academic year (e.g., '2024-2025').
 * @return array An array containing 'total_obtained_marks', 'total_maximum_marks', 'percentage', 'evaluation', 'grades_details'.
 */
function get_student_grades_summary(PDO $pdo, int $student_id, ?string $semester = null, ?string $academic_year = null): array
{
    $grades_query = "SELECT g.grade, s.name AS subject_name, s.max_grade, cs.academic_year, g.semester, g.exam_type "
                  . "FROM grades g "
                  . "JOIN class_subjects cs ON g.class_subject_id = cs.id "
                  . "JOIN subjects s ON cs.subject_id = s.id "
                  . "WHERE g.student_id = :student_id";

    $params = [':student_id' => $student_id];

    if ($semester !== null) {
        $grades_query .= " AND g.semester = :semester";
        $params[':semester'] = $semester;
    }
    if ($academic_year !== null) {
        $grades_query .= " AND cs.academic_year = :academic_year";
        $params[':academic_year'] = $academic_year;
    }

    $stmt = $pdo->prepare($grades_query);
    $stmt->execute($params);
    $grades_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_obtained_marks = 0;
    $total_maximum_marks = 0;

    if (empty($grades_details)) {
        return [
            'total_obtained_marks' => 0,
            'total_maximum_marks' => 0,
            'percentage' => 0,
            'evaluation' => 'لا توجد درجات مدخلة',
            'grades_details' => [],
            'rank_in_class' => null // Will be calculated separately
        ];
    }

    foreach ($grades_details as $grade_item) {
        if (is_numeric($grade_item['grade'])) {
            $total_obtained_marks += (float)$grade_item['grade'];
        }
        // Ensure max_grade is added only once per subject if grades are for different exam_types of same subject
        // For simplicity now, assuming each entry in grades_details corresponds to a unique subject contribution to total
        // This might need refinement if a student has multiple grade entries for the *same* subject instance (e.g. mid-term and final for one subject listing)
        // The current DB schema with class_subject_id implies one main grade per subject per student per period.
        if (is_numeric($grade_item['max_grade'])) {
            $total_maximum_marks += (float)$grade_item['max_grade'];
        }
    }

    $percentage = ($total_maximum_marks > 0) ? ($total_obtained_marks / $total_maximum_marks) * 100 : 0;
    $percentage = round($percentage, 2);

    $evaluation = get_evaluation_from_percentage($percentage);

    return [
        'total_obtained_marks' => $total_obtained_marks,
        'total_maximum_marks' => $total_maximum_marks,
        'percentage' => $percentage,
        'evaluation' => $evaluation,
        'grades_details' => $grades_details,
        'rank_in_class' => null // Placeholder, to be filled by another function
    ];
}

/**
 * Determines the evaluation string based on the percentage.
 *
 * @param float $percentage The student's percentage.
 * @return string The evaluation string.
 */
function get_evaluation_from_percentage(float $percentage): string
{
    if ($percentage >= 90) return "ممتاز";
    if ($percentage >= 80) return "جيد جداً";
    if ($percentage >= 70) return "جيد";
    if ($percentage >= 60) return "مقبول";
    if ($percentage >= 50) return "ضعيف"; // Or a passing grade if 50 is pass
    return "راسب"; // Or "ضعيف جداً" if 50 is not the lowest pass
    // This scale should be confirmed or made configurable
}

/**
 * Calculates the student's rank in their class based on percentage.
 *
 * @param PDO $pdo PDO database connection object.
 * @param int $student_id The ID of the student to rank.
 * @param int $class_id The ID of the class.
 * @param string|null $semester Optional: The semester.
 * @param string|null $academic_year Optional: The academic year.
 * @return int|null The student's rank (1 for top) or null if not calculable.
 */
function get_student_rank_in_class(PDO $pdo, int $student_id, int $class_id, ?string $semester = null, ?string $academic_year = null): ?int
{
    // First, get all students in the class
    $stmt_students = $pdo->prepare("SELECT id FROM students WHERE class_id = :class_id");
    $stmt_students->execute([':class_id' => $class_id]);
    $class_students = $stmt_students->fetchAll(PDO::FETCH_COLUMN);

    if (empty($class_students)) {
        return null; // No students in class
    }

    $student_percentages = [];
    foreach ($class_students as $s_id) {
        $summary = get_student_grades_summary($pdo, (int)$s_id, $semester, $academic_year);
        $student_percentages[(int)$s_id] = $summary['percentage'];
    }

    // Sort students by percentage in descending order
    arsort($student_percentages); // arsort preserves keys

    $rank = 0;
    $current_rank = 0;
    $previous_percentage = -1; // Initialize with a value that no percentage can be

    foreach ($student_percentages as $s_id => $percentage) {
        $current_rank++;
        if ($percentage != $previous_percentage) {
            $rank = $current_rank;
            $previous_percentage = $percentage;
        }
        if ($s_id === $student_id) {
            return $rank;
        }
    }

    return null; // Student not found in class ranking (should not happen if student is in class_students)
}


