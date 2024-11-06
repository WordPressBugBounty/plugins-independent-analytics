import {Controller} from "@hotwired/stimulus"
import {downloadCSV} from '../download'
import html2pdf from 'html2pdf.js';

class Report_Controller extends Controller {
    /**
     * Store the jQuery request so that it can be canceled if a new report is requested before the
     * current one is fetched.
     */
    static request;
    static targets = ['loadMore', 'exportCSV', 'exportPDF', 'spinner']
    static values = {
        name: String,
        relativeRangeId: String,
        exactStart: String,
        exactEnd: String,
        group: String,
        chartInterval: String,
        sortColumn: String,
        sortDirection: String,
        columns: Array,
        quickStats: Array,
        primaryChartMetricId: String,
        secondaryChartMetricId: String,
        filters: Array
    }

    exactStart = undefined
    exactEnd = undefined
    relativeRangeId = undefined
    columns = undefined
    quickStats = undefined
    filters = []
    sortColumn = undefined
    sortDirection = undefined
    group = undefined
    chartInterval = undefined
    primaryChartMetricId = undefined
    secondaryChartMetricId = undefined
    page = 1

    connect() {
        // If any values are empty strings, set them equal to undefined instead
        this.exactStart = this.exactStartValue === "" ? undefined : this.exactStartValue;
        this.exactEnd = this.exactEndValue === "" ? undefined : this.exactEndValue;
        this.relativeRangeId = this.relativeRangeIdValue === "" ? undefined : this.relativeRangeIdValue
        this.columns = this.columnsValue
        this.quickStats = this.quickStatsValue
        this.group = this.groupValue
        this.chartInterval = this.chartIntervalValue
        this.sortColumn = this.sortColumnValue
        this.sortDirection = this.sortDirectionValue
        this.primaryChartMetricId = this.primaryChartMetricIdValue
        this.secondaryChartMetricId = this.secondaryChartMetricIdValue
        this.filters = this.filtersValue
        document.addEventListener('iawp:changeDates', this.datesChanged)
        document.addEventListener('iawp:changeColumns', this.columnsChanged)
        document.addEventListener('iawp:changeQuickStats', this.quickStatsChanged)
        document.addEventListener('iawp:changeFilters', this.filtersChanged)
        document.addEventListener('iawp:changeSort', this.sortChanged)
        document.addEventListener('iawp:changeGroup', this.changeGroup)
        document.addEventListener('iawp:changeChartInterval', this.changeChartInterval)
        document.addEventListener('iawp:changePrimaryChartMetric', this.changePrimaryChartMetric)
        document.addEventListener('iawp:changeSecondaryChartMetric', this.changeSecondaryChartMetric)
        document.addEventListener('iawp:fetchingReport', this.onFetchingReport)
        setTimeout(() => {
            this.fetch({
                tableOnly: this.filters.length === 0,
                showLoadingOverlay: false
            })
        }, 0)
    }

    disconnect() {
        document.removeEventListener('iawp:changeDates', this.datesChanged)
        document.removeEventListener('iawp:changeColumns', this.columnsChanged)
        document.removeEventListener('iawp:changeQuickStats', this.quickStatsChanged)
        document.removeEventListener('iawp:changeFilters', this.filtersChanged)
        document.removeEventListener('iawp:changeSort', this.sortChanged)
        document.removeEventListener('iawp:changeGroup', this.changeGroup)
        document.removeEventListener('iawp:changeChartInterval', this.changeChartInterval)
        document.removeEventListener('iawp:changePrimaryChartMetric', this.changePrimaryChartMetric)
        document.removeEventListener('iawp:changeSecondaryChartMetric', this.changeSecondaryChartMetric)
        document.removeEventListener('iawp:fetchingReport', this.onFetchingReport)
    }

    emitChangedOption(detail) {
        document.dispatchEvent(
            new CustomEvent('iawp:changedOption', {
                detail
            })
        )
    }

    changePrimaryChartMetric = (e) => {
        this.primaryChartMetricId = e.detail.primaryChartMetricId
        this.emitChangedOption({
            'primary_chart_metric_id': this.primaryChartMetricId
        })
    }

    changeSecondaryChartMetric = (e) => {
        this.secondaryChartMetricId = e.detail.secondaryChartMetricId
        this.emitChangedOption({
            'secondary_chart_metric_id': this.secondaryChartMetricId
        })
    }

    datesChanged = (e) => {
        this.exactStart = e.detail.exactStart
        this.exactEnd = e.detail.exactEnd
        this.relativeRangeId = e.detail.relativeRangeId
        this.page = 1;

        this.emitChangedOption({
            'exact_start': this.exactStart || null,
            'exact_end': this.exactEnd || null,
            'relative_range_id': this.relativeRangeId || null
        })
        this.fetch({newDateRange: true})
    }

    columnsChanged = (e) => {
        this.columns = e.detail.optionIds
        this.emitChangedOption({
            'columns': this.columns
        })
    }

    quickStatsChanged = (e) => {
        this.quickStats = e.detail.optionIds
        this.emitChangedOption({
            'quick_stats': this.quickStats
        })
    }

    filtersChanged = (e) => {
        this.filters = e.detail.filters
        this.page = 1;

        this.emitChangedOption({
            'filters': this.filters
        })
        this.fetch({showLoadingOverlay: e.detail.showLoadingOverlay})
    }

    sortChanged = (e) => {
        this.sortColumn = e.detail.sortColumn
        this.sortDirection = e.detail.sortDirection
        this.page = 1;

        this.emitChangedOption({
            'sort_column': this.sortColumn,
            'sort_direction': this.sortDirection
        })
        this.fetch({tableOnly: true})
    }

    changeGroup = (e) => {
        if (this.group === e.detail.group) {
            return;
        }

        this.group = e.detail.group
        this.page = 1
        this.emitChangedOption({
            'group_name': this.group
        })
        this.fetch({newGroup: true});
    }

    changeChartInterval = (e) => {
        const chartInterval = e.detail.chartInterval

        if (this.chartInterval === chartInterval) {
            return;
        }

        this.chartInterval = chartInterval
        this.emitChangedOption({
            'chart_interval': this.chartInterval
        })
        this.fetch();
    }

    onFetchingReport = () => {
        this.spinnerTarget.classList.remove('hidden')

        document.addEventListener('iawp:fetchedReport', () => {
            this.spinnerTarget.classList.add('hidden')
        }, {once: true})
    }

    loadMore = () => {
        this.page = this.page + 1

        this.fetch({tableOnly: true})
    }

    fetch({
              tableOnly = false,
              showLoadingOverlay = true,
              newGroup = false,
              newDateRange = false
          } = {}) {

        if (showLoadingOverlay) {
            jQuery('#iawp-parent').addClass('loading');
        }

        const data = {
            ...iawpActions.filter,
            'filters': this.filters,
            'exact_start': this.exactStart,
            'exact_end': this.exactEnd,
            'is_new_date_range': newDateRange,
            'relative_range_id': this.relativeRangeId,
            'table_type': jQuery('#data-table').data('table-name'),
            'columns': this.columns,
            'report_quick_stats': ['visitors'], // TODO
            'primary_chart_metric_id': this.primaryChartMetricId,
            'secondary_chart_metric_id': this.secondaryChartMetricId,
            'sort_column': this.sortColumn,
            'quick_stats': this.quickStats,
            'sort_direction': this.sortDirection,
            'group': this.group,
            'is_new_group': newGroup,
            'chart_interval': this.chartInterval,
            'page': this.page,
        };

        if (Report_Controller.request) {
            Report_Controller.request.abort();
        }

        document.dispatchEvent(
            new CustomEvent('iawp:fetchingReport')
        )

        Report_Controller.request = jQuery.post(ajaxurl, data, (response) => {
            response = response.data

            this.columns = response.columns
            this.filters = response.filters
            this.chartInterval = response.chartInterval

            jQuery('#iawp-columns .row-number').text(response.totalNumberOfRows.toLocaleString());
            document.getElementById('data-table').setAttribute('data-total-number-of-rows', response.totalNumberOfRows)
            document.dispatchEvent(
                new CustomEvent('iawp:fetchedReport')
            )

            if (newGroup) {
                jQuery('#iawp-table-wrapper').replaceWith(response.table)
                jQuery('[data-plugin-group-options-option-type-value=columns]').replaceWith(response.columnsHTML)

                document.dispatchEvent(
                    new CustomEvent('iawp:changeColumns', {
                        detail: {
                            optionIds: this.columns
                        }
                    })
                )
            } else {
                jQuery('#iawp-rows').replaceWith(response.rows);
            }

            if (!tableOnly) {
                jQuery('#dates-button span:last-child').text(response.label)
                jQuery('#independent-analytics-chart').closest('.chart-container').replaceWith(response.chart);
                jQuery('#quick-stats').replaceWith(response.stats);
            }

            document.dispatchEvent(
                new CustomEvent('iawp:updateColumnsUserInterface')
            )

            document.dispatchEvent(
                new CustomEvent('iawp:groupChanged', {
                    detail: {
                        groupId: response.groupId
                    }
                })
            )

            document.dispatchEvent(
                new CustomEvent('iawp:filtersChanged', {
                    detail: {
                        filtersTemplateHTML: response.filtersTemplateHTML,
                        filtersButtonsHTML: response.filtersButtonsHTML,
                        filters: response.filters
                    }
                })
            )

            this.exportPDFTarget.removeAttribute('disabled')

            if (response.isLastPage) {
                this.loadMoreTarget.setAttribute('disabled', 'disabled')
            } else {
                this.loadMoreTarget.removeAttribute('disabled')
            }

            jQuery('#iawp-parent').removeClass('loading');
        });
    }

    exportCSV() {
        const data = {
            ...iawpActions.filter,
            'filters': this.filters,
            'exact_start': this.exactStart,
            'exact_end': this.exactEnd,
            'is_new_date_range': false,
            'relative_range_id': this.relativeRangeId,
            'table_type': jQuery('#data-table').data('table-name'),
            'columns': this.columns,
            'primary_chart_metric_id': this.primaryChartMetricId,
            'secondary_chart_metric_id': this.secondaryChartMetricId,
            'sort_column': this.sortColumn,
            'quick_stats': this.quickStats,
            'sort_direction': this.sortDirection,
            'group': this.group,
            'is_new_group': false,
            'chart_interval': this.chartInterval,
            'page': this.page,
            'as_csv': true
        };

        this.exportCSVTarget.classList.add('sending')
        this.exportCSVTarget.setAttribute('disabled', 'disabled')

        if (Report_Controller.csvRequest) {
            Report_Controller.csvRequest.abort();
        }

        Report_Controller.csvRequest = jQuery.post(ajaxurl, data, (response) => {
            downloadCSV(this.getFileName('csv'), response)
            this.exportCSVTarget.classList.add('sent')
            this.exportCSVTarget.classList.remove('sending')
            this.exportCSVTarget.removeAttribute('disabled')

            setTimeout(() => {
                this.exportCSVTarget.classList.remove('sent')
            }, 1000)
        })
    }

    exportPDF() {
        this.exportPDFTarget.classList.add('sending')
        this.exportPDFTarget.setAttribute('disabled', 'disabled')

        setTimeout(() => {
            const element = document.getElementById('wpwrap').cloneNode(true)
            const options = {
                filename: this.getFileName('pdf'),
                jsPDF: {
                    unit: 'in',
                    format: 'letter',
                    orientation: 'landscape'
                },
            }
            let base64Image = null

            if (window.iawp_chart) {
                const chartImageWidth = 1080
                const chartImageHeight = chartImageWidth * .25
                window.iawp_chart.resize(chartImageWidth, chartImageHeight)
                base64Image = window.iawp_chart.toBase64Image('image/png', 1);
                window.iawp_chart.resize()
            }

            if(window.iawp_geo_chart) {
                base64Image = window.iawp_geo_chart.getImageURI()
            }

            if(base64Image) {
                const imageElement = document.createElement('img')
                imageElement.src = base64Image

                element.querySelector('#independent-analytics-chart').replaceWith(imageElement)
            }

            // Prevent stimulus controllers from firing
            element.querySelectorAll('[data-controller]').forEach((element) => {
                element.removeAttribute('data-controller')
            })

            // Preserve selected values in clone by manually adding "selected" to options
            element.querySelectorAll('select').forEach((element) => {
                if(!element.id) {
                    return
                }

                const originalValue = document.getElementById(element.id).value

                element.options.forEach((option) => {
                    option.toggleAttribute('selected', option.value === originalValue)
                })
            })

            html2pdf().set(options).from(element).toContainer().save().then(() => {
                this.exportPDFTarget.classList.add('sent')
                this.exportPDFTarget.classList.remove('sending')
                this.exportPDFTarget.removeAttribute('disabled')

                setTimeout(() => {
                    this.exportPDFTarget.classList.remove('sent')
                }, 1000)
            })
        }, 250) // Allow animations to finish before exporting blocks things up
    }

    getFileName(fileExtension) {
        const reportTitleElement = document.querySelector('#report-title-bar .report-title')

        if (!reportTitleElement) {
            return 'report.' + fileExtension
        }

        return reportTitleElement.innerText.replace(/[^a-zA-Z0-9]+/g, '-').toLowerCase() + '.' + fileExtension
    }
}

export default Report_Controller;