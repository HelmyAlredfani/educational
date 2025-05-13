<?php
// Database connection details for the new system (alredfani_system_v2)
// IMPORTANT: Replace with your actual local or production database credentials.
$db_host = "localhost"; // Or your database host, e.g., 127.0.0.1
$db_name = "alredfani_system_v2";
$db_user = "root"; // Replace with your database username
$db_pass = ""; // Replace with your database password, leave empty if no password for root in local dev
$charset = "utf8mb4";

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (\PDOException $e) {
    // For development, show error. For production, log error and show generic message.
    error_log("Database Connection Error: " . $e->getMessage());
    die("فشل الاتصال بقاعدة البيانات. يرجى المحاولة مرة أخرى لاحقًا أو الاتصال بمسؤول النظام.");
}
?>
