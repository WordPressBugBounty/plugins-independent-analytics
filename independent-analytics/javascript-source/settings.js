import {UserRoles} from './modules/user-roles';
import {FieldDuplicator} from './modules/duplicate-field';
import {EmailReports} from './modules/email-reports';
import {downloadCSV} from './download'

jQuery(function ($) {
    UserRoles.setup();
    FieldDuplicator.setup();
    EmailReports.setup();
});

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById("iawp-export-views").addEventListener('click', function (e) {
        const button = e.target;

        button.textContent = iawpText.exportingPages
        button.setAttribute('disabled', 'disabled');

        const data = {
            ...iawpActions.export_pages
        };

        jQuery.post(ajaxurl, data, function (response) {
            downloadCSV('exported-pages.csv', response);
            button.textContent = iawpText.exportPages
            button.removeAttribute('disabled');
        });
    });

    document.getElementById("iawp-export-referrers").addEventListener('click', function (e) {
        const button = e.target;

        button.textContent = iawpText.exportingReferrers
        button.setAttribute('disabled', 'disabled');


        const data = {
            ...iawpActions.export_referrers
        };

        jQuery.post(ajaxurl, data, function (response) {
            downloadCSV('exported-referrers.csv', response);
            button.textContent = iawpText.exportReferrers
            button.removeAttribute('disabled');
        });
    });

    document.getElementById("iawp-export-geo").addEventListener('click', function (e) {
        const button = e.target;

        button.textContent = iawpText.exportingGeolocations
        button.setAttribute('disabled', 'disabled');


        const data = {
            ...iawpActions.export_geo
        };

        jQuery.post(ajaxurl, data, function (response) {
            downloadCSV('exported-geo.csv', response);
            button.textContent = iawpText.exportGeolocations
            button.removeAttribute('disabled');
        });
    });

    document.getElementById("iawp-export-devices").addEventListener('click', function (e) {
        const button = e.target;

        button.textContent = iawpText.exportingDevices
        button.setAttribute('disabled', 'disabled');


        const data = {
            ...iawpActions.export_devices
        };

        jQuery.post(ajaxurl, data, function (response) {
            downloadCSV('exported-devices.csv', response);
            button.textContent = iawpText.exportDevices
            button.removeAttribute('disabled');
        });
    });

    const campaignExportButton = document.getElementById("iawp-export-campaigns")

    if (campaignExportButton) {
        campaignExportButton.addEventListener('click', function (e) {
            const button = e.target;

            button.textContent = iawpText.exportingCampaigns
            button.setAttribute('disabled', 'disabled');

            const data = {
                ...iawpActions.export_campaigns,
            };

            jQuery.post(ajaxurl, data, (response) => {
                downloadCSV('exported-campaigns.csv', response);
                button.textContent = iawpText.exportCampaigns
                button.removeAttribute('disabled');
            });
        });
    }

    const clickExportButton = document.getElementById("iawp-export-clicks")

    if (clickExportButton) {
        clickExportButton.addEventListener('click', function (e) {
            const button = e.target;

            button.textContent = iawpText.exportingClicks
            button.setAttribute('disabled', 'disabled');

            const data = {
                ...iawpActions.export_clicks
            };

            jQuery.post(ajaxurl, data, (response) => {
                downloadCSV('exported-clicks.csv', response);
                button.textContent = iawpText.exportClicks
                button.removeAttribute('disabled');
            });
        });
    }
});