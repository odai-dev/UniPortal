// Shared form enhancement functionality for University Student Portal

// CAPTCHA refresh functionality - automatically refresh on page load
document.addEventListener('DOMContentLoaded', function() {
    const captchaImage = document.getElementById('captcha_image');
    if (captchaImage) {
        captchaImage.src = 'captcha.php?' + Math.random();
    }
});

// Keyboard arrow navigation between form inputs
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
        e.preventDefault();
        
        // Get all focusable form elements in order
        const formElements = Array.from(document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], input[type="checkbox"], select, textarea, button[type="submit"]'));
        const currentIndex = formElements.indexOf(document.activeElement);
        
        if (currentIndex !== -1) {
            let nextIndex;
            if (e.key === 'ArrowDown') {
                nextIndex = (currentIndex + 1) % formElements.length;
            } else { // ArrowUp
                nextIndex = currentIndex === 0 ? formElements.length - 1 : currentIndex - 1;
            }
            formElements[nextIndex].focus();
        }
    }
});

// Password strength indicator (for forms with password fields)
document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            const password = this.value;
            const hasLetters = /[a-zA-Z]/.test(password);
            const hasNumbers = /\d/.test(password);
            const hasSymbols = /[\W_]/.test(password);
            const isLongEnough = password.length >= 8;
            
            const isValid = hasLetters && hasNumbers && hasSymbols && isLongEnough;
            
            if (password.length > 0) {
                if (isValid) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
    }
});

// Confirm password validation (for forms with confirm password fields)
document.addEventListener('DOMContentLoaded', function() {
    const confirmPasswordField = document.getElementById('confirm_password');
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
    }
});