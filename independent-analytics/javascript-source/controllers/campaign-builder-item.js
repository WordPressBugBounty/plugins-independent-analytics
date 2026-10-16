import { Controller } from "@hotwired/stimulus";

const panelOpenedEvent = "campaign-builder-item:opened";
const campaignDeletedEvent = "campaign-builder-item:deleted";
const campaignDuplicatedEvent = "campaign-builder-item:duplicated";

export default class extends Controller {
    static targets = ["trigger", "panel", "delete", "copyIcon", "copySuccessIcon"];

    connect() {
        this.deleteLabel = this.deleteTarget.textContent.trim();
        this.awaitingDeleteConfirmation = false;
        this.copyResetTimeout = null;
        this.deleteRequest = null;
        this.duplicateRequest = null;
        this.resetCopyIcon();
    }

    disconnect() {
        clearTimeout(this.copyResetTimeout);
    }

    toggleActions() {
        if (this.panelTarget.hidden) {
            this.open();
        } else {
            this.close();
        }
    }

    open() {
        if (!this.panelTarget.hidden) {
            return;
        }

        document.dispatchEvent(new CustomEvent(panelOpenedEvent, { detail: { controller: this } }));

        this.panelTarget.hidden = false;
        this.positionPanel();
    }

    close({ restoreFocus = false } = {}) {
        if (this.panelTarget.hidden) {
            return;
        }

        this.panelTarget.hidden = true;
        this.panelTarget.style.removeProperty("inset");
        this.resetDeleteConfirmation();

        if (restoreFocus) {
            this.triggerTarget.focus();
        }
    }

    edit() {
        const campaignUrlId = Number(this.element.dataset.campaignUrlId);
        let fields;

        try {
            fields = JSON.parse(this.element.dataset.campaignFields);
        } catch {
            return;
        }

        if (!Number.isInteger(campaignUrlId) || campaignUrlId < 1 || !fields || typeof fields !== "object") {
            return;
        }

        this.close();
        this.dispatch("edit", {
            detail: {
                campaignUrlId,
                fields,
            },
        });
    }

    duplicate(event) {
        const button = event.currentTarget;
        const request = jQuery.post(ajaxurl, {
            ...iawpActions.duplicate_campaign,
            campaign_url_id: this.element.dataset.campaignUrlId,
        });

        this.duplicateRequest = request;
        button.disabled = true;
        button.classList.add("loading");

        request.done((response) => {
            const campaignUrlId = Number(response.data?.campaign_url_id);

            if (
                this.duplicateRequest !== request
                || !response.success
                || !Number.isInteger(campaignUrlId)
                || campaignUrlId < 1
            ) {
                return;
            }

            this.close();
            document.dispatchEvent(new CustomEvent(campaignDuplicatedEvent, {
                detail: { campaignUrlId },
            }));
        });

        request.always(() => {
            if (this.duplicateRequest !== request) {
                return;
            }

            this.duplicateRequest = null;
            button.disabled = false;
            button.classList.remove("loading");
        });
    }

    async copyCampaignUrl(event) {
        try {
            await this.copyTextToClipboard(event.currentTarget.dataset.copyText);
        } catch {
            return;
        }

        clearTimeout(this.copyResetTimeout);
        this.copyIconTarget.toggleAttribute("hidden", true);
        this.copySuccessIconTarget.toggleAttribute("hidden", false);

        this.copyResetTimeout = setTimeout(() => {
            this.resetCopyIcon();
        }, 1000);
    }

    downloadQrCode(event) {
        const campaignUrlId = Number(this.element.dataset.campaignUrlId);
        const url = event.currentTarget.dataset.campaignUrl;

        if (!Number.isInteger(campaignUrlId) || campaignUrlId < 1 || typeof url !== "string" || url === "") {
            return;
        }

        this.dispatch("qr-code", {
            detail: {
                campaignUrlId,
                url,
                actionButton: event.currentTarget,
                returnFocusElement: event.currentTarget,
            },
        });
    }

    resetCopyIcon() {
        this.copyIconTarget.toggleAttribute("hidden", false);
        this.copySuccessIconTarget.toggleAttribute("hidden", true);
    }

    copyTextToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        const textArea = document.createElement("textarea");

        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.inset = "0 auto auto -999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        const copied = document.execCommand("copy");

        textArea.remove();

        return copied ? Promise.resolve() : Promise.reject();
    }

    delete(event) {
        if (this.awaitingDeleteConfirmation) {
            this.deleteCampaign(event.currentTarget);
            return;
        }

        this.awaitingDeleteConfirmation = true;
        event.currentTarget.textContent = event.currentTarget.dataset.confirmLabel;
    }

    deleteCampaign(button) {
        const request = jQuery.post(ajaxurl, {
            ...iawpActions.delete_campaign,
            campaign_url_id: button.dataset.campaignUrlId,
        });

        this.deleteRequest = request;
        button.disabled = true;
        button.classList.add("loading");

        request.done((response) => {
            if (this.deleteRequest !== request || !response.success) {
                return;
            }

            this.close();
            document.dispatchEvent(new CustomEvent(campaignDeletedEvent));
        });

        request.always(() => {
            if (this.deleteRequest !== request) {
                return;
            }

            this.deleteRequest = null;
            button.disabled = false;
            button.classList.remove("loading");

            if (!this.panelTarget.hidden) {
                this.resetDeleteConfirmation();
            }
        });
    }

    resetDeleteConfirmation() {
        this.awaitingDeleteConfirmation = false;
        this.deleteTarget.textContent = this.deleteLabel;
    }

    handleDocumentClick(event) {
        if (this.panelTarget.hidden) {
            return;
        }

        if (!this.triggerTarget.contains(event.target) && !this.panelTarget.contains(event.target)) {
            this.close();
        }
    }

    handleKeydown(event) {
        if (this.panelTarget.hidden || event.key !== "Escape") {
            return;
        }

        event.preventDefault();
        this.close({ restoreFocus: true });
    }

    handlePanelOpened(event) {
        if (event.detail.controller !== this) {
            this.close();
        }
    }

    positionPanel() {
        if (this.panelTarget.hidden) {
            return;
        }

        const gap = 6;
        const viewportPadding = 8;
        const triggerRect = this.triggerTarget.getBoundingClientRect();
        const panelWidth = this.panelTarget.offsetWidth;
        const panelHeight = this.panelTarget.offsetHeight;
        const scrollingElement = document.scrollingElement ?? document.documentElement;
        const panelBottomInDocument = scrollingElement.scrollTop + triggerRect.bottom + gap + panelHeight;
        const canFitBelow = panelBottomInDocument <= scrollingElement.scrollHeight;
        const top = canFitBelow ? triggerRect.bottom + gap : triggerRect.top - panelHeight - gap;
        const maxLeft = Math.max(viewportPadding, window.innerWidth - panelWidth - viewportPadding);
        const anchoredLeft = triggerRect.right - panelWidth;
        const isTriggerHorizontallyVisible = triggerRect.right > 0 && triggerRect.left < window.innerWidth;
        const left = isTriggerHorizontallyVisible ? Math.min(maxLeft, Math.max(viewportPadding, anchoredLeft)) : anchoredLeft;

        this.panelTarget.style.inset = `${top}px auto auto ${left}px`;
    }
}
