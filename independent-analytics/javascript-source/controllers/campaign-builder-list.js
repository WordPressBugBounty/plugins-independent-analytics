import { Controller } from "@hotwired/stimulus";
import { downloadCSV } from "../download";

const campaignFeedbackDuration = 3000;
const campaignFeedbackFadeDuration = 1000;
const campaignScrollBuffer = 100;

export default class extends Controller {
    static values = {
        count: Number,
    };

    static targets = [
        "input",
        "filterAutocomplete",
        "filterButton",
        "filterPopover",
        "activeFilters",
        "filterChips",
        "resultCount",
        "exportButton",
        "exportButtonLabel",
        "content",
        "error",
    ];

    connect() {
        this.filters = [];
        this.request = null;
        this.exportRequest = null;
        this.exportableCount = this.countValue;
        this.isLoading = false;
        this.isExporting = false;
        this.pendingCampaignFeedback = null;
        this.feedbackTimeout = null;
        this.exportSuccessTimeout = null;
        this.updateExportButton();
    }

    disconnect() {
        this.request?.abort();
        this.exportRequest?.abort();
        clearTimeout(this.feedbackTimeout);
        clearTimeout(this.exportSuccessTimeout);
    }

    toggleFilterPopover(event) {
        event.preventDefault();

        if (this.filterPopoverTarget.hidden) {
            this.openFilterPopover();
            return;
        }

        this.closeFilterPopover();
    }

    openFilterPopover() {
        this.filterPopoverTarget.hidden = false;
        this.filterButtonTarget.classList.add("open");
        this.filterButtonTarget.setAttribute("aria-expanded", "true");
        requestAnimationFrame(() => this.inputTarget.focus());
    }

    closeFilterPopover({ restoreFocus = false } = {}) {
        if (this.filterPopoverTarget.hidden) {
            return;
        }

        this.filterPopoverTarget.hidden = true;
        this.filterButtonTarget.classList.remove("open");
        this.filterButtonTarget.setAttribute("aria-expanded", "false");
        this.inputTarget.value = "";
        this.filterAutocompleteTarget.dispatchEvent(new CustomEvent("campaign-autocomplete:reset"));

        if (restoreFocus) {
            this.filterButtonTarget.focus();
        }
    }

    handleDocumentClick(event) {
        if (
            this.filterPopoverTarget.hidden
            || this.filterPopoverTarget.contains(event.target)
            || this.filterButtonTarget.contains(event.target)
        ) {
            return;
        }

        this.closeFilterPopover();
    }

    handleDocumentKeydown(event) {
        if (event.key !== "Escape" || this.filterPopoverTarget.hidden) {
            return;
        }

        event.preventDefault();
        this.closeFilterPopover({ restoreFocus: true });
    }

    refresh(event) {
        if (!Array.isArray(event.detail?.filters)) {
            return;
        }

        this.filters = [];

        event.detail.filters.forEach((filter) => {
            const normalizedFilter = this.normalizeFilter(filter);

            if (normalizedFilter) {
                this.addOrMoveFilter(normalizedFilter);
            }
        });

        this.filtersChanged();
    }

    addAutocompleteFilter(event) {
        const filter = this.normalizeFilter(event.detail);

        if (!filter) {
            return;
        }

        this.inputTarget.value = "";
        this.inputTarget.blur();
        this.closeFilterPopover();
        this.addOrMoveFilter(filter);
        this.filtersChanged();
    }

    removeFilter(event) {
        const filterIndex = Number(event.currentTarget.dataset.campaignFilterIndex);

        if (!Number.isInteger(filterIndex) || !this.filters[filterIndex]) {
            return;
        }

        this.filters.splice(filterIndex, 1);
        this.filtersChanged();
    }

    clearFilters() {
        this.filters = [];
        this.filtersChanged();
    }

    retry() {
        this.fetchCampaigns();
    }

    refreshCurrent() {
        this.fetchCampaigns();
    }

    exportCampaignUrls() {
        if (this.exportButtonTarget.disabled) {
            return;
        }

        clearTimeout(this.exportSuccessTimeout);
        this.exportButtonTarget.classList.remove("sent");

        const data = {
            ...iawpActions.export_campaign_urls,
            filters: this.filters.map(({ field, value }) => ({ field, value })),
        };
        const request = jQuery.post(ajaxurl, data);

        this.exportRequest = request;
        this.setExporting(true);

        request.done((response) => {
            if (
                this.exportRequest !== request
                || !response.success
                || typeof response.data?.csv !== "string"
            ) {
                return;
            }

            downloadCSV("campaign-urls.csv", response.data.csv);
            this.showExportSuccess();
        });

        request.always(() => {
            if (this.exportRequest !== request) {
                return;
            }

            this.exportRequest = null;
            this.setExporting(false);
        });
    }

    campaignCreated(event) {
        const campaignUrlId = Number(event.detail?.campaignUrlId);

        if (!Number.isInteger(campaignUrlId) || campaignUrlId < 1) {
            return;
        }

        this.filters = [];
        this.inputTarget.value = "";
        this.closeFilterPopover();
        this.pendingCampaignFeedback = { campaignUrlId };
        this.renderFilters();
        this.fetchCampaigns();
    }

    campaignDuplicated(event) {
        const campaignUrlId = Number(event.detail?.campaignUrlId);

        if (!Number.isInteger(campaignUrlId) || campaignUrlId < 1) {
            return;
        }

        this.pendingCampaignFeedback = { campaignUrlId };
        this.fetchCampaigns();
    }

    campaignUpdated(event) {
        const campaignUrlId = Number(event.detail?.campaignUrlId);

        if (!Number.isInteger(campaignUrlId) || campaignUrlId < 1) {
            return;
        }

        this.pendingCampaignFeedback = { campaignUrlId };
        this.fetchCampaigns();
    }

    fetchCampaigns() {
        this.request?.abort();

        const data = {
            ...iawpActions.get_campaign_list,
            filters: this.filters.map(({ field, value }) => ({ field, value })),
        };
        const request = jQuery.post(ajaxurl, data);

        this.request = request;
        this.exportableCount = 0;
        this.setLoading(true);
        this.hideError();

        request.done((response) => {
            if (this.request !== request) {
                return;
            }

            if (
                !response.success
                || typeof response.data?.html !== "string"
                || typeof response.data?.count !== "number"
                || typeof response.data?.total_count !== "number"
            ) {
                this.showError();
                return;
            }

            const listDocument = new DOMParser().parseFromString(response.data.html, "text/html");

            this.contentTarget.replaceChildren(...listDocument.body.childNodes);
            this.updateResultCount(response.data.count, response.data.total_count);
            this.revealPendingCampaignFeedback();
        });

        request.fail((_request, status) => {
            if (this.request === request && status !== "abort") {
                this.showError();
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

    setLoading(isLoading) {
        this.isLoading = isLoading;
        this.element.classList.toggle("loading", isLoading);
        this.contentTarget.toggleAttribute("inert", isLoading);
        this.updateExportButton();
    }

    setExporting(isExporting) {
        this.isExporting = isExporting;
        this.exportButtonTarget.classList.toggle("sending", isExporting);
        this.updateExportButton();
    }

    updateExportButton() {
        this.exportButtonTarget.disabled = this.isLoading
            || this.isExporting
            || this.exportableCount === 0;
    }

    showExportSuccess() {
        clearTimeout(this.exportSuccessTimeout);
        this.exportButtonTarget.classList.add("sent");
        this.exportSuccessTimeout = setTimeout(() => {
            this.exportButtonTarget.classList.remove("sent");
        }, 1000);
    }

    showError() {
        this.errorTarget.hidden = false;
    }

    hideError() {
        this.errorTarget.hidden = true;
    }

    filtersChanged() {
        this.pendingCampaignFeedback = null;
        this.renderFilters();
        this.fetchCampaigns();
    }

    addOrMoveFilter(filter) {
        const filterIndex = this.findFilterIndex(filter);

        if (filterIndex !== -1) {
            this.filters.splice(filterIndex, 1);
        }

        this.filters.push(filter);
    }

    findFilterIndex(filter) {
        return this.filters.findIndex(({ field, value }) => field === filter.field && value === filter.value);
    }

    normalizeFilter(filter) {
        if (
            (filter?.field !== null && typeof filter?.field !== "string")
            || typeof filter?.value !== "string"
        ) {
            return null;
        }

        const field = typeof filter.field === "string" ? filter.field.trim() || null : null;
        const value = filter.value.trim();

        if (!value) {
            return null;
        }

        const fieldLabel = typeof filter.fieldLabel === "string" && filter.fieldLabel.trim()
            ? filter.fieldLabel.trim()
            : field;

        return { field, fieldLabel, value };
    }

    renderFilters() {
        this.filterChipsTarget.replaceChildren();

        this.filters.forEach((filter, filterIndex) => {
            const button = document.createElement("button");
            const subject = document.createElement("strong");
            const comparison = document.createElement("span");
            const value = document.createElement("strong");
            const remove = document.createElement("span");
            const isGeneralSearch = filter.field === null;

            button.type = "button";
            button.className = "filters-condition-button campaign-filter-chip";
            button.dataset.campaignFilterIndex = filterIndex;
            button.dataset.action = "campaign-builder-list#removeFilter";

            subject.className = "campaign-filter-chip-subject";
            subject.textContent = isGeneralSearch
                ? this.filterChipsTarget.dataset.anyFieldLabel
                : filter.fieldLabel;

            comparison.className = "campaign-filter-chip-comparison";
            comparison.textContent = ` ${isGeneralSearch
                ? this.filterChipsTarget.dataset.containsLabel
                : this.filterChipsTarget.dataset.isLabel} `;

            value.className = "campaign-filter-chip-value";
            value.textContent = filter.value;

            remove.className = "campaign-filter-chip-remove";
            remove.textContent = "×";
            remove.setAttribute("aria-hidden", "true");

            button.append(subject, comparison, value, remove);
            this.filterChipsTarget.append(button);
        });

        this.activeFiltersTarget.hidden = this.filters.length === 0;

        if (this.filters.length === 0) {
            this.updateExportButtonLabel(0);
        }

        this.syncFilterInput();
    }

    syncFilterInput() {
        if (this.filters.length > 0) {
            return;
        }

        this.inputTarget.value = "";
        this.filterAutocompleteTarget.dispatchEvent(new CustomEvent("campaign-autocomplete:reset"));
    }

    updateResultCount(count, totalCount) {
        this.countValue = totalCount;
        this.exportableCount = count;

        const numberFormatter = new Intl.NumberFormat();
        const formattedCount = numberFormatter.format(count);
        const formattedTotalCount = numberFormatter.format(totalCount);

        this.updateExportButtonLabel(count, formattedCount);
        this.resultCountTarget.textContent = this.resultCountTarget.dataset.template
            .replace("%1$s", formattedCount)
            .replace("%2$s", formattedTotalCount);
        this.updateExportButton();
    }

    updateExportButtonLabel(count, formattedCount = "0") {
        if (this.filters.length === 0) {
            this.exportButtonLabelTarget.textContent = this.exportButtonTarget.dataset.defaultLabel;
            return;
        }

        const template = count === 1
            ? this.exportButtonTarget.dataset.singularLabelTemplate
            : this.exportButtonTarget.dataset.pluralLabelTemplate;

        this.exportButtonLabelTarget.textContent = template.replace("%s", formattedCount);
    }

    revealPendingCampaignFeedback() {
        if (!this.pendingCampaignFeedback) {
            return;
        }

        const { campaignUrlId } = this.pendingCampaignFeedback;
        const campaign = this.contentTarget.querySelector(`[data-campaign-url-id="${campaignUrlId}"]`);

        this.pendingCampaignFeedback = null;

        if (!campaign) {
            return;
        }

        requestAnimationFrame(() => {
            this.scrollCampaignIntoView(campaign);
            campaign.classList.add("campaign-status-highlight");

            clearTimeout(this.feedbackTimeout);
            this.feedbackTimeout = setTimeout(() => {
                campaign.classList.remove("campaign-status-highlight");
            }, campaignFeedbackDuration - campaignFeedbackFadeDuration);
        });
    }

    scrollCampaignIntoView(campaign) {
        const campaignRect = campaign.getBoundingClientRect();
        const adminBarBottom = document.querySelector("#wpadminbar")?.getBoundingClientRect().bottom ?? 0;
        const viewportTop = Math.max(0, adminBarBottom);
        const viewportBottom = window.innerHeight || document.documentElement.clientHeight;

        if (campaignRect.top >= viewportTop && campaignRect.bottom <= viewportBottom) {
            return;
        }

        const availableSpace = Math.max(0, viewportBottom - viewportTop - campaignRect.height);
        const scrollBuffer = Math.min(campaignScrollBuffer, availableSpace / 2);
        const scrollOffset = campaignRect.top < viewportTop
            ? campaignRect.top - viewportTop - scrollBuffer
            : campaignRect.bottom - viewportBottom + scrollBuffer;

        window.scrollBy({ top: scrollOffset, behavior: "smooth" });
    }
}
