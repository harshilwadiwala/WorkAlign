$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Initialize popovers
    $('[data-bs-toggle="popover"]').popover();
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Form validation
    $('form').on('submit', function(e) {
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        
        // Show loading state
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Loading...');
        
        // Reset after 3 seconds if form is still processing
        setTimeout(function() {
            submitBtn.prop('disabled', false).html(submitBtn.data('original-text'));
        }, 3000);
    });
    
    // Store original button text
    $('button[type="submit"]').each(function() {
        $(this).data('original-text', $(this).html());
    });
    
    // Confirm delete actions
    $('.delete-btn').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
    
    // Dynamic form field validation
    $('.form-control').on('input', function() {
        var field = $(this);
        var value = field.val();
        var feedback = field.siblings('.invalid-feedback');
        
        if (field.hasClass('required') && value.trim() === '') {
            field.addClass('is-invalid');
            if (feedback.length === 0) {
                field.after('<div class="invalid-feedback">This field is required.</div>');
            }
        } else {
            field.removeClass('is-invalid');
            feedback.remove();
        }
    });
    
    // Email validation
    $('.email-field').on('input', function() {
        var field = $(this);
        var value = field.val();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        var feedback = field.siblings('.invalid-feedback');
        
        if (!emailRegex.test(value)) {
            field.addClass('is-invalid');
            if (feedback.length === 0) {
                field.after('<div class="invalid-feedback">Please enter a valid email address.</div>');
            }
        } else {
            field.removeClass('is-invalid');
            feedback.remove();
        }
    });
    
    // Phone number validation
    $('.phone-field').on('input', function() {
        var field = $(this);
        var value = field.val();
        var phoneRegex = /^[0-9]{10}$/;
        var feedback = field.siblings('.invalid-feedback');
        
        if (!phoneRegex.test(value)) {
            field.addClass('is-invalid');
            if (feedback.length === 0) {
                field.after('<div class="invalid-feedback">Please enter a valid 10-digit phone number.</div>');
            }
        } else {
            field.removeClass('is-invalid');
            feedback.remove();
        }
    });
    
    // Password strength indicator
    $('.password-field').on('input', function() {
        var field = $(this);
        var value = field.val();
        var strengthIndicator = field.siblings('.password-strength');
        
        if (strengthIndicator.length === 0) {
            field.after('<div class="password-strength mt-2"></div>');
            strengthIndicator = field.siblings('.password-strength');
        }
        
        var strength = getPasswordStrength(value);
        strengthIndicator.removeClass('weak medium strong').addClass(strength.class);
        strengthIndicator.html(strength.text);
    });
    
    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        var field = $(this).siblings('.password-field');
        var icon = $(this).find('i');
        
        if (field.attr('type') === 'password') {
            field.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            field.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Date picker initialization
    $('.date-picker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
    
    // Time picker initialization
    $('.time-picker').timepicker({
        showMeridian: false,
        showInputs: false,
        minuteStep: 5
    });
    
    // AJAX form submission
    $('.ajax-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var url = form.attr('action');
        var method = form.attr('method') || 'POST';
        var data = form.serialize();
        var submitBtn = form.find('button[type="submit"]');
        
        // Show loading state
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Loading...');
        
        $.ajax({
            url: url,
            method: method,
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert(response.message, 'success');
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 2000);
                    }
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function() {
                showAlert('An error occurred. Please try again.', 'danger');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(submitBtn.data('original-text'));
            }
        });
    });
    
    // Search functionality
    $('.search-input').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        var table = $(this).data('table');
        
        if (table) {
            $('#' + table + ' tbody tr').each(function() {
                var row = $(this);
                var text = row.text().toLowerCase();
                
                if (text.includes(searchTerm)) {
                    row.show();
                } else {
                    row.hide();
                }
            });
        }
    });
    
    // Print functionality
    $('.print-btn').on('click', function() {
        var printContent = $(this).data('print');
        
        if (printContent) {
            var printWindow = window.open('', '_blank');
            var content = $('#' + printContent).html();
            
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Print</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            table { width: 100%; border-collapse: collapse; }
                            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                            th { background-color: #f2f2f2; }
                        </style>
                    </head>
                    <body>
                        ${content}
                    </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.print();
        }
    });
    
    // Export to CSV
    $('.export-csv').on('click', function() {
        var tableId = $(this).data('table');
        var table = $('#' + tableId);
        
        if (table.length) {
            var csv = tableToCSV(table);
            downloadCSV(csv, 'export.csv');
        }
    });
});

function getPasswordStrength(password) {
    var strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    if (strength < 3) {
        return { class: 'weak text-danger', text: 'Weak password' };
    } else if (strength < 5) {
        return { class: 'medium text-warning', text: 'Medium strength' };
    } else {
        return { class: 'strong text-success', text: 'Strong password' };
    }
}

function showAlert(message, type) {
    var alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show alert-custom" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('.container-fluid').prepend(alertHtml);
    
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
}

function tableToCSV(table) {
    var csv = [];
    var rows = table.find('tr');
    
    rows.each(function() {
        var row = [];
        $(this).find('th, td').each(function() {
            var text = $(this).text().trim();
            if (text.includes(',')) {
                text = '"' + text + '"';
            }
            row.push(text);
        });
        csv.push(row.join(','));
    });
    
    return csv.join('\n');
}

function downloadCSV(csv, filename) {
    var blob = new Blob([csv], { type: 'text/csv' });
    var url = window.URL.createObjectURL(blob);
    var a = document.createElement('a');
    
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR'
    }).format(amount);
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatDateTime(datetime) {
    return new Date(datetime).toLocaleString('en-IN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
