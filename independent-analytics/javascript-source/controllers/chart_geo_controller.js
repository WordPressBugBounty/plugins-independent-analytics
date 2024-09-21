import {Controller} from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["chart"]
    static values = {
        data: Array,
        darkMode: Boolean
    }

    connect() {
        google.charts.load('current', {
            'packages': ['geochart'],
        })
        google.charts.setOnLoadCallback(() => {
            this.drawChart()
        })
        window.addEventListener('resize', this.drawChart)
    }

    disconnect() {
        window.removeEventListener('resize', this.drawChart)
    }

    drawChart = () => {
        // Empty the element to fix glitch where chart height wouldn't be resized
        this.chartTarget.innerHTML = '';

        let dataTable = new google.visualization.DataTable();

        dataTable.addColumn('string', 'country');
        dataTable.addColumn('number', 'views');
        dataTable.addColumn({
            'type': 'string',
            'role': 'tooltip',
            'p': {'html': true}
        })

        dataTable.addRows(this.dataValue);

        const options = {
            displayMode: 'regions',
            tooltip: {
                isHtml: true,
                showTitle: false
            },
            backgroundColor: this.darkModeValue ? '#373040' : '#FFFFFF',
            datalessRegionColor: this.darkModeValue ? '#695C7A' : undefined,
            colorAxis: {
                colors: this.darkModeValue ? ['#AC9CC9', '#9E66FF'] : ['#C4ABED', '#5223A0']
            },
            legend: {
                numberFormat: `${iawpText.views}: #`,
                textStyle: {
                    color: this.darkModeValue ? '#FFFFFF' : '#000000',
                    strokeWidth: 0
                }
            }
        }

        const chart = new google.visualization.GeoChart(this.chartTarget)

        chart.draw(dataTable, options)
        window.iawp_geo_chart = chart
    }
}