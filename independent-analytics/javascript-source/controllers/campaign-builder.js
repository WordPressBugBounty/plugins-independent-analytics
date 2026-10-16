import { Controller } from "@hotwired/stimulus";
import MicroModal from "micromodal";

const modalId = "iawp-campaign-editor-modal";
const campaignFieldNames = ["path", "utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content", "note"];

export default class extends Controller {
    static targets = [
        "modal",
        "title",
        "form",
        "fields",
        "submitButton",
        "closeButton",
        "cancelButton",
        "requestError",
    ];

    connect() {
        this.isModalOpen = false;
        this.isSubmitting = false;
        this.request = null;
        this.mode = "create";
        this.campaignUrlId = null;
        this.updateEditorCopy();
    }

    openCreate() {
        if (this.isModalOpen) {
            return;
        }

        this.setMode("create");
        this.resetEditor();
        this.showEditor();
    }

    openEdit(event) {
        if (this.isModalOpen) {
            return;
        }

        const campaignUrlId = Number(event.detail?.campaignUrlId);
        const fields = event.detail?.fields;

        if (
            !Number.isInteger(campaignUrlId)
            || campaignUrlId < 1
            || !fields
            || typeof fields !== "object"
            || !campaignFieldNames.every((fieldName) => typeof fields[fieldName] === "string")
        ) {
            return;
        }

        this.setMode("edit", campaignUrlId);
        this.resetEditor();

        campaignFieldNames.forEach((fieldName) => {
            this.formTarget.elements[fieldName].value = fields[fieldName];
        });

        this.showEditor();
    }

    showEditor() {
        this.isModalOpen = true;

        MicroModal.show(modalId, {
            disableScroll: true,
            disableFocus: true,
            onClose: () => {
                this.isModalOpen = false;
                this.setMode("create");
                this.resetEditor();
            },
        });

        this.formTarget.elements.path.focus();
    }

    closeFromOverlay(event) {
        if (event.target !== event.currentTarget || this.isSubmitting) {
            return;
        }

        MicroModal.close(modalId);
    }

    guardModalClose(event) {
        if (!this.isModalOpen || !this.isSubmitting || event.key !== "Escape") {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
    }

    submit(event) {
        event.preventDefault();

        if (this.isSubmitting) {
            return;
        }

        this.clearErrors();

        const mode = this.mode;
        const isUpdate = mode === "edit";
        const request = jQuery.post(ajaxurl, {
            ...(isUpdate ? iawpActions.update_campaign : iawpActions.create_campaign),
            ...(isUpdate ? { campaign_url_id: this.campaignUrlId } : {}),
            path: this.formTarget.elements.path.value,
            utm_source: this.formTarget.elements.utm_source.value,
            utm_medium: this.formTarget.elements.utm_medium.value,
            utm_campaign: this.formTarget.elements.utm_campaign.value,
            utm_term: this.formTarget.elements.utm_term.value,
            utm_content: this.formTarget.elements.utm_content.value,
            note: this.formTarget.elements.note.value,
        });

        this.request = request;
        this.setSubmitting(true);

        request.done((response) => {
            if (this.request !== request) {
                return;
            }

            const campaignUrlId = Number(response.data?.campaign_url_id);

            if (!response.success || !Number.isInteger(campaignUrlId) || campaignUrlId < 1) {
                this.showRequestError();
                return;
            }

            this.request = null;
            this.setSubmitting(false);
            MicroModal.close(modalId);
            this.dispatch(mode === "edit" ? "updated" : "created", { detail: { campaignUrlId } });
        });

        request.fail((xhr, status) => {
            if (this.request !== request || status === "abort") {
                return;
            }

            const errors = xhr.responseJSON?.data?.errors;

            if (errors && typeof errors === "object" && !Array.isArray(errors)) {
                this.showFieldErrors(errors);
            } else {
                this.showRequestError();
            }
        });

        request.always(() => {
            if (this.request !== request) {
                return;
            }

            this.request = null;
            this.setSubmitting(false);
        });
    }

    handleInput(event) {
        if (event.target.name) {
            this.clearFieldError(event.target.name);
        }
    }

    setSubmitting(isSubmitting) {
        this.isSubmitting = isSubmitting;
        this.fieldsTarget.disabled = isSubmitting;
        this.submitButtonTarget.disabled = isSubmitting;
        this.closeButtonTarget.disabled = isSubmitting;
        this.cancelButtonTarget.disabled = isSubmitting;
        this.submitButtonTarget.classList.toggle("sending", isSubmitting);
        this.modalTarget.classList.toggle("submitting", isSubmitting);
    }

    setMode(mode, campaignUrlId = null) {
        this.mode = mode;
        this.campaignUrlId = campaignUrlId;
        this.updateEditorCopy();
    }

    updateEditorCopy() {
        const isUpdate = this.mode === "edit";
        const labelKey = isUpdate ? "updateLabel" : "createLabel";
        const messageKey = isUpdate ? "updateMessage" : "createMessage";

        this.titleTarget.textContent = this.titleTarget.dataset[labelKey];
        this.submitButtonTarget.textContent = this.submitButtonTarget.dataset[labelKey];
        this.requestErrorTarget.textContent = this.requestErrorTarget.dataset[messageKey];
    }

    showFieldErrors(errors) {
        Object.entries(errors).forEach(([fieldName, message]) => {
            if (typeof message !== "string") {
                return;
            }

            const errorElement = this.formTarget.querySelector(`[data-campaign-builder-error-field="${fieldName}"]`);
            const input = this.formTarget.elements[fieldName];

            if (!errorElement || !input) {
                return;
            }

            errorElement.textContent = message;
            errorElement.hidden = false;
            input.classList.add("error");
        });
    }

    clearFieldError(fieldName) {
        const errorElement = this.formTarget.querySelector(`[data-campaign-builder-error-field="${fieldName}"]`);
        const input = this.formTarget.elements[fieldName];

        if (errorElement) {
            errorElement.textContent = "";
            errorElement.hidden = true;
        }

        input?.classList.remove("error");
    }

    showRequestError() {
        this.requestErrorTarget.hidden = false;
    }

    clearErrors() {
        this.formTarget.querySelectorAll("[data-campaign-builder-error-field]").forEach((errorElement) => {
            errorElement.textContent = "";
            errorElement.hidden = true;
        });
        this.formTarget.querySelectorAll("input.error").forEach((input) => input.classList.remove("error"));
        this.requestErrorTarget.hidden = true;
    }

    resetEditor() {
        this.formTarget.reset();
        this.clearErrors();
        this.closeAutocompletes();
    }

    closeAutocompletes() {
        this.modalTarget.querySelectorAll('[data-controller~="campaign-autocomplete"]').forEach((autocomplete) => {
            autocomplete.dispatchEvent(new CustomEvent("campaign-autocomplete:reset"));
        });
    }
}
