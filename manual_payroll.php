<?php
require_once 'config.php';

$conn = connectDB();

if (isset($_POST['manual_process'])) {
    $month = sanitizeInput($_POST['month']);
    
    // Get all active employees
    $stmt = $conn->prepare("SELECT employee_id FROM employees WHERE role = 'employee' AND is_active = 1");
    $stmt->execute();
    $employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $processed = 0;
    $errors = [];
    
    foreach ($employees as $employee) {
        $emp_id = $employee['employee_id'];
        
        // Get salary structure
        $stmt_salary = $conn->prepare("SELECT * FROM salary_structure WHERE employee_id = ? ORDER BY effective_date DESC LIMIT 1");
        $stmt_salary->bind_param("s", $emp_id);
        $stmt_salary->execute();
        $salary = $stmt_salary->get_result()->fetch_assoc();
        
        if ($salary) {
            // Get attendance
            $stmt_att = $conn->prepare("SELECT COUNT(*) as working_days, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days, SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days, SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days FROM attendance WHERE employee_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
            $stmt_att->bind_param("ss", $emp_id, $month);
            $stmt_att->execute();
            $attendance = $stmt_att->get_result()->fetch_assoc();
            
            if ($attendance['working_days'] > 0) {
                // Check if exists
                $stmt_check = $conn->prepare("SELECT id FROM payroll WHERE employee_id = ? AND month = ?");
                $stmt_check->bind_param("ss", $emp_id, $month);
                $stmt_check->execute();
                
                if ($stmt_check->get_result()->num_rows == 0) {
                    // Insert payroll
                    $stmt_insert = $conn->prepare("INSERT INTO payroll (employee_id, month, basic_salary, hra, da, ta, pf_deduction, tax_deduction, other_deductions, working_days, present_days, leave_days, absent_days, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'processed')");
                    $stmt_insert->bind_param("ssddddddsiiss", 
                        $emp_id, $month,
                        $salary['basic_salary'], $salary['hra'], $salary['da'], $salary['ta'],
                        $salary['pf_deduction'], $salary['tax_deduction'], $salary['other_deductions'],
                        $attendance['working_days'], $attendance['present_days'], $attendance['leave_days'], $attendance['absent_days']
                    );
                    
                    if ($stmt_insert->execute()) {
                        $processed++;
                    } else {
                        $errors[] = "Failed to insert payroll for $emp_id: " . $stmt_insert->error;
                    }
                    $stmt_insert->close();
                }
                $stmt_check->close();
            } else {
                $errors[] = "No attendance data for $emp_id in $month";
            }
            $stmt_att->close();
        } else {
            $errors[] = "No salary structure for $emp_id";
        }
        $stmt_salary->close();
    }
    
    $stmt->close();
    
    echo "<h2>Manual Payroll Processing Results</h2>";
    echo "<p><strong>Processed:</strong> $processed employees</p>";
    
    if (!empty($errors)) {
        echo "<h3>Errors:</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
    }
    
    echo "<p><a href='admin/payroll.php'>Back to Payroll Management</a></p>";
} else {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manual Payroll Processing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Manual Payroll Processing</h2>
    
    <form method="POST">
        <div class="mb-3">
            <label for="month" class="form-label">Select Month</label>
            <input type="month" class="form-control" id="month" name="month" value="2026-01" required>
        </div>
        <button type="submit" name="manual_process" class="btn btn-primary">Process Payroll Manually</button>
    </form>
    
    <div class="mt-3">
        <a href="admin/payroll.php">Back to Payroll Management</a>
    </div>
</div>
</body>
</html>
<?php
}
$conn->close();
?>
