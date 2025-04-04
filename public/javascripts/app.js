import FormValidator from "./FormValidator.js";
import DateTimeDisplay from "./DateTimeDisplay.js";
export default class Application {

    #configurations;

    constructor(configurations) {
        this.#configurations = configurations;
    }

    initialize() {
        this.#enableTooltips();
        this.#initializeFormValidation();
        new DateTimeDisplay("date-time");
    }

    #enableTooltips() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
    }

    #initializeFormValidation() {
        if (document.getElementById("login-form")) {
            new FormValidator("login-form");
        }

        if (document.getElementById("register-form")) {
            new FormValidator("register-form");
        }
    }
}
