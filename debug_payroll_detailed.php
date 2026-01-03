<?php
require_once 'config.php';

$conn = connectDB();

echo "<h2>Payroll Debug Investigation</h2>";

// Check if payroll records exist
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM payroll");
$stmt->execute();
$total_payroll = $stmt->get_result()->fetch_assoc()['count'];
echo "<p><strong>Total payroll records in database:</strong> $total_payroll</p>";

// Check salary structure details
$stmt = $conn->prepare("SELECT employee_id, basic_salary, hra, da, ta, effective_date FROM salary_structure");
$stmt->execute();
$salary_structures = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "<h3>Salary Structure Details:</h3>";
echo "<table border='1'>";
echo "<tr><th>Employee ID</th><th>Basic Salary</th><th>HRA</th><th>DA</th><th>TA</th><th>Effective Date</th></tr>";
foreach ($salary_structures as $salary) {
    echo "<tr>";
    echo "<td>{$salary['employee_id']}</td>";
    echo "<td>{$salary['basic_salary']}</td>";
    echo "<td>{$salary['hra']}</td>";
    echo "<td>{$salary['da']}</td>";
    echo "<td>{$salary['ta']}</td>";
    echo "<td>{$salary['effective_date']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check attendance details for January 2026
$stmt = $conn->prepare("SELECT employee_id, COUNT(*) as days, GROUP_CONCAT(DISTINCT date) as dates FROM attendance WHERE DATE_FORMAT(date, '%Y-%m') = '2026-01' GROUP BY employee_id");
$stmt->execute();
$attendance_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "<h3>Attendance Details (January 2026):</h3>";
echo "<table border='1'>";
echo "<tr><th>Employee ID</th><th>Days</th><th>Dates</th></tr>";
foreach ($attendance_data as $att) {
    echo "<tr>";
    echo "<td>{$att['employee_id']}</td>";
    echo "<td>{$att['days']}</td>";
    echo "<td>{$att['dates']}</td>";
    echo "</tr>";
}
echo "</table>";

// Try to manually process payroll for one employee to see what happens
echo "<h3>Manual Payroll Processing Test:</h3>";

if (!empty($salary_structures) && !empty($attendance_data)) {
    $emp_id = $salary_structures[0]['employee_id'];
    $month = '2026-01';
    
    echo "<p>Testing payroll processing for employee: $emp_id</p>";
    
    // Get salary structure
    $stmt_salary = $conn->prepare("SELECT * FROM salary_structure WHERE employee_id = ? AND effective_date <= '2026-01-01' ORDER BY effective_date DESC LIMIT 1");
    $stmt_salary->bind_param("s", $emp_id);
    $stmt_salary->execute();
    $salary = $stmt_salary->get_result()->fetch_assoc();
    
    if ($salary) {
        echo "<p>✅ Salary structure found</p>";
        
        // Get attendance
        $stmt_att = $conn->prepare("SELECT COUNT(*) as working_days, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days FROM attendance WHERE employee_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
        $stmt_att->bind_param("ss", $emp_id, $month);
        $stmt_att->execute();
        $attendance = $stmt_att->get_result()->fetch_assoc();
        
        echo "<p>Working days: {$attendance['working_days']}, Present days: {$attendance['present_days']}</p>";
        
        if ($attendance['working_days'] > 0) {
            echo "<p>✅ Attendance data found</p>";
            
            // Check if payroll already exists
            $stmt_check = $conn->prepare("SELECT id FROM payroll WHERE employee_id = ? AND month = ?");
            $stmt_check->bind_param("ss", $emp_id, $month);
            $stmt_check->execute();
            $existing = $stmt_check->get_result()->num_rows;
            
            if ($existing == 0) {
                echo "<p>✅ No existing payroll found</p>";
                
                // Try to insert
                $stmt_insert = $conn->prepare("INSERT INTO payroll (employee_id, month, basic_salary, hra, da, ta, pf_deduction, tax_deduction, other_deductions, working_days, present_days, leave_days, absent_days, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'processed')");
                $stmt_insert->bind_param("ssddddddsiiss", 
                    $emp_id, $month, 
                    $salary['basic_salary'], $salary['hra'], $salary['da'], $salary['ta'], 
                    $salary['pf_deduction'], $salary['tax_deduction'], $salary['other_deductions'],
                    $attendance['working_days'], $attendance['present_days'], 0, 0
                );
                
                if ($stmt_insert->execute()) {
                    echo "<p style='color: green;'>✅ SUCCESS: Payroll record created for $emp_id</p>";
                } else {
                    echo "<p style='color: red;'>❌ FAILED: " . $stmt_insert->error . "</p>";
                }
                $stmt_insert->close();
            } else {
                echo "<p>❌ Payroll already exists</p>";
            }
        } else {
            echo "<p>❌ No attendance data</p>";
        }
        $stmt_att->close();
    } else {
        echo "<p>❌ No salary structure found</p>";
    }
    $stmt_salary->close();
}

$conn->close();
?>
