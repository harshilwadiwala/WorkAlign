<?php
require_once 'config.php';

$conn = connectDB();

// Check total payroll records
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM payroll");
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
echo "Total payroll records: " . $result['count'] . "\n";

// Check if there are any records
$stmt = $conn->prepare("SELECT * FROM payroll LIMIT 5");
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($records)) {
    echo "No payroll records found in database.\n";
} else {
    echo "Sample records:\n";
    foreach ($records as $record) {
        echo "ID: {$record['id']}, Employee: {$record['employee_id']}, Month: {$record['month']}, Status: {$record['status']}\n";
    }
}

// Check if there are employees with salary structure
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM salary_structure");
$stmt->execute();
$salary_count = $stmt->get_result()->fetch_assoc();
echo "Total salary structure records: " . $salary_count['count'] . "\n";

// Check attendance records for current month
$current_month = date('Y-m');
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM attendance WHERE DATE_FORMAT(date, '%Y-%m') = ?");
$stmt->bind_param("s", $current_month);
$stmt->execute();
$attendance_count = $stmt->get_result()->fetch_assoc();
echo "Attendance records for current month ($current_month): " . $attendance_count['count'] . "\n";

$conn->close();
?>
