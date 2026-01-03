<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if employee needs to checkout
if (isEmployee()) {
    $conn = connectDB();
    $employee_id = $_SESSION['employee_id'];
    $today = date('Y-m-d');
    
    // Check if employee has checked in today but not checked out
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ? AND check_in IS NOT NULL AND check_out IS NULL");
    $stmt->bind_param("ss", $employee_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Store checkout required message
        $_SESSION['checkout_required'] = "You must check out before logging out. Please go to attendance page to check out.";
        $stmt->close();
        $conn->close();
        redirect('employee/attendance.php');
    }
    
    $stmt->close();
    $conn->close();
}

// Proceed with logout
session_destroy();

redirect('login.php');
?>
