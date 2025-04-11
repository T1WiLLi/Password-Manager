import FormValidator from "./FormValidator.js";
import DateTimeDisplay from "./DateTimeDisplay.js";
import PasswordSearch from "./PasswordSearch.js";
import ComponentManager from "./ComponentManager.js";

export default class Application {
    #configurations;
    #passwordSearch;
    #componentManager;

    constructor(configurations) {
        this.#configurations = configurations;
    }

    initialize() {
        this.#enableTooltips();
        this.#initializeFormValidation();
        new DateTimeDisplay("date-time");
        this.#initializePasswordSearch();
        this.#initializeComponentManager();
        this.#setupQuickActions();
    }

    #enableTooltips() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].forEach(el => new bootstrap.Tooltip(el));
    }

    #initializeFormValidation() {
        if (document.getElementById("login-form")) {
            new FormValidator("login-form");
        }
        if (document.getElementById("register-form")) {
            new FormValidator("register-form");
        }
    }

    #initializePasswordSearch() {
        if (document.getElementById("passwordSearch")) {
            this.#passwordSearch = new PasswordSearch("passwordSearch", ".table tbody");
            const resetButton = document.querySelector('.reset-search');
            if (resetButton) {
                resetButton.addEventListener('click', () => {
                    this.#passwordSearch.resetSearch();
                });
            }
        }
    }

    #initializeComponentManager() {
        const tabs = {
            'passwords-link': 'component-passwords',
            'sharing-link': 'component-sharing',
            'groups-link': 'component-groups'
        };
        this.#componentManager = new ComponentManager({
            tabs: tabs,
            passwordSearch: this.#passwordSearch
        });
    }

    #setupQuickActions() {
        const addPasswordAction = document.querySelector('.action-item[data-action="add-password"]');
        if (addPasswordAction) {
            addPasswordAction.addEventListener('click', () => {
                this.#componentManager.switchToComponentById('component-passwords');
                const addPasswordForm = document.getElementById('addPasswordForm');
                if (addPasswordForm) {
                    const collapse = new bootstrap.Collapse(addPasswordForm, {
                        toggle: false
                    });
                    collapse.show();
                    if (this.#passwordSearch) {
                        this.#passwordSearch.resetSearch();
                    }
                }
            });
        }
    }
}