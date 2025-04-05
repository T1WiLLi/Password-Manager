export default class DateTimeDisplay {
    #elementId;
    #dateTimeElement;
    #updateInterval;

    constructor(elementId) {
        this.#elementId = elementId;
        this.#dateTimeElement = document.getElementById(this.#elementId);

        if (this.#dateTimeElement) {
            this.#updateDateTime();
            this.#updateInterval = setInterval(() => this.#updateDateTime(), 1000);
        }
    }

    #updateDateTime() {
        const now = new Date();
        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        const dayOfWeek = dayNames[now.getDay()];

        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');

        const formattedDate = `${dayOfWeek}`;
        const formattedTime = `${hours}:${minutes}`;

        this.#dateTimeElement.textContent = `${formattedDate} - ${formattedTime}`;
    }

    destroy() {
        if (this.#updateInterval) {
            clearInterval(this.#updateInterval);
        }
    }
}