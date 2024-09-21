function downloadCSV(fileName, data) {
    const blob = new Blob([data], {type: 'text/csv'})
    const element = window.document.createElement('a')
    element.href = window.URL.createObjectURL(blob)
    element.download = fileName
    document.body.appendChild(element)
    element.click()
    document.body.removeChild(element)
}

function downloadJSON(fileName, data) {
    const blob = new Blob([data], {type: 'application/json'})
    const element = window.document.createElement('a')
    element.href = window.URL.createObjectURL(blob)
    element.download = fileName
    document.body.appendChild(element)
    element.click()
    document.body.removeChild(element)
}

module.exports = {downloadCSV, downloadJSON}