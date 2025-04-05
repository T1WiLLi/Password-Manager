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
        this.#setupComponentSwitching();
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

    #setupComponentSwitching() {
        const tabs = {
            'passwords-link': 'component-passwords',
            'sharing-link': 'component-sharing',
            'groups-link': 'component-groups'
        };

        const hideSpinner = () => {
            const spinner = document.querySelector('.content-placeholder');
            if (spinner) {
                spinner.style.display = 'none';
            }
        };

        const switchComponent = (link, componentId) => {
            document.querySelectorAll(".dashboard-nav .nav-link").forEach(nav => {
                nav.classList.remove("active");
            });

            link.classList.add("active");

            document.querySelectorAll("[data-component]").forEach(comp => {
                comp.style.display = "none";
            });

            const target = document.getElementById(componentId);
            if (target) {
                target.style.display = "block";
                hideSpinner();
            }
        };

        Object.entries(tabs).forEach(([linkId, componentId]) => {
            const link = document.getElementById(linkId);
            link?.addEventListener("click", (e) => {
                e.preventDefault();
                switchComponent(link, componentId);
            });
        });

        const defaultLink = document.getElementById("passwords-link");
        if (defaultLink) {
            switchComponent(defaultLink, "component-passwords");
        }
    }
}
