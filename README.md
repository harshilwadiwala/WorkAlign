# Dayflow HR Management System

A comprehensive HR management system built with PHP and MySQL that streamlines employee attendance, leave management, payroll, and administrative tasks.

## Features

### Employee Portal
- **Authentication & Profile Management**: Secure login, registration, and profile editing
- **Attendance Management**: Daily check-in/check-out with attendance history
- **Leave Management**: Apply for paid, sick, and unpaid leave with status tracking
- **Payroll View**: View monthly salary breakdowns and payment history
- **Dashboard**: Quick access to all employee functions with real-time statistics

### Admin Portal
- **Employee Management**: Add, edit, activate/deactivate employee accounts
- **Attendance Oversight**: View and correct attendance records for all employees
- **Leave Approval Workflow**: Review, approve, or reject leave requests with comments
- **Payroll Management**: Update salary structures and process monthly payroll
- **Comprehensive Dashboard**: Company-wide statistics and quick actions

## Technology Stack

- **Backend**: PHP 8+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: Bootstrap 5
- **Icons**: Font Awesome 6

## Installation

### Prerequisites
- XAMPP/WAMP/MAMP (or similar PHP/MySQL environment)
- PHP 8.0 or higher
- MySQL 5.7 or higher

### Setup Instructions

1. **Clone/Download the Project**
   ```bash
   # Place the project in your web server's root directory
   # For XAMPP: C:/xampp/htdocs/WorkAlign/
   ```

2. **Database Setup**
   - Start Apache and MySQL from XAMPP control panel
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the `database.sql` file to create the database and tables
   - Or run the SQL commands manually in phpMyAdmin

3. **Configure Database Connection**
   - Open `config.php`
   - Update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'dayflow_hr');
   ```

4. **Create Upload Directory**
   - Create `uploads/profile/` directory for profile pictures
   - Set appropriate permissions (755)

5. **Access the Application**
   - Open browser and navigate to: `http://localhost/WorkAlign/`

## Default Login Credentials

### Admin Account
- **Email**: admin@dayflow.com
- **Password**: password

### Employee Account
- **Email**: john.doe@company.com
- **Password**: password

## Project Structure

```
WorkAlign/
├── admin/                  # Admin panel pages
│   ├── dashboard.php      # Admin dashboard
│   ├── employees.php      # Employee management
│   ├── attendance.php     # Attendance oversight
│   ├── leave.php          # Leave approval
│   └── payroll.php        # Payroll management
├── employee/              # Employee panel pages
│   ├── dashboard.php      # Employee dashboard
│   ├── profile.php        # Profile management
│   ├── attendance.php     # Attendance tracking
│   ├── leave.php          # Leave application
│   └── payroll.php        # Payroll view
├── assets/                # Static assets
│   ├── css/
│   │   └── style.css      # Custom styles
│   └── js/
│       └── script.js      # JavaScript functionality
├── uploads/               # File uploads
│   └── profile/           # Profile pictures
├── config.php             # Configuration and helper functions
├── database.sql           # Database schema
├── index.php              # Landing page
├── login.php              # Login page
├── signup.php             # Registration page
├── logout.php             # Logout handler
├── header.php             # Common header
└── footer.php             # Common footer
```

## Database Schema

The system uses the following main tables:

- **employees**: Employee information and authentication
- **attendance**: Daily attendance records
- **leave_requests**: Leave applications and approvals
- **salary_structure**: Employee salary components
- **payroll**: Monthly payroll records
- **leave_balance**: Annual leave balances
- **employee_documents**: Document management

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with bcrypt
- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Protection**: Input sanitization and output escaping
- **Session Management**: Secure session handling
- **Role-Based Access**: Employee and admin role separation

## Key Features Explained

### Attendance System
- Employees can check-in/check-out daily
- Automatic status calculation (present, absent, half-day, leave)
- Admin can correct attendance records
- Monthly and weekly attendance summaries

### Leave Management
- Three types of leave: Paid, Sick, Unpaid
- Annual leave balance tracking
- Overlap prevention
- Admin approval workflow with comments

### Payroll System
- Flexible salary structure (Basic + HRA + DA + TA - Deductions)
- Monthly payroll processing based on attendance
- Payment status tracking (Pending, Processed, Paid)
- Comprehensive salary reports

## Customization

### Adding New Features
- Follow the existing code structure and naming conventions
- Use prepared statements for database operations
- Implement proper validation and error handling
- Follow the responsive design patterns

### Styling
- Custom CSS is in `assets/css/style.css`
- Bootstrap 5 components are used throughout
- Responsive design for mobile devices
- Custom color scheme and animations

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check MySQL server is running
   - Verify database credentials in `config.php`
   - Ensure database `dayflow_hr` exists

2. **File Upload Issues**
   - Check `uploads/profile/` directory permissions
   - Verify PHP upload limits in `php.ini`
   - Ensure proper file size limits

3. **Session Issues**
   - Check session save path permissions
   - Verify session timeout settings
   - Clear browser cookies if needed

## Future Enhancements

- Email notifications for leave approvals
- Advanced reporting and analytics
- Document management system
- Biometric attendance integration
- Mobile app development
- API endpoints for third-party integration

## Support

For issues and questions:
1. Check the troubleshooting section
2. Verify all installation steps
3. Review error logs in XAMPP
4. Test with demo credentials first

## License

This project is for educational and demonstration purposes. Feel free to modify and enhance according to your requirements.

---

**Note**: This system is designed as a complete HR management solution suitable for small to medium-sized organizations. All core HR functions are implemented with security best practices and user-friendly interfaces.
