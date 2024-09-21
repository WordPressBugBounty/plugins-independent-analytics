import {Controller} from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ['quickStat']

    connect() {
        document.addEventListener('iawp:changeQuickStats', this.updateTableUI)
    }

    disconnect() {
        document.removeEventListener('iawp:changeQuickStats', this.updateTableUI)
    }

    updateTableUI = (e) => {
        const quickStatIds = e.detail.optionIds
        const quickStatCount = quickStatIds.length

        this.element.classList.forEach((className) => {
            if (className.startsWith("total-of-")) {
                this.element.classList.remove(className)
            }
        })

        this.element.classList.add('total-of-' + quickStatCount.toString())

        this.quickStatTargets.forEach((stat) => {
            const isPresent = quickStatIds.includes(stat.dataset.id)
            stat.classList.toggle('visible', isPresent)
        })
    }
}
