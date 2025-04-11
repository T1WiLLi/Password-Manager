export default class PasswordSearch {

    constructor(searchInputId, tableBodySelector) {
        this.searchInput = document.getElementById(searchInputId);
        this.tableBody = document.querySelector(tableBodySelector);
        this.passwordRows = [];
        this.editRows = [];

        if (this.searchInput && this.tableBody) {
            this.init();
        }
    }

    init() {
        this.passwordRows = Array.from(this.tableBody.querySelectorAll('.password-row'));
        this.editRows = Array.from(this.tableBody.querySelectorAll('.edit-row'));

        this.searchInput.addEventListener('input', this.handleSearch.bind(this));
    }

    handleSearch(event) {
        const searchTerm = event.target.value.toLowerCase().trim();

        this.passwordRows.forEach((row, index) => {
            const service = row.querySelector('td:first-child').textContent.toLowerCase();
            const username = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const isMatch = service.includes(searchTerm) || username.includes(searchTerm);
            row.style.display = isMatch ? 'table-row' : 'none';

            if (this.editRows[index]) {
                this.editRows[index].style.display = 'none';
            }
        });
        this.updateNoResultsMessage(searchTerm);
    }

    updateNoResultsMessage(searchTerm) {
        const existingMessage = this.tableBody.querySelector('.no-results-row');
        if (existingMessage) {
            this.tableBody.removeChild(existingMessage);
        }

        const visibleRows = this.passwordRows.filter(row => row.style.display !== 'none');

        if (visibleRows.length === 0 && searchTerm) {
            const messageRow = document.createElement('tr');
            messageRow.className = 'no-results-row';

            const messageCell = document.createElement('td');
            messageCell.colSpan = 4;
            messageCell.textContent = `No passwords found matching "${searchTerm}"`;
            messageCell.className = 'text-center py-4 text-muted';

            messageRow.appendChild(messageCell);
            this.tableBody.appendChild(messageRow);
        }
    }

    resetSearch() {
        if (this.searchInput) {
            this.searchInput.value = '';
            this.passwordRows.forEach(row => {
                row.style.display = 'table-row';
            });

            this.editRows.forEach(row => {
                row.style.display = 'none';
            });

            const existingMessage = this.tableBody.querySelector('.no-results-row');
            if (existingMessage) {
                this.tableBody.removeChild(existingMessage);
            }
        }
    }
}