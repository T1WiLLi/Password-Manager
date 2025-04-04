export default class FormValidator {
    #form;
    #fields;
    #tooltips = new Map();
    #isLoginForm = false;

    constructor(formId) {
        this.#form = document.getElementById(formId);

        this.#isLoginForm = formId === 'login-form';

        this.#fields = this.#form.querySelectorAll('input');
        this.#initialize();
    }

    #initialize() {
        if (!this.#form) return;

        this.#form.addEventListener('submit', (event) => {
            if (!this.#validate()) {
                event.preventDefault();
                event.stopPropagation();
            }
        });

        this.#fields.forEach(field => {
            field.setAttribute('data-bs-toggle', 'tooltip');
            field.setAttribute('data-bs-placement', 'right');
            field.setAttribute('data-bs-trigger', 'manual');
            field.addEventListener('focus', () => this.#handleFocus(field));
            field.addEventListener('blur', () => this.#handleBlur(field));
            field.addEventListener('input', () => this.#handleInput(field));
        });
    }

    #validate() {
        let isValid = true;
        this.#fields.forEach(field => {
            if (!this.#validateField(field)) {
                isValid = false;
            }
        });
        return isValid;
    }

    #validateField(field) {
        const name = field.name;
        const value = field.value.trim();
        let errorMessage = '';

        field.classList.remove('is-valid', 'is-invalid');

        if (field.required && !value) {
            errorMessage = `${this.#getFieldLabel(field)} is required.`;
        }
        else if (!this.#isLoginForm) {
            if (name === 'email' && value && !this.#isValidEmail(value)) {
                errorMessage = "Email address is invalid.";
            } else if (name === 'password' && value && !this.#isValidPassword(value)) {
                errorMessage = "Password must be at least 8 characters long, and contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
            } else if (name === 'username' && value && (value.length < 3 || value.length > 50)) {
                errorMessage = "Username must be between 3 and 50 characters long.";
            } else if ((name === 'first_name' || name === 'last_name') && value && value.length > 32) {
                errorMessage = `${this.#getFieldLabel(field)} must be 32 characters or less.`;
            } else if (name === 'phone_number' && !this.#isValidPhone(value)) {
                errorMessage = value ? "Phone number is invalid." : "Phone number is required.";
            }
        }

        if (errorMessage) {
            field.classList.add('is-invalid');
            this.#setTooltip(field, errorMessage);
            return false;
        } else {
            field.classList.add('is-valid');
            return true;
        }
    }

    #getFieldLabel(field) {
        const label = document.querySelector(`label[for="${field.id}"]`);
        return label ? label.textContent : field.name;
    }

    #isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    #isValidPhone(phone) {
        const phoneRegex = /^\+?[1-9]\d{1,14}$/;
        return phoneRegex.test(phone);
    }

    #isValidPassword(password) {
        const minLength = password.length >= 8;
        const hasUppercase = /[A-Z]/.test(password);
        const hasLowercase = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        return minLength && hasUppercase && hasLowercase && hasNumber && hasSpecialChar;
    }

    #setTooltip(field, message) {
        let tooltip = this.#tooltips.get(field);
        if (!tooltip) {
            tooltip = new bootstrap.Tooltip(field, {
                trigger: 'manual',
                animation: false
            });
            this.#tooltips.set(field, tooltip);
        }
        field.setAttribute('data-bs-title', message);
        tooltip._config.title = message;
    }

    #showTooltip(field) {
        const tooltip = this.#tooltips.get(field);
        if (tooltip && field.classList.contains('is-invalid')) {
            tooltip.show();
        }
    }

    #hideTooltip(field) {
        const tooltip = this.#tooltips.get(field);
        if (tooltip) {
            tooltip.hide();
        }
    }

    #handleFocus(field) {
        this.#validateField(field);
        if (field.classList.contains('is-invalid')) {
            this.#showTooltip(field);
        }
    }

    #handleBlur(field) {
        this.#hideTooltip(field);
    }

    #handleInput(field) {
        this.#validateField(field);
        if (field.classList.contains('is-invalid') && document.activeElement === field) {
            this.#showTooltip(field);
        } else {
            this.#hideTooltip(field);
        }
    }

    destroy() {
        this.#fields.forEach(field => {
            const tooltip = this.#tooltips.get(field);
            if (tooltip) {
                tooltip.dispose();
                this.#tooltips.delete(field);
            }
        });
    }
}