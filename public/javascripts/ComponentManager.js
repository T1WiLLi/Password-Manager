export default class ComponentManager {
    #tabs;
    #componentToLink;
    #STORAGE_KEY = "activeComponent";
    #DEFAULT_COMPONENT = "component-passwords";
    #passwordSearch;

    constructor(options = {}) {
        this.#tabs = options.tabs || {
            'passwords-link': 'component-passwords',
            'sharing-link': 'component-sharing',
            'groups-link': 'component-groups'
        };

        this.#passwordSearch = options.passwordSearch || null;
        this.#componentToLink = Object.entries(this.#tabs).reduce((acc, [linkId, componentId]) => {
            acc[componentId] = linkId;
            return acc;
        }, {});

        this.initialize();
    }

    initialize() {
        Object.entries(this.#tabs).forEach(([linkId, componentId]) => {
            const link = document.getElementById(linkId);
            link?.addEventListener("click", (e) => {
                e.preventDefault();
                this.switchComponent(link, componentId);
            });
        });

        this.restoreActiveComponent();
    }

    switchComponent(link, componentId) {
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
            this.#hideSpinner();

            if (componentId === "component-passwords" && this.#passwordSearch) {
                this.#passwordSearch.resetSearch();
            }

            try {
                localStorage.setItem(this.#STORAGE_KEY, componentId);
            } catch (error) {
                console.warn("Could not save active component to localStorage:", error);
            }
        }
    }

    restoreActiveComponent() {
        let activeComponentId;

        try {
            activeComponentId = localStorage.getItem(this.#STORAGE_KEY);
        } catch (error) {
            console.warn("Could not access localStorage:", error);
        }

        if (!activeComponentId || !document.getElementById(activeComponentId)) {
            activeComponentId = this.#DEFAULT_COMPONENT;
        }

        const linkId = this.#componentToLink[activeComponentId] || 'passwords-link';
        const linkElement = document.getElementById(linkId);

        if (linkElement) {
            this.switchComponent(linkElement, activeComponentId);
        } else {
            const defaultLink = document.getElementById("passwords-link");
            if (defaultLink) {
                this.switchComponent(defaultLink, this.#DEFAULT_COMPONENT);
            }
        }
    }

    setPasswordSearch(passwordSearch) {
        this.#passwordSearch = passwordSearch;
    }

    #hideSpinner() {
        const spinner = document.querySelector('.content-placeholder');
        if (spinner) {
            spinner.style.display = 'none';
        }
    }

    switchToComponentById(componentId) {
        if (!this.#componentToLink[componentId]) {
            return false;
        }

        const linkId = this.#componentToLink[componentId];
        const linkElement = document.getElementById(linkId);

        if (!linkElement) {
            return false;
        }

        this.switchComponent(linkElement, componentId);
        return true;
    }
}