/**
 * Quality Check Gauge JavaScript
 * Handles the gauge functionality for quality check results
 */

document.addEventListener("DOMContentLoaded", () => {
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);

    if (urlParams.get('sustainable')) {
        showSustainableResult();
    }
});

/**
 * Prepare the result for printing
 */
function prepareToPrintResult() {
    const gauge = document.getElementById('gauge');

    if (!gauge) {
        console.warn('Gauge element not found');
        return;
    }

    const canvas = gauge.getElementsByTagName("canvas");
    
    if (!canvas || canvas.length === 0) {
        console.warn('Canvas element not found in gauge');
        return;
    }

    console.log(canvas[0]);
    var link = document.getElementById('link');
    
    if (link) {
        link.setAttribute('download', 'MintyPaper.png');
    }

    const canvasData = canvas[0].toDataURL("image/png");
    console.log(canvasData);
    
    var ajax = new XMLHttpRequest();
    ajax.open("POST", "/quality_check/", false);
    ajax.setRequestHeader('Content-Type', 'application/upload');
    ajax.send(canvasData);
}

/**
 * Show sustainable result by flipping the card
 */
function showSustainableResult() {
    doIt('flip-card', 'rotate');
    doIt('notSustainableCard', 'hidden not-print');
    doIt('notSustainableScore', 'hidden');
}

/**
 * Toggle CSS classes on elements
 * @param {string} className - The class name to select elements
 * @param {string} flipClass - The class to toggle
 */
function doIt(className, flipClass) {
    let elements = document.getElementsByClassName(className);
    Array.prototype.forEach.call(elements, function (el) {
        if (el.className.includes(flipClass)) {
            el.className = el.className.replace(flipClass, '');
        } else {
            el.className += ' ' + flipClass;
        }
    });
}

// Make functions globally available
window.prepareToPrintResult = prepareToPrintResult;
window.showSustainableResult = showSustainableResult;
window.doIt = doIt; 