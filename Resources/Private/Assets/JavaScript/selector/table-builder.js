import Handsontable from 'handsontable';
import Helper from "../helper";

export default class TableBuilder {
    constructor(element) {
        this.tableWrapper = element;
        this.handsOnTableInstance = null;
    }

    /**
     * @deprecated Will be removed with Handsontable migration
     */
    buildHandsOnTable(spreadsheetData, currentSelectedRange) {
        const _this = this;
        if (this.tableWrapper.length === 0) {
            // table cell selection is disabled => sheets only
            return;
        }

        if (typeof this.handsOnTableInstance === 'object' && this.handsOnTableInstance !== null) {
            this.tableWrapper.textContent = '';
        }

        const data = spreadsheetData.getSheetData();
        this.handsOnTableInstance = new Handsontable(this.tableWrapper, {
            data: data,
            cell: spreadsheetData.getSheetCellMetaData(),
            mergeCells: spreadsheetData.getSheetMergeData(),
            rowHeaders: true,
            colHeaders: true,
            readOnly: true,
            stretchH: 'all',
            height: function () {
                var height = (data.length + 1) * 25;
                if (height > 300) {
                    return 300;
                } else if (height < 150) {
                    return 150;
                }
                return height;
            },
            cells: (row, col) => {
                let cellProperties = {};
                if (typeof currentSelectedRange !== 'undefined') {
                    const startCoords = this.getCellCoords(currentSelectedRange[0]),
                        endCoords = this.getCellCoords(currentSelectedRange[1]);

                    cellProperties.renderer = 'text';
                    if (col >= startCoords[0] && row >= startCoords[1] && col <= endCoords[0] && row <= endCoords[1]) {
                        // a range or single cell will be highlighted
                        cellProperties.renderer = TableBuilder.highlightCurrentSelectedCell;
                    } else if (startCoords[1] === null && endCoords[1] === null && col >= startCoords[0] && col <= endCoords[0]) {
                        // only one or more columns are selected and will be highlighted (no specific rows)
                        cellProperties.renderer = TableBuilder.highlightCurrentSelectedCell;
                    } else if (startCoords[0] === null && endCoords[0] === null && row >= startCoords[1] && row <= endCoords[1]) {
                        // only one or more rows are selected and will be highlighted (no specific columns)
                        cellProperties.renderer = TableBuilder.highlightCurrentSelectedCell;
                    }
                }
                return cellProperties;
            },
            afterSelectionEnd: function (rowStart, columnStart, rowEnd, columnEnd) {
                let rows = {start: rowStart, end: rowEnd},
                    cols = {start: columnStart, end: columnEnd};

                // fix ordering of rows and columns
                if (rows.start > rows.end) {
                    rows = Helper.switchObjectPropertiesValue(rows, 'start', 'end');
                }
                if (cols.start > cols.end) {
                    cols = Helper.switchObjectPropertiesValue(cols, 'start', 'end');
                }

                const maxRows = this.countRows(), maxCols = this.countCols();
                let startPoint = '', endPoint = '';

                // one or multiple columns are selected
                if (rows.start === 0 && (rows.end + 1) === maxRows) {
                    startPoint = Helper.getColHeader(cols.start);
                    endPoint = Helper.getColHeader(cols.end);
                }
                // one or multiple rows are selected
                else if (cols.start === 0 && (cols.end + 1) === maxCols) {
                    startPoint = (rows.start + 1);
                    endPoint = (rows.end + 1);
                }
                // on default columns with rows (range) is selected
                else {
                    startPoint = Helper.getColHeader(cols.start) + (rows.start + 1);
                    endPoint = Helper.getColHeader(cols.end) + (rows.end + 1);
                }

                // trigger updateIndex event
                _this.tableWrapper.dispatchEvent(new CustomEvent("changeSelection", {
                    detail: {
                        start: startPoint,
                        end: endPoint
                    }
                }));
            }
        });
    }

    /**
     * @deprecated Will be removed with Handsontable migration
     */
    static highlightCurrentSelectedCell(instance, td) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);
        td.style["background"] = '#fce7a1';
    }

    /**
     * @deprecated Will be removed with Handsontable migration
     */
    getCellCoords(namedCoordinate) {
        if (typeof namedCoordinate === 'undefined' || namedCoordinate === null) {
            return [];
        }

        if (!isNaN(parseFloat(namedCoordinate)) && isFinite(namedCoordinate)) {
            return [null, parseInt(namedCoordinate) - 1];
        }

        let result = namedCoordinate.split(/([0-9])/),
            column = this.getColFromName(result.shift()),
            row = parseInt(result.join('')) - 1;

        column = isNaN(column) ? null : column;
        row = isNaN(row) ? null : row;
        return [column, row];
    }

    /**
     * @deprecated Will be removed with Handsontable migration
     */
    getColFromName(name) {
        if (typeof this.handsOnTableInstance !== 'object' || this.handsOnTableInstance === null) {
            return -1; //return -1 if nothing can be found
        }

        const colCount = this.handsOnTableInstance.countCols();
        for (let i = 0; i < colCount; i++) {
            if (name.toLowerCase() === this.handsOnTableInstance.getColHeader(i).toLowerCase()) {
                return i;
            }
        }
        return -1; //return -1 if nothing can be found
    }
}
