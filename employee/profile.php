<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!isEmployee()) {
    redirect('../admin/dashboard.php');
}

$conn = connectDB();
$employee_id = $_SESSION['employee_id'];

// Get employee details
$stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

// Initialize variables to prevent undefined array key warnings
$first_name = $employee['first_name'] ?? '';
$last_name = $employee['last_name'] ?? '';
$email = $employee['email'] ?? '';
$phone = $employee['phone'] ?? '';
$address = $employee['address'] ?? '';
$department = $employee['department'] ?? '';
$designation = $employee['designation'] ?? '';
$date_of_joining = $employee['date_of_joining'] ?? '';
$profile_picture = $employee['profile_picture'] ?? '';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile picture upload separately
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $upload_result = uploadFile($_FILES['profile_picture'], 'uploads/profile/');
        
        if ($upload_result['success']) {
            // Delete old profile picture if exists
            if ($profile_picture && file_exists('uploads/profile/' . $profile_picture)) {
                unlink('uploads/profile/' . $profile_picture);
            }
            
            $profile_picture = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }
    
    // Handle form data
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    
    // Validation
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    
    if (empty($errors)) {
        $stmt_update = $conn->prepare("UPDATE employees SET first_name = ?, last_name = ?, phone = ?, address = ?, profile_picture = ? WHERE employee_id = ?");
        $stmt_update->bind_param("ssssss", $first_name, $last_name, $phone, $address, $profile_picture, $employee_id);
        
        if ($stmt_update->execute()) {
            $success = "Profile updated successfully!";
            
            // Update session
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['profile_picture'] = $profile_picture;
            
            // Refresh employee data
            $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
            $stmt->bind_param("s", $employee_id);
            $stmt->execute();
            $employee = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Failed to update profile. Please try again.";
        }
        
        $stmt_update->close();
    }
}

$stmt->close();
$conn->close();
?>

<?php include '../header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2><i class="fas fa-user me-2"></i>My Profile</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-user-circle me-2"></i>Profile Picture</h5>
            </div>
            <div class="card-body text-center">
                <img src="<?php echo !empty($employee['profile_picture']) ? 'https://via.placeholder.com/150' : '../uploads/profile/' . htmlspecialchars($employee['profile_picture']); ?>" 
                     alt="Profile Picture" 
                     class="profile-avatar mb-3"
                     onerror="this.src='https://via.placeholder.com/150';">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Change Profile Picture</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-upload me-2"></i>Upload Picture
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-edit me-2"></i>Edit Profile</h5>
            </div>
            <div class="card-body">
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
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_id" class="form-label">Employee ID</label>
                                <input type="text" class="form-control" id="employee_id" value="<?php echo htmlspecialchars($employee['employee_id']); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($employee['email']); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control phone-field" id="phone" name="phone" value="<?php echo htmlspecialchars($employee['phone']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" value="<?php echo htmlspecialchars($employee['department']); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="designation" class="form-label">Designation</label>
                                <input type="text" class="form-control" id="designation" value="<?php echo htmlspecialchars($employee['designation']); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_of_joining" class="form-label">Date of Joining</label>
                                <input type="date" class="form-control" id="date_of_joining" value="<?php echo htmlspecialchars($employee['date_of_joining']); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($employee['address']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
