<?php
require_once 'config.php';

$conn = connectDB();

echo "<h2>Quick Payroll Setup</h2>";

// Check current status
$stmt_emp = $conn->prepare("SELECT COUNT(*) as count FROM employees WHERE role = 'employee' AND is_active = 1");
$stmt_emp->execute();
$emp_count = $stmt_emp->get_result()->fetch_assoc()['count'];

$stmt_sal = $conn->prepare("SELECT COUNT(DISTINCT employee_id) as count FROM salary_structure");
$stmt_sal->execute();
$salary_count = $stmt_sal->get_result()->fetch_assoc()['count'];

$stmt_att = $conn->prepare("SELECT COUNT(DISTINCT employee_id) as count FROM attendance WHERE DATE_FORMAT(date, '%Y-%m') = '2026-01'");
$stmt_att->execute();
$attendance_count = $stmt_att->get_result()->fetch_assoc()['count'];

echo "<p><strong>Current Status:</strong></p>";
echo "<ul>";
echo "<li>Active Employees: $emp_count</li>";
echo "<li>With Salary Structure: $salary_count</li>";
echo "<li>With Attendance (Jan 2026): $attendance_count</li>";
echo "</ul>";

// Add attendance if missing
if ($attendance_count < $emp_count) {
    echo "<h3>Adding Sample Attendance Data...</h3>";
    
    // Get active employees
    $stmt = $conn->prepare("SELECT employee_id FROM employees WHERE role = 'employee' AND is_active = 1");
    $stmt->execute();
    $employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $added_count = 0;
    foreach ($employees as $employee) {
        $emp_id = $employee['employee_id'];
        
        // Check if employee already has attendance for January
        $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM attendance WHERE employee_id = ? AND DATE_FORMAT(date, '%Y-%m') = '2026-01'");
        $stmt_check->bind_param("s", $emp_id);
        $stmt_check->execute();
        $existing = $stmt_check->get_result()->fetch_assoc()['count'];
        
        if ($existing == 0) {
            // Add attendance for current month (few days)
            $dates = ['2026-01-01', '2026-01-02', '2026-01-03', '2026-01-06', '2026-01-07'];
            
            foreach ($dates as $date) {
                $stmt_att = $conn->prepare("INSERT IGNORE INTO attendance (employee_id, date, check_in, check_out, status) VALUES (?, ?, '09:00:00', '18:00:00', 'present')");
                $stmt_att->bind_param("ss", $emp_id, $date);
                $stmt_att->execute();
                $stmt_att->close();
            }
            $added_count++;
            echo "<p>Added attendance for employee: $emp_id</p>";
        }
        $stmt_check->close();
    }
    $stmt->close();
    
    echo "<p><strong>Added attendance for $added_count employees</strong></p>";
}

echo "<h3>Ready to Process Payroll!</h3>";
echo "<p><a href='admin/payroll.php'>Go to Payroll Management</a> and click 'Process Payroll' for January 2026.</p>";

$conn->close();
?>
