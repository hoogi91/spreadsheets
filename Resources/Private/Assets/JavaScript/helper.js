/**
 * Unselect all user selected content of window/document
 */
export function unselectEverything() {
    if (window.getSelection) {
        if (window.getSelection().empty) {
            window.getSelection().empty(); // Chrome
        } else if (window.getSelection().removeAllRanges) {
            window.getSelection().removeAllRanges(); // Firefox
        }
    } else if (document.selection) {
        document.selection.empty(); // fallback
    }
}

/**
 * Get excel like col header string from index
 *
 * @param {int} index
 * @returns {string}
 */
export function colHeaderByIndex(index) {
    // charcode of "a" == 97
    // charcode of "z" == 122
    const base24Str = index.toString(24); // string base (("z" == 122) - ("a" == 97) - 1)
    let excelStr = "";
    for (let i = 0; i < base24Str.length; i++) {
        let base24Char = base24Str[i];
        let alphabetIndex = ((base24Char * 1).toString() === base24Char) ? base24Char : (base24Char.charCodeAt(0) - 97 + 10);
        // bizarre thing, A==1 in first digit, A==0 in other digits
        if (i === 0) {
            alphabetIndex -= 1;
        }
        excelStr += String.fromCharCode(97 + alphabetIndex);
    }
    return excelStr.toUpperCase();
}

/**
 * Get column index from header string
 * @param {string} headerString
 * @returns {number}
 */
export function colHeaderToIndex(headerString) {
    const base = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    let result = 0;
    for (let i = 0, j = headerString.length - 1; i < headerString.length; i += 1, j -= 1) {
        result += Math.pow(base.length, j) * (base.indexOf(headerString[i]) + 1);
    }
    return result;
}

/**
 * Return cell representation based on selection mode
 *
 * @param {Number} colIndex
 * @param {Number} rowIndex
 * @param {string|null} selectMode
 * @returns {string|int}
 */
export function cellRepresentation(colIndex, rowIndex, selectMode = null) {
    if (selectMode === 'row') {
        return rowIndex;
    } else if (selectMode === 'column') {
        return colHeaderByIndex(colIndex);
    }

    return colHeaderByIndex(colIndex) + rowIndex;
}

/**
 * Calculate cell indexes for element (optionally add spans)
 * @param {Element} cellElement
 * @param {boolean} calculateSpans
 * @returns {{colIndex: number, rowIndex: number}}
 */
export function calculateCellIndexes(cellElement, calculateSpans = true) {
    let colIndex = parseInt(cellElement.getAttribute('data-col'));
    if (calculateSpans === true && cellElement.hasAttribute('colspan') === true) {
        colIndex += parseInt(cellElement.getAttribute('colspan')) - 1;
    }

    let rowIndex = parseInt(cellElement.getAttribute('data-row'));
    if (calculateSpans === true && cellElement.hasAttribute('rowspan') === true) {
        rowIndex += parseInt(cellElement.getAttribute('rowspan')) - 1;
    }

    return {colIndex: colIndex, rowIndex: rowIndex};
}

/**
 * Throttle calls by millisecond limit
 * @param {Number}limit
 * @param func
 * @returns {function(...[*]=)}
 */
export function throttle(limit, func) {
    let lastFunc;
    let lastRan;
    return function () {
        const context = this;
        const args = arguments;
        if (!lastRan) {
            func.apply(context, args);
            lastRan = Date.now();
        } else {
            clearTimeout(lastFunc);
            lastFunc = setTimeout(function () {
                if ((Date.now() - lastRan) >= limit) {
                    func.apply(context, args);
                    lastRan = Date.now();
                }
            }, limit - (Date.now() - lastRan));
        }
    };
}
