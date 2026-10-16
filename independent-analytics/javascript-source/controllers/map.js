import { Controller } from "@hotwired/stimulus";
import { isDarkMode } from "../utils/appearance";
import svgMap from "svgmap";

export default class extends Controller {
    static targets = ["chart"];
    static values = {
        data: Array,
        flagsUrl: String,
        locale: String,
    };

    map = null;
    resizeObserver = null;

    connect() {
        if (this.map) {
            this.resizeObserver.observe(this.chartTarget);
            return;
        }

        this.initializeMap();

        window.iawpMaps = window.iawpMaps || [];
        window.iawpMaps.push(this.map);

        this.resizeObserver.observe(this.chartTarget);
    }

    disconnect() {
        this.resizeObserver.disconnect();
        window.iawpMaps = window.iawpMaps || [];
        window.iawpMaps = window.iawpMaps.filter((map) => {
            return map !== this.map;
        });
    }

    initializeMap() {
        const id = "iawp-map-" + Math.random().toString();
        this.chartTarget.setAttribute("id", id);

        const chartValues = {};

        this.dataValue.forEach((country) => {
            chartValues[country.country_code] = {
                visitors: country.visitors,
                views: country.views,
                sessions: country.sessions,
            };
        });

        // map.applyData(newData);

        this.map = new svgMap({
            targetElementID: id,
            allowInteraction: false,
            initialZoom: 1.1,
            colorMax: "#5123A0",
            colorMin: "#bea4eb",
            colorNoData: "#dedede",
            ratioType: "log",
            data: {
                data: {
                    visitors: {
                        name: "Visitors",
                    },
                    views: {
                        name: "Views",
                    },
                    sessions: {
                        name: "Sessions",
                    },
                },
                applyData: "visitors",
                values: chartValues,
            },
            onGetTooltip: (tooltipDiv, countryCode, countryValues) => {
                const country = this.dataValue.find((country) => {
                    return country["country_code"] === countryCode;
                });
                const countryName = country
                    ? country["country"]
                    : this.map.getCountryName(countryCode);

                const flagUrl = this.flagsUrlValue + "/" + countryCode.toLowerCase() + ".svg";

                // Render an empty tooltip when there's no country data
                if (!country) {
                    const templateString = `
                        <div class="iawp-geo-chart-tooltip">
                            <img src="${flagUrl}" alt="Country flag"/>
                            <h1>${countryName}</h1>
                            <p>No data available</p>
                        </div>
                    `;

                    const templateDocument = new DOMParser().parseFromString(templateString, "text/html");

                    return templateDocument.body.firstElementChild;
                }

                const formatted_views = this.formatNumber(country["views"]);
                const formatted_visitors = this.formatNumber(country["visitors"]);
                const formatted_sessions = this.formatNumber(country["sessions"]);

                const templateString = `
                    <div class="iawp-geo-chart-tooltip">
                        <img src="${flagUrl}" alt="Country flag"/>
                        <h1>${countryName}</h1>
                        <div class="iawp-geo-chart-tooltip-table">
                            <span>${iawpText.views}</span><span>${formatted_views}</span>
                            <span>${iawpText.visitors}</span><span>${formatted_visitors}</span>
                            <span>${iawpText.sessions}</span><span>${formatted_sessions}</span>
                        </div>
                    </div>
                `;

                const templateDocument = new DOMParser().parseFromString(templateString, "text/html");

                return templateDocument.body.firstElementChild;
            },
        });

        this.resizeObserver = new ResizeObserver(() => {
            if (!this.chartTarget.checkVisibility()) {
                return;
            }

            this.map.mapPanZoom.resize();
            this.map.mapPanZoom.fit();
            this.map.mapPanZoom.center();
            this.map.mapPanZoom.zoom(1.1);
        });
    }

    dataValueChanged(newData) {
        if (!this.map) {
            return;
        }

        const chartValues = {};

        newData.forEach((country) => {
            chartValues[country.country_code] = {
                visitors: country.visitors,
                views: country.views,
                sessions: country.sessions,
            };
        });

        this.map.options.data.values = chartValues
        this.map.applyData(this.map.options.data);
    }

    formatNumber(number) {
        return new Intl.NumberFormat(this.localeValue, {
            maximumFractionDigits: 0,
        }).format(number);
    }
}
