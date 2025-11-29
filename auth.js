// Authentication related JavaScript
class AuthHelper {
    static validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    static validatePassword(password) {
        return password.length >= 6;
    }

    static validateUsername(username) {
        return username.length >= 3 && /^[a-zA-Z0-9_]+$/.test(username);
    }

    static showError(field, message) {
        // Remove existing error
        this.clearFieldError(field);
        
        // Add error styling
        field.classList.add('is-invalid');
        
        // Create error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    }

    static clearFieldError(field) {
        field.classList.remove('is-invalid');
        const existingError = field.parentNode.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }
    }

    static clearAllErrors() {
        document.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });
        document.querySelectorAll('.invalid-feedback').forEach(error => {
            error.remove();
        });
    }

    static showSuccess(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
    }
}

// Real-time form validation
document.addEventListener('DOMContentLoaded', function() {
    // Login form validation
    const loginForm = document.querySelector('form[action*="login"]');
    if (loginForm) {
        const username = loginForm.querySelector('input[name="username"]');
        const password = loginForm.querySelector('input[name="password"]');
        
        if (username) {
            username.addEventListener('blur', function() {
                AuthHelper.clearFieldError(this);
                if (!this.value.trim()) {
                    AuthHelper.showError(this, 'Username is required');
                }
            });
        }
        
        if (password) {
            password.addEventListener('blur', function() {
                AuthHelper.clearFieldError(this);
                if (!this.value) {
                    AuthHelper.showError(this, 'Password is required');
                }
            });
        }
        
        loginForm.addEventListener('submit', function(e) {
            AuthHelper.clearAllErrors();
            
            let isValid = true;
            
            if (!username.value.trim()) {
                AuthHelper.showError(username, 'Username is required');
                isValid = false;
            }
            
            if (!password.value) {
                AuthHelper.showError(password, 'Password is required');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // Registration form validation
    const registerForm = document.querySelector('form[action*="register"]');
    if (registerForm) {
        const fullName = registerForm.querySelector('input[name="full_name"]');
        const username = registerForm.querySelector('input[name="username"]');
        const email = registerForm.querySelector('input[name="email"]');
        const password = registerForm.querySelector('input[name="password"]');
        
        // Real-time validation
        if (fullName) {
            fullName.addEventListener('input', function() {
                AuthHelper.clearFieldError(this);
                if (this.value.trim().length >= 2) {
                    AuthHelper.showSuccess(this);
                }
            });
        }
        
        if (username) {
            username.addEventListener('input', function() {
                AuthHelper.clearFieldError(this);
                if (AuthHelper.validateUsername(this.value)) {
                    AuthHelper.showSuccess(this);
                }
            });
        }
        
        if (email) {
            email.addEventListener('input', function() {
                AuthHelper.clearFieldError(this);
                if (AuthHelper.validateEmail(this.value)) {
                    AuthHelper.showSuccess(this);
                }
            });
        }
        
        if (password) {
            password.addEventListener('input', function() {
                AuthHelper.clearFieldError(this);
                if (AuthHelper.validatePassword(this.value)) {
                    AuthHelper.showSuccess(this);
                }
            });
        }
        
        registerForm.addEventListener('submit', function(e) {
            AuthHelper.clearAllErrors();
            
            let isValid = true;
            
            if (!fullName.value.trim()) {
                AuthHelper.showError(fullName, 'Full name is required');
                isValid = false;
            } else if (fullName.value.trim().length < 2) {
                AuthHelper.showError(fullName, 'Full name must be at least 2 characters');
                isValid = false;
            }
            
            if (!username.value.trim()) {
                AuthHelper.showError(username, 'Username is required');
                isValid = false;
            } else if (!AuthHelper.validateUsername(username.value)) {
                AuthHelper.showError(username, 'Username must be at least 3 characters and contain only letters, numbers, and underscores');
                isValid = false;
            }
            
            if (!email.value.trim()) {
                AuthHelper.showError(email, 'Email is required');
                isValid = false;
            } else if (!AuthHelper.validateEmail(email.value)) {
                AuthHelper.showError(email, 'Please enter a valid email address');
                isValid = false;
            }
            
            if (!password.value) {
                AuthHelper.showError(password, 'Password is required');
                isValid = false;
            } else if (!AuthHelper.validatePassword(password.value)) {
                AuthHelper.showError(password, 'Password must be at least 6 characters long');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
});

// Password strength indicator
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength++;
    
    return strength;
}