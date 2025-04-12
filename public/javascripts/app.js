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
                    const collapse = new bootstrap.Collapse(addPasswordForm, { toggle: false });
                    collapse.show();
                    if (this.#passwordSearch) {
                        this.#passwordSearch.resetSearch();
                    }
                    addPasswordForm.querySelector('#serviceName').value = '';
                    addPasswordForm.querySelector('#username').value = '';
                    addPasswordForm.querySelector('#password').value = '';
                }
            });
        }

        const sharePasswordAction = document.querySelector('.action-item[data-action="share-password"]');
        if (sharePasswordAction) {
            sharePasswordAction.addEventListener('click', () => {
                this.#componentManager.switchToComponentById('component-sharing');
                const sharePasswordForm = document.getElementById('sharePasswordForm');
                if (sharePasswordForm) {
                    const collapse = new bootstrap.Collapse(sharePasswordForm, { toggle: false });
                    collapse.show();
                    sharePasswordForm.querySelector('#password_id').value = '';
                    sharePasswordForm.querySelector('#email').value = '';
                }
            });
        }

        const generatePasswordAction = document.querySelector('.action-item[data-action="generate-password"]');
        if (generatePasswordAction) {
            generatePasswordAction.addEventListener('click', () => {
                const modal = new bootstrap.Modal(document.getElementById('generatePasswordModal'));
                modal.show();
                const generatedPasswordText = document.getElementById('generatedPasswordText');
                generatedPasswordText.textContent = 'Loading...';

                fetch('/dashboard/generate-password?length=12', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.password) {
                            generatedPasswordText.textContent = data.password;
                        } else {
                            generatedPasswordText.textContent = 'Error generating password';
                            console.error('Error in response:', data.error);
                        }
                    })
                    .catch(error => {
                        generatedPasswordText.textContent = 'Failed to generate password';
                        console.error('Fetch Error:', error);
                    });
            });
        }

        const useGeneratedPasswordBtn = document.getElementById('useGeneratedPassword');
        if (useGeneratedPasswordBtn) {
            useGeneratedPasswordBtn.addEventListener('click', () => {
                const generatedPassword = document.getElementById('generatedPasswordText').textContent;
                if (generatedPassword && generatedPassword !== 'Loading...' && generatedPassword !== 'Error generating password' && generatedPassword !== 'Failed to generate password') {
                    this.#componentManager.switchToComponentById('component-passwords');
                    const addPasswordForm = document.getElementById('addPasswordForm');
                    if (addPasswordForm) {
                        const collapse = new bootstrap.Collapse(addPasswordForm, { toggle: false });
                        collapse.show();
                        if (this.#passwordSearch) {
                            this.#passwordSearch.resetSearch();
                        }
                        addPasswordForm.querySelector('#serviceName').value = '';
                        addPasswordForm.querySelector('#username').value = '';
                        addPasswordForm.querySelector('#password').value = generatedPassword;
                        bootstrap.Modal.getInstance(document.getElementById('generatePasswordModal')).hide();
                    }
                }
            });
        }

        const securityCheckAction = document.querySelector('.action-item[data-action="security-check"]');
        if (securityCheckAction) {
            securityCheckAction.addEventListener('click', () => {
                const modal = new bootstrap.Modal(document.getElementById('securityCheckModal'));
                modal.show();
                this.#runSecurityCheck();
            });
        }

        const securityAlertsAction = document.querySelector('.action-item[data-action="security-alerts"]');
        if (securityAlertsAction) {
            securityAlertsAction.addEventListener('click', () => {
                const modal = new bootstrap.Modal(document.getElementById('securityAlertsModal'));
                modal.show();
                this.#runSecurityAlerts();
            });
        }
    }

    #runSecurityCheck() {
        const loadingDiv = document.getElementById('securityCheckLoading');
        const resultsDiv = document.getElementById('securityCheckResults');
        loadingDiv.style.display = 'block';
        resultsDiv.style.display = 'none';

        fetch('/dashboard/security-check', {
            method: 'GET'
        })
            .then(response => response.json())
            .then(data => {
                loadingDiv.style.display = 'none';
                resultsDiv.style.display = 'block';
                const list = resultsDiv.querySelector('.list-group');
                list.innerHTML = '';
                data.passwords.forEach(p => {
                    const item = document.createElement('li');
                    item.className = `list-group-item d-flex justify-content-between align-items-center ${p.strength === 'Strong' ? 'list-group-item-success' : p.strength === 'Moderate' ? 'list-group-item-warning' : 'list-group-item-danger'}`;
                    item.innerHTML = `
                    <span>${p.service_name} (${p.username})</span>
                    <span>${p.strength} (Entropy: ${p.entropy.toFixed(2)})</span>
                `;
                    list.appendChild(item);
                });
                if (data.passwords.length === 0) {
                    list.innerHTML = '<li class="list-group-item">No passwords to check.</li>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                loadingDiv.style.display = 'none';
                resultsDiv.style.display = 'block';
                resultsDiv.querySelector('.list-group').innerHTML = '<li class="list-group-item list-group-item-danger">Error loading security check results.</li>';
            });
    }

    #runSecurityAlerts() {
        const loadingDiv = document.getElementById('securityAlertsLoading');
        const resultsDiv = document.getElementById('securityAlertsResults');
        const breachList = document.getElementById('breachList');
        loadingDiv.style.display = 'block';
        resultsDiv.style.display = 'none';

        fetch('/dashboard/security-alerts', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                loadingDiv.style.display = 'none';
                resultsDiv.style.display = 'block';
                breachList.innerHTML = '';

                if (data.breaches && data.breaches.length > 0) {
                    data.breaches.forEach(breach => {
                        const item = document.createElement('li');
                        item.className = 'list-group-item list-group-item-danger d-flex justify-content-between align-items-center';
                        item.innerHTML = `
                            <span>${breach.service_name} (${breach.username})</span>
                            <span>Breached ${breach.breach_count} times</span>
                        `;
                        breachList.appendChild(item);
                    });
                } else {
                    breachList.innerHTML = '<li class="list-group-item list-group-item-success">No breached passwords found.</li>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                loadingDiv.style.display = 'none';
                resultsDiv.style.display = 'block';
                breachList.innerHTML = '<li class="list-group-item list-group-item-danger">Error loading security alerts.</li>';
            });
    }
}