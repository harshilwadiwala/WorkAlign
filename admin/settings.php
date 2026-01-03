<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!isAdmin()) {
    redirect('../employee/dashboard.php');
}

$conn = connectDB();

$errors = [];
$success = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = sanitizeInput($_POST['company_name']);
    $company_email = sanitizeInput($_POST['company_email']);
    $company_phone = sanitizeInput($_POST['company_phone']);
    $company_address = sanitizeInput($_POST['company_address']);
    $working_days = sanitizeInput($_POST['working_days']);
    $working_hours_start = sanitizeInput($_POST['working_hours_start']);
    $working_hours_end = sanitizeInput($_POST['working_hours_end']);
    $leave_policy_paid = intval($_POST['leave_policy_paid']);
    $leave_policy_sick = intval($_POST['leave_policy_sick']);
    
    // Validation
    if (empty($company_name)) $errors[] = "Company name is required";
    if (empty($company_email)) $errors[] = "Company email is required";
    if (!filter_var($company_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid company email format";
    if (empty($working_days)) $errors[] = "Working days is required";
    if (empty($working_hours_start)) $errors[] = "Working hours start is required";
    if (empty($working_hours_end)) $errors[] = "Working hours end is required";
    
    if (empty($errors)) {
        // For demo purposes, we'll just show success message
        // In a real application, you would save these to a settings table
        $success = "Settings updated successfully!";
    }
}

// Get current settings (for demo, using defaults)
$settings = [
    'company_name' => 'WorkAlign Company',
    'company_email' => 'hr@workalign.com',
    'company_phone' => '+1 234 567 8900',
    'company_address' => '123 Business Street, City, State 12345',
    'working_days' => '5',
    'working_hours_start' => '09:00',
    'working_hours_end' => '18:00',
    'leave_policy_paid' => '12',
    'leave_policy_sick' => '8'
];

$conn->close();
?>

<?php include '../header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2><i class="fas fa-cog me-2"></i>System Settings</h2>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-building me-2"></i>Company Information</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($settings['company_name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_email" class="form-label">Company Email</label>
                                <input type="email" class="form-control" id="company_email" name="company_email" value="<?php echo htmlspecialchars($settings['company_email']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_phone" class="form-label">Company Phone</label>
                                <input type="tel" class="form-control" id="company_phone" name="company_phone" value="<?php echo htmlspecialchars($settings['company_phone']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_address" class="form-label">Company Address</label>
                                <input type="text" class="form-control" id="company_address" name="company_address" value="<?php echo htmlspecialchars($settings['company_address']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6><i class="fas fa-clock me-2"></i>Working Hours</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="working_days" class="form-label">Working Days per Week</label>
                                <select class="form-select" id="working_days" name="working_days" required>
                                    <option value="5" <?php echo $settings['working_days'] === '5' ? 'selected' : ''; ?>>5 Days</option>
                                    <option value="6" <?php echo $settings['working_days'] === '6' ? 'selected' : ''; ?>>6 Days</option>
                                    <option value="7" <?php echo $settings['working_days'] === '7' ? 'selected' : ''; ?>>7 Days</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="working_hours_start" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="working_hours_start" name="working_hours_start" value="<?php echo htmlspecialchars($settings['working_hours_start']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="working_hours_end" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="working_hours_end" name="working_hours_end" value="<?php echo htmlspecialchars($settings['working_hours_end']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6><i class="fas fa-plane me-2"></i>Leave Policy</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="leave_policy_paid" class="form-label">Paid Leave Days per Year</label>
                                <input type="number" class="form-control" id="leave_policy_paid" name="leave_policy_paid" value="<?php echo htmlspecialchars($settings['leave_policy_paid']); ?>" min="0" max="365" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="leave_policy_sick" class="form-label">Sick Leave Days per Year</label>
                                <input type="number" class="form-control" id="leave_policy_sick" name="leave_policy_sick" value="<?php echo htmlspecialchars($settings['leave_policy_sick']); ?>" min="0" max="365" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>System Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Application:</strong><br>
                    WorkAlign HR Management System
                </div>
                <div class="mb-3">
                    <strong>Version:</strong><br>
                    1.0.0
                </div>
                <div class="mb-3">
                    <strong>PHP Version:</strong><br>
                    <?php echo PHP_VERSION; ?>
                </div>
                <div class="mb-3">
                    <strong>Database:</strong><br>
                    MySQL/MariaDB
                </div>
                <div class="mb-3">
                    <strong>Last Backup:</strong><br>
                    <span class="text-muted">Not available in demo</span>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-shield-alt me-2"></i>Security Settings</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="email_notifications" checked>
                        <label class="form-check-label" for="email_notifications">
                            Email Notifications
                        </label>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="two_factor_auth">
                        <label class="form-check-label" for="two_factor_auth">
                            Two-Factor Authentication
                        </label>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="session_timeout" checked>
                        <label class="form-check-label" for="session_timeout">
                            Session Timeout
                        </label>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="password_policy" checked>
                        <label class="form-check-label" for="password_policy">
                            Strong Password Policy
                        </label>
                    </div>
                </div>
                
                <button class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-key me-2"></i>Change Security Settings
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
