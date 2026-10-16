import { Controller } from "@hotwired/stimulus";
import MicroModal from "micromodal";
import QRCodeStyling from "qr-code-styling";

const modalId = "iawp-campaign-qr-code-modal";
const hexColorPattern = /^#[0-9a-f]{6}$/i;
const svgNamespace = "http://www.w3.org/2000/svg";
const preferenceSaveDelay = 1000;
const loadErrorLabelDuration = 2000;
const hasOwn = (object, key) => Object.prototype.hasOwnProperty.call(object, key);
let frameClipPathSequence = 0;
const colorPreferenceNames = {
    pattern: "pattern_color",
    eyes: "eye_color",
    background: "background_color",
    frame: "frame_color",
};
const colorPreferenceKeys = Object.values(colorPreferenceNames);
const choicePreferenceNames = ["pattern", "eyes", "background", "frame"];
const formPreferenceNames = ["size", ...choicePreferenceNames];
const allowedPreferenceValues = {
    size: [256, 512, 1024],
    pattern: ["square", "rounded", "dots"],
    eyes: ["square", "rounded", "circular"],
    background: ["white", "none", "custom"],
    frame: ["none", "border", "rounded"],
};

export default class extends Controller {
    static targets = [
        "modal",
        "form",
        "preview",
        "url",
        "error",
        "chooseLogoButton",
        "logoName",
        "removeLogoButton",
        "closeButton",
        "cancelButton",
        "downloadButton",
    ];

    connect() {
        this.isModalOpen = false;
        this.isMediaLibraryOpen = false;
        this.isExporting = false;
        this.qrReady = false;
        this.qrCode = null;
        this.campaignUrlId = null;
        this.campaignUrl = "";
        this.logoData = "";
        this.logoAttachmentId = null;
        this.pendingLogoDetails = null;
        this.mediaFrame = null;
        this.renderSequence = 0;
        this.renderFrame = null;
        this.returnFocusElement = null;
        this.isLoadingPreferences = false;
        this.preferenceSaveTimer = null;
        this.preferenceSavePromise = null;
        this.pendingPreferenceChanges = {};
        this.loadErrorLabelTimer = null;
        this.loadErrorButton = null;
        this.handlePageHide = () => this.flushPreferences({ keepalive: true });
        this.resetDesigner();
        this.frontendDefaults = this.preferenceValues();
        window.addEventListener("pagehide", this.handlePageHide);
    }

    disconnect() {
        this.renderSequence += 1;
        cancelAnimationFrame(this.renderFrame);
        clearTimeout(this.preferenceSaveTimer);
        clearTimeout(this.loadErrorLabelTimer);
        this.restorePreferenceLoadLabel();
        window.removeEventListener("pagehide", this.handlePageHide);
        this.flushPreferences({ keepalive: true });
        this.releaseMediaFrame();
    }

    async open(event) {
        const campaignUrlId = Number(event.detail?.campaignUrlId);
        const url = event.detail?.url;
        const actionButton = event.detail?.actionButton;
        const returnFocusElement = event.detail?.returnFocusElement;

        if (
            this.isModalOpen
            || this.isLoadingPreferences
            || !Number.isInteger(campaignUrlId)
            || campaignUrlId < 1
            || typeof url !== "string"
            || url === ""
            || !(actionButton instanceof HTMLButtonElement)
        ) {
            return;
        }

        this.isLoadingPreferences = true;
        this.setPreferenceLoading(actionButton, true);

        try {
            await this.flushPreferences();
            const loaded = await this.fetchPreferences();

            this.resetDesigner();
            this.applyPreferences(loaded.preferences, loaded.logo);
            this.campaignUrlId = campaignUrlId;
            this.campaignUrl = url;
            this.urlTarget.textContent = url;
            this.urlTarget.title = url;
            this.returnFocusElement = returnFocusElement instanceof HTMLElement ? returnFocusElement : document.activeElement;
            this.isModalOpen = true;
            this.renderQrCode();

            this.showModal(this.closeButtonTarget);
        } catch {
            this.showPreferenceLoadError(actionButton);
            requestAnimationFrame(() => actionButton.focus());
        } finally {
            this.setPreferenceLoading(actionButton, false);
            this.isLoadingPreferences = false;
        }
    }

    closeFromOverlay(event) {
        if (event.target !== event.currentTarget || this.isExporting) {
            return;
        }

        MicroModal.close(modalId);
    }

    guardModalClose(event) {
        if (!this.isModalOpen || !this.isExporting || event.key !== "Escape") {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
    }

    preventSubmission(event) {
        event.preventDefault();
    }

    handleInput(event) {
        if (!event.target.matches("[data-qr-color-input]")) {
            return;
        }

        this.synchronizeColor(event.target);
    }

    handleChange(event) {
        if (event.target.matches("[data-qr-color-input]")) {
            return;
        }

        if (["background", "frame"].includes(event.target.name)) {
            this.updateStyleControls();
        }

        this.queueFormPreference(event.target.name);
        this.schedulePreview();
    }

    normalizeColor(event) {
        if (!event.target.matches('[data-qr-color-input="hex"]')) {
            return;
        }

        const colorControl = event.target.closest("[data-qr-color]");
        const picker = colorControl?.querySelector('[data-qr-color-input="picker"]');

        if (!picker) {
            return;
        }

        if (!hexColorPattern.test(event.target.value.trim())) {
            event.target.value = picker.value.toUpperCase();
            event.target.removeAttribute("aria-invalid");
            return;
        }

        event.target.value = event.target.value.trim().toUpperCase();
    }

    openMediaLibrary() {
        this.clearError();

        if (!window.wp || typeof window.wp.media !== "function") {
            this.showError(this.modalTarget.dataset.mediaLibraryMessage);
            return;
        }

        try {
            if (!this.mediaFrame) {
                this.mediaFrame = window.wp.media({
                    title: this.modalTarget.dataset.mediaTitle,
                    button: {
                        text: this.modalTarget.dataset.mediaButtonLabel,
                    },
                    library: {
                        type: "image",
                    },
                    multiple: false,
                });
                this.mediaFrame.on("select", this.selectMediaLogo, this);
                this.mediaFrame.on("close", this.handleMediaLibraryClosed, this);
            }

            this.isMediaLibraryOpen = true;
            MicroModal.close(modalId);
            this.mediaFrame.open();
        } catch {
            this.isMediaLibraryOpen = false;

            if (this.modalTarget.getAttribute("aria-hidden") === "true") {
                this.showModal(this.chooseLogoButtonTarget);
            }

            this.showError(this.modalTarget.dataset.mediaLibraryMessage);
        }
    }

    selectMediaLogo() {
        const selectedAttachment = this.mediaFrame.state().get("selection").first();
        const attachment = selectedAttachment?.toJSON();
        const logoUrl = this.normalizeMediaUrl(attachment?.sizes?.medium?.url || attachment?.url);
        const logoAttachmentId = Number(attachment?.id);

        if (!logoUrl || !Number.isInteger(logoAttachmentId) || logoAttachmentId < 1) {
            this.showError(this.modalTarget.dataset.mediaLibraryMessage);
            return;
        }

        this.logoData = logoUrl;
        this.logoAttachmentId = logoAttachmentId;
        this.pendingLogoDetails = {
            id: logoAttachmentId,
            url: logoUrl,
            name: attachment.filename || attachment.title || "Logo",
        };
        this.logoNameTarget.textContent = this.pendingLogoDetails.name;
        this.logoNameTarget.hidden = false;
        this.removeLogoButtonTarget.hidden = false;
        this.clearError();
        this.queuePreference("logo_id", logoAttachmentId);
        this.schedulePreview();
    }

    normalizeMediaUrl(source) {
        if (!source) {
            return "";
        }

        try {
            const url = new URL(source, window.location.href);

            if (!["http:", "https:"].includes(url.protocol)) {
                return "";
            }

            if (url.protocol === "http:" && window.location.protocol === "https:") {
                if (url.hostname === window.location.hostname) {
                    return `${window.location.origin}${url.pathname}${url.search}${url.hash}`;
                }

                url.protocol = "https:";
            }

            return url.href;
        } catch {
            return "";
        }
    }

    handleMediaLibraryClosed() {
        if (!this.isMediaLibraryOpen) {
            return;
        }

        this.isMediaLibraryOpen = false;

        if (this.isModalOpen) {
            this.showModal(this.chooseLogoButtonTarget);
        }
    }

    removeLogo() {
        this.logoData = "";
        this.logoAttachmentId = null;
        this.pendingLogoDetails = null;
        this.logoNameTarget.textContent = "";
        this.logoNameTarget.hidden = true;
        this.removeLogoButtonTarget.hidden = true;
        this.clearError();
        this.queuePreference("logo_id", null);
        this.schedulePreview();
    }

    async fetchPreferences() {
        const response = await fetch(ajaxurl, {
            method: "POST",
            body: new URLSearchParams(iawpActions.get_campaign_qr_code_preferences),
            credentials: "same-origin",
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error("QR code preferences could not be loaded.");
        }

        return this.validateLoadedPreferences(payload.data);
    }

    validateLoadedPreferences(data) {
        if (!data || typeof data !== "object" || Array.isArray(data)) {
            throw new Error("Invalid QR code preferences response.");
        }

        const rawPreferences = data.preferences;

        if (!rawPreferences || typeof rawPreferences !== "object" || Array.isArray(rawPreferences)) {
            throw new Error("Invalid QR code preferences response.");
        }

        const preferences = {};

        Object.entries(rawPreferences).forEach(([key, value]) => {
            preferences[key] = this.validatePreferenceValue(key, value);
        });

        Object.entries(this.pendingPreferenceChanges).forEach(([key, value]) => {
            if (value === null) {
                delete preferences[key];
            } else {
                preferences[key] = this.validatePreferenceValue(key, value);
            }
        });

        let logo = null;

        if (hasOwn(preferences, "logo_id")) {
            const rawLogo = hasOwn(this.pendingPreferenceChanges, "logo_id")
                ? this.pendingLogoDetails
                : data.logo;

            if (
                !rawLogo
                || typeof rawLogo !== "object"
                || Array.isArray(rawLogo)
                || rawLogo.id !== preferences.logo_id
                || typeof rawLogo.name !== "string"
            ) {
                throw new Error("Invalid QR code logo response.");
            }

            const logoUrl = this.normalizeMediaUrl(rawLogo.url);

            if (!logoUrl) {
                throw new Error("Invalid QR code logo URL.");
            }

            logo = {
                id: rawLogo.id,
                url: logoUrl,
                name: rawLogo.name || "Logo",
            };
        }

        return { preferences, logo };
    }

    validatePreferenceValue(key, value) {
        if (key === "size") {
            if (!Number.isInteger(value) || !allowedPreferenceValues.size.includes(value)) {
                throw new Error("Invalid QR code size preference.");
            }

            return value;
        }

        if (choicePreferenceNames.includes(key)) {
            if (typeof value !== "string" || !allowedPreferenceValues[key].includes(value)) {
                throw new Error("Invalid QR code choice preference.");
            }

            return value;
        }

        if (colorPreferenceKeys.includes(key)) {
            if (typeof value !== "string" || !hexColorPattern.test(value)) {
                throw new Error("Invalid QR code color preference.");
            }

            return value.toUpperCase();
        }

        if (key === "logo_id") {
            if (!Number.isInteger(value) || value < 1) {
                throw new Error("Invalid QR code logo preference.");
            }

            return value;
        }

        throw new Error("Unknown QR code preference.");
    }

    applyPreferences(preferences, logo) {
        formPreferenceNames.forEach((key) => {
            if (hasOwn(preferences, key)) {
                this.formTarget.elements[key].value = String(preferences[key]);
            }
        });

        Object.entries(colorPreferenceNames).forEach(([controlName, preferenceName]) => {
            if (!hasOwn(preferences, preferenceName)) {
                return;
            }

            const colorControl = this.colorControl(controlName);
            const picker = colorControl.querySelector('[data-qr-color-input="picker"]');
            const hex = colorControl.querySelector('[data-qr-color-input="hex"]');

            picker.value = preferences[preferenceName];
            hex.value = preferences[preferenceName];
        });

        if (logo) {
            this.logoAttachmentId = logo.id;
            this.logoData = logo.url;
            this.logoNameTarget.textContent = logo.name;
            this.logoNameTarget.hidden = false;
            this.removeLogoButtonTarget.hidden = false;
        }

        this.synchronizeAllColors();
        this.updateStyleControls();
    }

    setPreferenceLoading(button, isLoading) {
        if (isLoading) {
            clearTimeout(this.loadErrorLabelTimer);
            this.restorePreferenceLoadLabel();
            this.setPreferenceButtonLabel(button, button.dataset.qrLabel);
        }

        button.disabled = isLoading;
        button.toggleAttribute("aria-busy", isLoading);
        button.classList.toggle("loading", isLoading);
    }

    showPreferenceLoadError(button) {
        clearTimeout(this.loadErrorLabelTimer);
        this.restorePreferenceLoadLabel();
        this.loadErrorButton = button;
        button.classList.add("load-error");
        this.setPreferenceButtonLabel(button, button.dataset.qrErrorLabel);
        this.loadErrorLabelTimer = setTimeout(() => {
            this.restorePreferenceLoadLabel();
        }, loadErrorLabelDuration);
    }

    restorePreferenceLoadLabel() {
        if (this.loadErrorButton?.isConnected) {
            this.loadErrorButton.classList.remove("load-error");
            this.setPreferenceButtonLabel(this.loadErrorButton, this.loadErrorButton.dataset.qrLabel);
        }

        this.loadErrorButton = null;
    }

    setPreferenceButtonLabel(button, label) {
        button.title = label;
        button.setAttribute("aria-label", label);
    }

    downloadPng(event) {
        this.download("png", event.currentTarget);
    }

    downloadSvg(event) {
        this.download("svg", event.currentTarget);
    }

    schedulePreview() {
        if (!this.isModalOpen) {
            return;
        }

        cancelAnimationFrame(this.renderFrame);
        this.renderFrame = requestAnimationFrame(() => this.renderQrCode());
    }

    renderQrCode() {
        const configuration = this.configuration();
        const sequence = ++this.renderSequence;

        this.clearError();
        this.qrReady = false;
        this.updateDownloadButtons();
        this.previewTarget.setAttribute("aria-busy", "true");
        this.previewTarget.replaceChildren();

        const qrCode = new QRCodeStyling({ type: "svg" });

        qrCode.applyExtension((svg) => this.decorateSvg(svg, configuration));
        qrCode.append(this.previewTarget);
        qrCode.update({
            width: configuration.size,
            height: configuration.size,
            type: "svg",
            data: this.campaignUrl,
            image: this.logoData,
            margin: Math.round(configuration.size * 0.08),
            qrOptions: {
                errorCorrectionLevel: "M",
            },
            imageOptions: {
                hideBackgroundDots: true,
                imageSize: 0.25,
                margin: Math.max(2, Math.round(configuration.size * 0.012)),
                saveAsBlob: true,
            },
            dotsOptions: {
                color: configuration.patternColor,
                type: configuration.pattern,
                roundSize: false,
            },
            cornersSquareOptions: {
                color: configuration.eyeColor,
                type: configuration.eyeSquare,
            },
            cornersDotOptions: {
                color: configuration.eyeColor,
                type: configuration.eyeDot,
            },
            backgroundOptions: {
                color: configuration.backgroundColor,
            },
        });

        this.qrCode = qrCode;
        qrCode
            .getRawData("svg")
            .then(() => {
                if (sequence !== this.renderSequence || qrCode !== this.qrCode) {
                    return;
                }

                this.qrReady = true;
                this.previewTarget.removeAttribute("aria-busy");
                this.updateDownloadButtons();
            })
            .catch(() => {
                if (sequence !== this.renderSequence || qrCode !== this.qrCode) {
                    return;
                }

                this.previewTarget.removeAttribute("aria-busy");
                this.showError(this.modalTarget.dataset.generationMessage, { disableDownloads: true });
            });
    }

    configuration() {
        const size = Number(this.formTarget.elements.size.value);
        const eyeStyle = this.formTarget.elements.eyes.value;
        const backgroundStyle = this.formTarget.elements.background.value;
        const eyeTypes = {
            square: { square: "square", dot: "square" },
            rounded: { square: "extra-rounded", dot: "dot" },
            circular: { square: "dot", dot: "dot" },
        }[eyeStyle];

        return {
            size: [256, 512, 1024].includes(size) ? size : 256,
            pattern: this.formTarget.elements.pattern.value,
            eyeSquare: eyeTypes.square,
            eyeDot: eyeTypes.dot,
            patternColor: this.colorValue("pattern"),
            eyeColor: this.colorValue("eyes"),
            backgroundColor: {
                none: "transparent",
                white: "#FFFFFF",
                custom: this.colorValue("background"),
            }[backgroundStyle],
            frameColor: this.colorValue("frame"),
            frame: this.formTarget.elements.frame.value,
        };
    }

    decorateSvg(svg, configuration) {
        this.prepareSvg(svg);

        if (configuration.frame === "none") {
            return;
        }

        const size = configuration.size;
        const borderInset = size * 0.025;
        const borderWidth = Math.max(2, size * 0.015);
        const backgroundInset = borderInset + borderWidth / 2;
        const contentInset = size * 0.0225;
        const contentSize = size - contentInset * 2;
        const contentGroup = document.createElementNS(svgNamespace, "g");
        const renderableChildren = Array.from(svg.children).filter((child) => child.tagName.toLowerCase() !== "defs");

        renderableChildren.forEach((child) => contentGroup.appendChild(child));
        contentGroup.setAttribute("transform", `translate(${contentInset} ${contentInset}) scale(${contentSize / size})`);

        const background = document.createElementNS(svgNamespace, "rect");
        background.setAttribute("x", String(backgroundInset));
        background.setAttribute("y", String(backgroundInset));
        background.setAttribute("width", String(size - backgroundInset * 2));
        background.setAttribute("height", String(size - backgroundInset * 2));
        background.setAttribute("fill", configuration.backgroundColor);

        const border = document.createElementNS(svgNamespace, "rect");
        border.setAttribute("x", String(borderInset));
        border.setAttribute("y", String(borderInset));
        border.setAttribute("width", String(size - borderInset * 2));
        border.setAttribute("height", String(size - borderInset * 2));
        border.setAttribute("fill", "none");
        border.setAttribute("stroke", configuration.frameColor);
        border.setAttribute("stroke-width", String(borderWidth));
        border.setAttribute("stroke-linejoin", "round");

        const clipRect = document.createElementNS(svgNamespace, "rect");
        clipRect.setAttribute("x", String(backgroundInset));
        clipRect.setAttribute("y", String(backgroundInset));
        clipRect.setAttribute("width", String(size - backgroundInset * 2));
        clipRect.setAttribute("height", String(size - backgroundInset * 2));

        if (configuration.frame === "rounded") {
            const borderRadius = size * 0.075;
            const backgroundRadius = Math.max(0, borderRadius - borderWidth / 2);

            border.setAttribute("rx", String(borderRadius));
            background.setAttribute("rx", String(backgroundRadius));
            clipRect.setAttribute("rx", String(backgroundRadius));
        }

        const clipPathId = `campaign-qr-frame-clip-${++frameClipPathSequence}`;
        const clipPath = document.createElementNS(svgNamespace, "clipPath");
        const frameContentGroup = document.createElementNS(svgNamespace, "g");
        const definitions = svg.querySelector("defs");

        clipPath.setAttribute("id", clipPathId);
        clipPath.setAttribute("clipPathUnits", "userSpaceOnUse");
        clipPath.appendChild(clipRect);
        definitions.appendChild(clipPath);
        frameContentGroup.setAttribute("clip-path", `url(#${clipPathId})`);
        frameContentGroup.appendChild(background);
        frameContentGroup.appendChild(contentGroup);

        svg.appendChild(frameContentGroup);
        svg.appendChild(border);
    }

    prepareSvg(svg) {
        svg.querySelectorAll("clipPath").forEach((clipPath) => {
            clipPath.setAttribute("clipPathUnits", "userSpaceOnUse");
        });

        svg.querySelectorAll("[clip-path]").forEach((element) => {
            const reference = element.getAttribute("clip-path");

            if (reference) {
                element.setAttribute("clip-path", reference.replace(/^url\((['"])(#[^'"]+)\1\)$/, "url($2)"));
            }
        });

        svg.querySelectorAll('clipPath[id^="clip-path-corners-"] > *').forEach((element) => {
            element.setAttribute("shape-rendering", "geometricPrecision");
        });
    }

    async download(extension, button) {
        if (this.isExporting || !this.qrReady || !this.qrCode) {
            return;
        }

        const qrCode = this.qrCode;
        const size = this.configuration().size;

        this.clearError();
        this.setExporting(true, button);

        try {
            const svgBlob = await qrCode.getRawData("svg");

            if (!(svgBlob instanceof Blob)) {
                throw new Error("QR code SVG was not created.");
            }

            const blob = extension === "svg" ? svgBlob : await this.rasterizeSvg(svgBlob, size);

            this.downloadBlob(blob, `campaign-${this.campaignUrlId}-qr-code.${extension}`);
        } catch {
            this.showError(this.modalTarget.dataset.downloadMessage);
        } finally {
            this.setExporting(false);
        }
    }

    rasterizeSvg(svgBlob, size) {
        return new Promise((resolve, reject) => {
            const imageUrl = URL.createObjectURL(svgBlob);
            const image = new Image();

            image.onload = () => {
                const canvas = document.createElement("canvas");
                const context = canvas.getContext("2d");

                URL.revokeObjectURL(imageUrl);

                if (!context) {
                    reject(new Error("Canvas is unavailable."));
                    return;
                }

                canvas.width = size;
                canvas.height = size;
                context.drawImage(image, 0, 0, size, size);
                canvas.toBlob((blob) => {
                    if (blob) {
                        resolve(blob);
                    } else {
                        reject(new Error("PNG could not be created."));
                    }
                }, "image/png");
            };

            image.onerror = () => {
                URL.revokeObjectURL(imageUrl);
                reject(new Error("SVG could not be rasterized."));
            };

            image.src = imageUrl;
        });
    }

    downloadBlob(blob, filename) {
        const downloadUrl = URL.createObjectURL(blob);
        const link = document.createElement("a");

        link.href = downloadUrl;
        link.download = filename;
        link.hidden = true;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
    }

    synchronizeColor(input) {
        const colorControl = input.closest("[data-qr-color]");
        const picker = colorControl?.querySelector('[data-qr-color-input="picker"]');
        const hex = colorControl?.querySelector('[data-qr-color-input="hex"]');

        if (!picker || !hex) {
            return;
        }

        if (input.dataset.qrColorInput === "picker") {
            hex.value = picker.value.toUpperCase();
            hex.removeAttribute("aria-invalid");
            this.queueColorPreference(colorControl, picker.value);
            this.schedulePreview();
            return;
        }

        const value = hex.value.trim();

        if (!hexColorPattern.test(value)) {
            hex.setAttribute("aria-invalid", "true");
            return;
        }

        picker.value = value;
        hex.removeAttribute("aria-invalid");
        this.queueColorPreference(colorControl, value);
        this.schedulePreview();
    }

    preferenceValues() {
        return {
            size: Number(this.formTarget.elements.size.value),
            pattern: this.formTarget.elements.pattern.value,
            eyes: this.formTarget.elements.eyes.value,
            background: this.formTarget.elements.background.value,
            pattern_color: this.colorValue("pattern").toUpperCase(),
            eye_color: this.colorValue("eyes").toUpperCase(),
            background_color: this.colorValue("background").toUpperCase(),
            frame_color: this.colorValue("frame").toUpperCase(),
            frame: this.formTarget.elements.frame.value,
            logo_id: this.logoAttachmentId,
        };
    }

    queueFormPreference(name) {
        if (!formPreferenceNames.includes(name)) {
            return;
        }

        const values = this.preferenceValues();

        this.queuePreference(name, values[name]);
    }

    queueColorPreference(colorControl, value) {
        const preferenceName = colorPreferenceNames[colorControl.dataset.qrColor];

        if (!preferenceName || !hexColorPattern.test(value)) {
            return;
        }

        this.queuePreference(preferenceName, value.toUpperCase());
    }

    queuePreference(key, value) {
        if (!this.frontendDefaults) {
            return;
        }

        this.pendingPreferenceChanges[key] = value === this.frontendDefaults[key] ? null : value;
        clearTimeout(this.preferenceSaveTimer);
        this.preferenceSaveTimer = setTimeout(() => this.flushPreferences(), preferenceSaveDelay);
    }

    async flushPreferences({ keepalive = false } = {}) {
        clearTimeout(this.preferenceSaveTimer);
        this.preferenceSaveTimer = null;

        if (this.preferenceSavePromise) {
            await this.preferenceSavePromise;
        }

        if (Object.keys(this.pendingPreferenceChanges).length === 0) {
            return true;
        }

        const changes = this.pendingPreferenceChanges;

        this.pendingPreferenceChanges = {};
        const request = this.updatePreferences(changes, { keepalive });
        this.preferenceSavePromise = request;
        const wasSaved = await request;

        if (!wasSaved) {
            Object.entries(changes).forEach(([key, value]) => {
                if (!hasOwn(this.pendingPreferenceChanges, key)) {
                    this.pendingPreferenceChanges[key] = value;
                }
            });
        }

        if (this.preferenceSavePromise === request) {
            this.preferenceSavePromise = null;
        }

        return wasSaved;
    }

    async updatePreferences(changes, { keepalive = false } = {}) {
        const data = {
            ...iawpActions.update_campaign_qr_code_preferences,
            preferences: JSON.stringify(changes),
        };

        try {
            const response = await fetch(ajaxurl, {
                method: "POST",
                body: new URLSearchParams(data),
                credentials: "same-origin",
                keepalive,
            });
            const payload = await response.json();

            return response.ok && payload.success;
        } catch {
            return false;
        }
    }

    colorControl(name) {
        return this.formTarget.querySelector(`[data-qr-color="${name}"]`);
    }

    colorValue(name) {
        return this.colorControl(name).querySelector('[data-qr-color-input="picker"]').value;
    }

    updateStyleControls() {
        this.colorControl("background").hidden = this.formTarget.elements.background.value !== "custom";
        this.colorControl("frame").hidden = this.formTarget.elements.frame.value === "none";
    }

    updateDownloadButtons() {
        this.downloadButtonTargets.forEach((button) => {
            button.disabled = this.isExporting || !this.qrReady;
        });
    }

    setExporting(isExporting, activeButton = null) {
        this.isExporting = isExporting;
        this.modalTarget.classList.toggle("exporting", isExporting);
        this.closeButtonTarget.disabled = isExporting;
        this.cancelButtonTarget.disabled = isExporting;
        this.downloadButtonTargets.forEach((button) => button.classList.toggle("loading", isExporting && button === activeButton));
        this.updateDownloadButtons();
    }

    showError(message, { disableDownloads = false } = {}) {
        this.errorTarget.textContent = message;
        this.errorTarget.hidden = false;

        if (disableDownloads) {
            this.qrReady = false;
        }

        this.updateDownloadButtons();
    }

    clearError() {
        this.errorTarget.textContent = "";
        this.errorTarget.hidden = true;
    }

    resetDesigner() {
        cancelAnimationFrame(this.renderFrame);
        this.renderSequence += 1;
        this.qrCode = null;
        this.qrReady = false;
        this.logoData = "";
        this.logoAttachmentId = null;
        this.formTarget.reset();
        this.logoNameTarget.textContent = "";
        this.logoNameTarget.hidden = true;
        this.removeLogoButtonTarget.hidden = true;
        this.previewTarget.replaceChildren();
        this.previewTarget.removeAttribute("aria-busy");
        this.urlTarget.textContent = "";
        this.urlTarget.removeAttribute("title");
        this.clearError();
        this.synchronizeAllColors();
        this.updateStyleControls();
        this.setExporting(false);
    }

    synchronizeAllColors() {
        this.formTarget.querySelectorAll("[data-qr-color]").forEach((colorControl) => {
            const picker = colorControl.querySelector('[data-qr-color-input="picker"]');
            const hex = colorControl.querySelector('[data-qr-color-input="hex"]');

            hex.value = picker.value.toUpperCase();
            hex.removeAttribute("aria-invalid");
        });
    }

    showModal(focusElement) {
        MicroModal.show(modalId, {
            disableScroll: true,
            onShow: () => focusElement.focus(),
            onClose: () => this.handleClosed(),
        });
    }

    releaseMediaFrame() {
        if (!this.mediaFrame) {
            return;
        }

        this.mediaFrame.off("select", this.selectMediaLogo, this);
        this.mediaFrame.off("close", this.handleMediaLibraryClosed, this);
        this.mediaFrame = null;
    }

    handleClosed() {
        if (this.isMediaLibraryOpen) {
            return;
        }

        this.isModalOpen = false;
        const returnFocusElement = this.returnFocusElement;

        this.flushPreferences();
        this.resetDesigner();
        this.releaseMediaFrame();
        this.campaignUrlId = null;
        this.campaignUrl = "";
        this.returnFocusElement = null;

        requestAnimationFrame(() => {
            if (returnFocusElement instanceof HTMLElement && returnFocusElement.isConnected) {
                returnFocusElement.focus();
            }
        });
    }
}
