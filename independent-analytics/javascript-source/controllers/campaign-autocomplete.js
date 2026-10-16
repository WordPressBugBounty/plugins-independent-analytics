import { Controller } from "@hotwired/stimulus";

const SEARCH_DEBOUNCE_MS = 300;

export default class extends Controller {
    static targets = ["input", "listbox"];

    static values = {
        field: String,
    };

    connect() {
        this.activeIndex = -1;
        this.options = [];
        this.request = null;
        this.searchTimeout = null;
        this.isSelecting = false;
    }

    disconnect() {
        clearTimeout(this.searchTimeout);
        this.request?.abort();
    }

    focus() {
        this.queueRequest({ immediately: true });
    }

    blur(event) {
        if (this.element.contains(event.relatedTarget)) {
            return;
        }

        this.close();
    }

    search() {
        if (this.isSelecting) {
            return;
        }

        this.close();
        this.queueRequest();
    }

    keydown(event) {
        if (event.key === "Escape") {
            const wasOpen = !this.listboxTarget.hidden;

            this.close();

            if (wasOpen) {
                event.stopPropagation();
            }

            return;
        }

        if (event.key === "ArrowDown" || event.key === "ArrowUp") {
            event.preventDefault();

            if (this.listboxTarget.hidden) {
                this.queueRequest({ immediately: true });
                return;
            }

            const direction = event.key === "ArrowDown" ? 1 : -1;
            const startingIndex = this.activeIndex === -1 && direction === -1 ? this.options.length : this.activeIndex;
            this.setActiveIndex(startingIndex + direction);
            return;
        }

        if (event.key === "Enter") {
            if (
                this.activeIndex !== -1
                && !this.listboxTarget.hidden
                && this.options[this.activeIndex]
            ) {
                event.preventDefault();
                this.select(this.activeIndex);
                return;
            }

            if (this.inputTarget.form) {
                this.close();
                return;
            }

            if (this.inputTarget.value.trim() !== "") {
                event.preventDefault();
                this.selectTypedValue();
                return;
            }

            if (!this.listboxTarget.hidden && this.options.length > 0) {
                event.preventDefault();
                this.select(0);
            }
        }
    }

    selectOption(event) {
        const optionElement = event.target.closest("[data-campaign-autocomplete-index]");

        if (!optionElement || !this.listboxTarget.contains(optionElement)) {
            return;
        }

        event.preventDefault();
        this.select(Number(optionElement.dataset.campaignAutocompleteIndex));
    }

    handleDocumentClick(event) {
        if (!this.element.contains(event.target)) {
            this.close();
        }
    }

    reset() {
        this.close();
    }

    queueRequest({ immediately = false } = {}) {
        clearTimeout(this.searchTimeout);

        if (immediately) {
            this.fetchOptions();
            return;
        }

        this.searchTimeout = setTimeout(() => this.fetchOptions(), SEARCH_DEBOUNCE_MS);
    }

    fetchOptions() {
        this.request?.abort();

        const query = this.inputTarget.value;
        const data = {
            ...iawpActions.campaign_autocomplete,
            query,
        };

        if (this.hasFieldValue && this.fieldValue) {
            data.field = this.fieldValue;
        }

        this.setLoading(true);
        const request = jQuery.post(ajaxurl, data);

        this.request = request;

        request.done((response) => {
            if (
                this.request !== request
                || this.inputTarget.value !== query
                || !response.success
                || !Array.isArray(response.data)
            ) {
                return;
            }

            this.render(response.data);
        });

        request.fail((_request, status) => {
            if (this.request === request && status !== "abort") {
                this.close({ cancelRequest: false });
            }
        });

        request.always(() => {
            if (this.request !== request) {
                return;
            }

            this.request = null;
            this.setLoading(false);
        });
    }

    render(options) {
        this.options = options;
        this.activeIndex = -1;
        this.listboxTarget.replaceChildren();

        options.forEach((option, index) => {
            const optionElement = document.createElement("div");
            const optionContent = document.createElement("span");
            const optionValue = document.createElement("span");

            optionElement.className = "campaign-autocomplete-option";
            optionElement.dataset.campaignAutocompleteIndex = index;

            optionContent.className = "campaign-autocomplete-option-content";
            optionValue.className = "campaign-autocomplete-option-value";
            optionValue.textContent = option.field === null
                ? `${iawpText.contains} “${option.value}”`
                : option.value;

            const optionField = document.createElement("span");
            const fieldLabel = option.field === null ? iawpText.search : option.fieldLabel;
            optionField.className = "campaign-autocomplete-option-field";
            optionField.textContent = option.count === null
                ? `${iawpText.new} ${fieldLabel}`
                : fieldLabel;
            optionContent.append(optionField);

            optionContent.append(optionValue);
            optionElement.append(optionContent);

            if (option.count !== null) {
                const optionCount = document.createElement("span");
                optionCount.className = "campaign-autocomplete-option-count";
                optionCount.textContent = option.count;
                optionElement.append(optionCount);
            }

            this.listboxTarget.append(optionElement);
        });

        if (options.length === 0) {
            this.close({ cancelRequest: false });
            return;
        }

        this.listboxTarget.hidden = false;
    }

    select(index) {
        const option = this.options[index];

        if (!option) {
            return;
        }

        this.commitSelection(option);
    }

    selectTypedValue() {
        this.commitSelection({
            field: this.hasFieldValue && this.fieldValue ? this.fieldValue : null,
            fieldLabel: this.hasFieldValue && this.fieldValue ? this.fieldValue : null,
            value: this.inputTarget.value,
            count: null,
        });
    }

    commitSelection(option) {
        this.isSelecting = true;
        this.inputTarget.value = option.value;
        this.inputTarget.dispatchEvent(new Event("input", { bubbles: true }));
        this.inputTarget.dispatchEvent(new Event("change", { bubbles: true }));
        this.isSelecting = false;

        this.dispatch("selected", { detail: option });
        this.close();
    }

    setActiveIndex(index) {
        if (this.options.length === 0) {
            return;
        }

        this.activeIndex = (index + this.options.length) % this.options.length;

        this.listboxTarget.querySelectorAll("[data-campaign-autocomplete-index]").forEach((optionElement, optionIndex) => {
            const isActive = optionIndex === this.activeIndex;
            optionElement.classList.toggle("active", isActive);

            if (isActive) {
                optionElement.scrollIntoView({ block: "nearest" });
            }
        });
    }

    setLoading(isLoading) {
        this.element.classList.toggle("loading", isLoading);
    }

    close({ cancelRequest = true } = {}) {
        clearTimeout(this.searchTimeout);

        if (cancelRequest) {
            this.request?.abort();
            this.request = null;
            this.setLoading(false);
        }

        this.options = [];
        this.activeIndex = -1;
        this.listboxTarget.hidden = true;
        this.listboxTarget.replaceChildren();
    }
}
