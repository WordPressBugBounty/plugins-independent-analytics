import {Controller} from "@hotwired/stimulus"
import {isDarkMode} from "../utils/appearance";

export default class extends Controller {
    static targets = ["chart"]
    static values = {
        data: Array,
        flagsUrl: String,
        locale: String
    }

    mapInstance = null

    connect() {
        this.resizeObserver = new ResizeObserver(() => {
            this.drawChart()
        });

        google.charts.load('current', {
            'packages': ['geochart'],
        })

        google.charts.setOnLoadCallback(() => {
            this.drawChart()
            this.resizeObserver.observe(this.element);
        })
    }

    disconnect() {
        this.resizeObserver.disconnect();
        window.mapInstances = window.mapInstances.filter(mapInstance => {
            return mapInstance !== this.mapInstance
        })
    }

    drawChart = () => {
        // Empty the element to fix glitch where chart height wouldn't be resized
        this.chartTarget.innerHTML = '';

        // Remove the map
        if(this.mapInstance) {
            const index = window.mapInstances.indexOf(this.mapInstance);

            if(index > -1) {
                window.mapInstances.splice(index, 1)
            }
        }

        let dataTable = new google.visualization.DataTable();

        dataTable.addColumn('string', 'country');
        dataTable.addColumn('number', 'views');
        dataTable.addColumn({
            'type': 'string',
            'role': 'tooltip',
            'p': {'html': true}
        })

        dataTable.addRows(
            this.convertCountryDataToRows(this.dataValue)
        );

        const options = {
            displayMode: 'regions',
            tooltip: {
                isHtml: true,
                showTitle: false
            },
            backgroundColor: isDarkMode() ? '#373040' : '#FFFFFF',
            datalessRegionColor: isDarkMode() ? '#695C7A' : undefined,
            colorAxis: {
                colors: isDarkMode() ? ['#AC9CC9', '#9E66FF'] : ['#C4ABED', '#5223A0']
            },
            legend: {
                numberFormat: `${iawpText.views}: #`,
                textStyle: {
                    color: isDarkMode() ? '#FFFFFF' : '#000000',
                    strokeWidth: 0
                }
            },
        }

        const map = new google.visualization.GeoChart(this.chartTarget)

        map.draw(dataTable, options)

        if(!window.mapInstances) {
            window.mapInstances = []
        }

        this.mapInstance = map
        window.mapInstances.push(map)
    }

    convertCountryDataToRows(countryData) {
        return countryData.map(country => {
            return [
                country.country_code,
                country.views,
                this.getTooltipMarkup(country)
            ]
        })
    }

    getTooltipMarkup(country) {
        const formatted_views = this.formatNumber(country['views'])
        const formatted_visitors = this.formatNumber(country['visitors'])
        const formatted_sessions = this.formatNumber(country['sessions'])
        const flagUrl = this.flagsUrlValue + '/' + country['country_code'].toLowerCase() + '.svg'

        return `
        <div class="iawp-geo-chart-tooltip">
            <img src="${flagUrl}" alt="Country flag"/>
            <h1>${country['country']}</h1>
            <p><span>${iawpText.views}: </span>${formatted_views}</p>
            <p><span>${iawpText.visitors}: </span>${formatted_visitors}</p>
            <p><span>${iawpText.sessions}: </span>${formatted_sessions}</p>
        </div>
        `;

        return title.outerHTML
    }

    formatNumber(number) {
        return new Intl.NumberFormat(this.localeValue, {
            maximumFractionDigits: 0
        }).format(number);
    }
}