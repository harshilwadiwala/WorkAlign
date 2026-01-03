<?php
// Check for dark mode preference
$dark_mode = isset($_SESSION['dark_mode']) ? $_SESSION['dark_mode'] : false;
if (isset($_COOKIE['dark_mode'])) {
    $dark_mode = $_COOKIE['dark_mode'] === 'true';
}
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $dark_mode ? 'dark-mode' : 'light-mode'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkAlign - HR Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style-advanced.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #16a34a;
            --danger-color: #dc2626;
            --warning-color: #f59e0b;
            --info-color: #0891b2;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
            --gradient-primary: linear-gradient(135deg, #2563eb, #1d4ed8);
            --gradient-success: linear-gradient(135deg, #16a34a, #15803d);
            --gradient-danger: linear-gradient(135deg, #dc2626, #b91c1c);
            --gradient-warning: linear-gradient(135deg, #f59e0b, #d97706);
            --gradient-info: linear-gradient(135deg, #0891b2, #0e7490);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Dark Mode Variables */
        .dark-mode {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: #475569;
            --card-bg: rgba(30, 41, 59, 0.95);
            --navbar-bg: linear-gradient(135deg, #1e293b, #334155);
        }

        /* Light Mode Variables */
        .light-mode {
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --card-bg: rgba(255, 255, 255, 0.95);
            --navbar-bg: var(--gradient-primary);
        }

        /* Apply theme variables */
        body {
            background: var(--bg-secondary);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .navbar {
            background: var(--navbar-bg) !important;
            transition: all 0.3s ease;
        }

        .card {
            background: var(--card-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .form-control, .form-select {
            background: var(--bg-primary);
            color: var(--text-primary);
            border-color: var(--border-color);
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            background: var(--bg-primary);
            color: var(--text-primary);
            border-color: var(--primary-color);
        }

        .table {
            color: var(--text-primary);
        }

        .table thead th {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        .table tbody td {
            border-color: var(--border-color);
        }

        .table tbody tr:hover {
            background: var(--bg-secondary);
        }

        .modal-content {
            background: var(--card-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .dropdown-menu {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
        }

        .dropdown-item {
            color: var(--text-primary);
        }

        .dropdown-item:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .border {
            border-color: var(--border-color) !important;
        }

        /* Dark mode toggle switch */
        .theme-toggle {
            position: relative;
            width: 60px;
            height: 30px;
            background: var(--border-color);
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid var(--border-color);
        }

        .theme-toggle.active {
            background: var(--gradient-primary);
            border-color: var(--primary-color);
        }

        .theme-toggle-slider {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 22px;
            height: 22px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .theme-toggle.active .theme-toggle-slider {
            transform: translateX(30px);
        }

        .theme-toggle i {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Enhanced animations */
        .fade-in {
            animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(20px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        /* Smooth transitions for theme switching */
        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
    </style>
</head>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/WorkAlign/index.php">
                <i class="fas fa-briefcase me-2"></i>WorkAlign
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/WorkAlign/index.php">Home</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isEmployee()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/WorkAlign/employee/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/WorkAlign/employee/profile.php">Profile</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/WorkAlign/employee/attendance.php">Attendance</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/WorkAlign/employee/leave.php">Leave</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/WorkAlign/employee/payroll.php">Payroll</a>
                            </li>
                        <?php elseif (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/WorkAlign/admin/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/WorkAlign/admin/employees.php">Employees</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/WorkAlign/admin/attendance.php">Attendance</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/WorkAlign/admin/leave.php">Leave Management</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/WorkAlign/admin/payroll.php">Payroll</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/WorkAlign/admin/reports.php">Reports</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/WorkAlign/admin/settings.php">Settings</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <div class="nav-link d-flex align-items-center">
                            <div class="theme-toggle <?php echo $dark_mode ? 'active' : ''; ?>" onclick="toggleTheme()" title="Toggle theme">
                                <div class="theme-toggle-slider">
                                    <i class="fas fa-<?php echo $dark_mode ? 'moon' : 'sun'; ?>"></i>
                                </div>
                            </div>
                        </div>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/WorkAlign/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/WorkAlign/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/WorkAlign/signup.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const toggle = document.querySelector('.theme-toggle');
            const icon = toggle.querySelector('i');
            
            if (html.classList.contains('dark-mode')) {
                html.classList.remove('dark-mode');
                html.classList.add('light-mode');
                toggle.classList.remove('active');
                icon.className = 'fas fa-sun';
                document.cookie = 'dark_mode=false; path=/; max-age=31536000';
                <?php if (isLoggedIn()): ?>
                // Save to session for logged-in users
                fetch('/WorkAlign/api/theme.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({dark_mode: false})
                });
                <?php endif; ?>
            } else {
                html.classList.remove('light-mode');
                html.classList.add('dark-mode');
                toggle.classList.add('active');
                icon.className = 'fas fa-moon';
                document.cookie = 'dark_mode=true; path=/; max-age=31536000';
                <?php if (isLoggedIn()): ?>
                // Save to session for logged-in users
                fetch('/WorkAlign/api/theme.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({dark_mode: true})
                });
                <?php endif; ?>
            }
        }

        // Set initial theme based on cookie or system preference
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeCookie = document.cookie.split(';').find(cookie => cookie.trim().startsWith('dark_mode='));
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (darkModeCookie) {
                const isDark = darkModeCookie.split('=')[1] === 'true';
                if (isDark && !document.documentElement.classList.contains('dark-mode')) {
                    toggleTheme();
                }
            } else if (systemPrefersDark && !document.documentElement.classList.contains('dark-mode')) {
                toggleTheme();
            }
            
            // Advanced UI Interactions
            initAdvancedUI();
        });

        // Advanced UI Functions
        function initAdvancedUI() {
            // Navbar scroll effect
            const navbar = document.querySelector('.navbar');
            if (navbar) {
                window.addEventListener('scroll', function() {
                    if (window.scrollY > 50) {
                        navbar.classList.add('scrolled');
                    } else {
                        navbar.classList.remove('scrolled');
                    }
                });
            }

            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Add fade-in animation to elements
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            // Observe cards, stats, and other elements
            document.querySelectorAll('.card, .stats-card, .btn, .alert').forEach(el => {
                observer.observe(el);
            });

            // Enhanced form interactions
            document.querySelectorAll('.form-control, .form-select').forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });

            // Button ripple effect
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple');
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Loading states for buttons
            document.querySelectorAll('.btn[data-loading]').forEach(button => {
                button.addEventListener('click', function() {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Loading...';
                    this.disabled = true;
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 2000);
                });
            });
        }

        // Add ripple effect styles
        const style = document.createElement('style');
        style.textContent = `
            .btn {
                position: relative;
                overflow: hidden;
            }
            .ripple {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                transform: scale(0);
                animation: ripple-animation 0.6s ease-out;
                pointer-events: none;
            }
            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            .focused {
                transform: translateY(-2px);
            }
        `;
        document.head.appendChild(style);
    </script>

    <main class="container-fluid py-4">
