export default class Helper {
    /**
     * Get excel like col header string from index
     *
     * @param {int} index
     * @returns {string}
     */
    static getColHeader(index) {
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

    static getCellRepresentation(cellElement) {
        const trow = cellElement.parentNode;
        const tbody = cellElement.parentNode.parentNode;
        if (trow.nodeName.toLowerCase() !== 'tr' || tbody.nodeName.toLowerCase() !== 'tbody') {
            return '';
        }

        // find indexes by cell element and table row
        const colIndex = Array.from(trow.childNodes.values()).indexOf(cellElement);
        const rowIndex = Array.from(tbody.childNodes.values()).indexOf(trow);

        return this.getColHeader(colIndex) + (rowIndex + 1);
    }

    /**
     * Switch provided property values of an object
     *
     * @param {object} object
     * @param {string} property1
     * @param {string} property2
     * @returns {object}
     */
    static switchObjectPropertiesValue(object, property1, property2) {
        const temp = object[property1];
        object[property1] = object[property2];
        object[property2] = temp;
        return object;
    }
}

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
