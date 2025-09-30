document.addEventListener('DOMContentLoaded', function() {
    const captchaImage = document.getElementById('captcha_image');
    if (captchaImage) {
        captchaImage.src = 'captcha.php?' + Math.random();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
        e.preventDefault();
        
        const formElements = Array.from(document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], input[type="checkbox"], select, textarea, button[type="submit"]'));
        const currentIndex = formElements.indexOf(document.activeElement);
        
        if (currentIndex !== -1) {
            let nextIndex;
            if (e.key === 'ArrowDown') {
                nextIndex = (currentIndex + 1) % formElements.length;
            } else {
                nextIndex = currentIndex === 0 ? formElements.length - 1 : currentIndex - 1;
            }
            formElements[nextIndex].focus();
        }
    }
});

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