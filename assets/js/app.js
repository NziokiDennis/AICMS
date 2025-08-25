// App-wide JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide success/info alerts after 5s
    document.querySelectorAll('.alert.alert-success, .alert.alert-info')
        .forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });

    // Form validation enhancement
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                showAlert('Please fill in all required fields.', 'danger');
            }
        });
    });

    // Utility: show alerts
    window.showAlert = function(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    };

    // Utility: loading state on buttons
    window.setButtonLoading = function(button, loading = true) {
        if (loading) {
            if (!button.getAttribute('data-original-text')) {
                button.setAttribute('data-original-text', button.innerHTML);
            }
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
        } else {
            button.disabled = false;
            button.innerHTML = button.getAttribute('data-original-text');
        }
    };
});

// Confirmation dialog
window.confirmDelete = function(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
};

// Format dates
window.formatDate = function(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};
