export default class FormValidator {
    #form;
    #fields;

    constructor(formId) {
        this.#form = document.getElementById(formId);
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
            field.addEventListener('input', () => this.#validateField(field));
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
        const feedback = field.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = '';
        }

        if (field.required && !value) {
            errorMessage = `${this.#getFieldLabel(field)} is required.`;
        } else if (name === 'email' && value && !this.#isValidEmail(value)) {
            errorMessage = "Email address is invalid.";
        } else if (name === 'password' && value && value.length < 8) {
            errorMessage = "Password must be at least 8 characters long, and contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
        } else if (name === 'username' && value && (value.length < 3 || value.length > 50)) {
            errorMessage = "Username must be between 3 and 50 characters long.";
        } else if ((name === 'first_name' || name === 'last_name') && value && value.length > 32) {
            errorMessage = `${this.#getFieldLabel(field)} must be 32 characters or less.`;
        } else if (name === 'phone_number' && value && !this.#isValidPhone(value)) {
            errorMessage = "Phone number is invalid.";
        }

        if (errorMessage) {
            field.classList.add('is-invalid');
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.textContent = errorMessage;
            }
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
}