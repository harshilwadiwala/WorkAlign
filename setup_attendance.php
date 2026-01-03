<?php
require_once 'config.php';

$conn = connectDB();

// Get active employees
$stmt = $conn->prepare("SELECT employee_id FROM employees WHERE role = 'employee' AND is_active = 1");
$stmt->execute();
$employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Add sample attendance for January 2026
$current_month = '2026-01';
$days_in_month = 31;

foreach ($employees as $employee) {
    $emp_id = $employee['employee_id'];
    
    // Add attendance for working days (let's say 22 working days)
    for ($day = 1; $day <= $days_in_month; $day++) {
        // Skip weekends (simplified - assuming Saturday=6, Sunday=7)
        $date_info = date_create("2026-01-$day");
        $day_of_week = date_format($date_info, 'N');
        
        if ($day_of_week <= 5) { // Monday to Friday
            $date = "2026-01-" . str_pad($day, 2, '0', STR_PAD_LEFT);
            
            // Randomly assign present/absent/leave
            $status = 'present';
            $rand = rand(1, 20);
            if ($rand == 1) $status = 'leave';
            if ($rand == 2) $status = 'absent';
            
            // Insert attendance
            $check_in = $status == 'present' ? '09:00:00' : null;
            $check_out = $status == 'present' ? '18:00:00' : null;
            
            $stmt_att = $conn->prepare("INSERT IGNORE INTO attendance (employee_id, date, check_in, check_out, status) VALUES (?, ?, ?, ?, ?)");
            $stmt_att->bind_param("sssss", $emp_id, $date, $check_in, $check_out, $status);
            $stmt_att->execute();
            $stmt_att->close();
        }
    }
}

echo "Sample attendance data added for " . count($employees) . " employees for January 2026\n";
$conn->close();
?>
