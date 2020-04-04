import {calculateCellIndexes, cellRepresentation, throttle, unselectEverything} from "./helper";

export default class Selector {
    constructor(tableWrapper) {
        this.cursor = {
            isSelecting: false,
            selectMode: null,
        };
        this.properties = {};
        this.tableWrapper = tableWrapper;

        if (this.tableWrapper !== null) {
            this.tableWrapper.addEventListener('mousedown', (mouseEvent) => {
                const target = document.elementFromPoint(mouseEvent.x, mouseEvent.y);

                this.cursor.isSelecting = true;
                this.cursor.start = target;

                this.cursor.selectMode = null;
                if (this.reachedColumnHeader(target)) {
                    this.cursor.selectMode = 'column';
                } else if (this.reachedRowHeader(target)) {
                    this.cursor.selectMode = 'row';
                }
            });

            const moveEvent = (mouseEvent) => {
                if (this.cursor.isSelecting !== true) {
                    // stop if we are not selecting
                    return false;
                } else if (mouseEvent.type === 'mouseup') {
                    // stop selecting on mouse up
                    this.cursor.isSelecting = false;
                    unselectEverything();
                }

                // stop processing if we are not inside table or we are moving over col/row header
                const target = document.elementFromPoint(mouseEvent.x, mouseEvent.y);
                if (this.isInsideTable(target) === false) {
                    // we are outside the table
                    return false;
                } else if (this.reachedColumnHeader(target) && this.reachedRowHeader(target)) {
                    // we reached the top/left corner
                    return false;
                } else if (this.cursor.selectMode === 'column' && this.reachedRowHeader(target)) {
                    // stop if column select reaches table row headers
                    return false;
                } else if (this.cursor.selectMode === 'row' && this.reachedColumnHeader(target)) {
                    // stop if row select reaches table column headers
                    return false;
                } else if (this.cursor.selectMode === null && (this.reachedColumnHeader(target) || this.reachedRowHeader(target))) {
                    // stop if default select mode reaches row or column headers
                    return false;
                }

                // set selection by cursor start end mouse event end position
                this.cursor.end = target;
                this.selection = [this.cursor.start, this.cursor.end];

                // calculate merge cell information and highlight selection
                this.calculateMergeCells();
                this.highlightSelection();

                // dispatch change selection event with start/end point value
                this.tableWrapper.dispatchEvent(new CustomEvent("changeSelection", {
                    detail: {
                        start: this.selection.start,
                        end: this.selection.end,
                    }
                }));
            };
            this.tableWrapper.addEventListener('mousemove', throttle(60, moveEvent));
            this.tableWrapper.addEventListener('mouseup', moveEvent);
        }
    }

    get selection() {
        return this.properties.selection;
    }

    set selection(elements) {
        if (elements.length <= 1) {
            return;
        }

        // iterate elements to find col- and row-index
        let startElement = null, endElement = null;
        let colIndex = {min: null, max: null};
        let rowIndex = {min: null, max: null};
        elements.forEach((element) => {
            const i = calculateCellIndexes(element, false);
            if (colIndex.min === null || colIndex.min > i.colIndex) {
                colIndex.min = i.colIndex;
                startElement = element;
            }
            if (rowIndex.min === null || rowIndex.min > i.rowIndex) {
                rowIndex.min = i.rowIndex;
                startElement = element;
            }

            const span = calculateCellIndexes(element, true);
            if (colIndex.max === null || colIndex.max < span.colIndex) {
                colIndex.max = span.colIndex;
                endElement = element;
            }
            if (rowIndex.max === null || rowIndex.max < span.rowIndex) {
                rowIndex.max = span.rowIndex;
                endElement = element;
            }
        });

        if (startElement === endElement) {
            colIndex.max = colIndex.min;
            rowIndex.max = rowIndex.min;
        }

        this.properties.selection = {
            start: cellRepresentation(colIndex.min, rowIndex.min, this.cursor.selectMode),
            end: cellRepresentation(colIndex.max, rowIndex.max, this.cursor.selectMode),
            elements: {
                start: startElement,
                end: endElement,
            },
            indexes: {
                col: colIndex,
                row: rowIndex,
            },
        };
    }

    isInsideTable(target) {
        return target !== null ? (target.closest('table') !== null) : false;
    }

    reachedColumnHeader(target) {
        return target !== null ? (target.closest('thead') !== null) : false;
    }

    reachedRowHeader(target) {
        // check parent row first child is target
        return target !== null ? (target.closest('tr').querySelector('td') === target) : false;
    }

    calculateMergeCells(alreadyProcessedIndexes = []) {
        // calculate current active col/row indexes
        let colIndexes = this.selection.indexes.col;
        let rowIndexes = this.selection.indexes.row;
        if (this.cursor.selectMode === 'row') {
            colIndexes = {min: 0, max: this.tableWrapper.querySelector('table').rows[0].cells.length};
        } else if (this.cursor.selectMode === 'column') {
            rowIndexes = {min: 0, max: this.tableWrapper.querySelector('table').rows.length};
        }

        //   1. iterate current selection
        //   1a. find merge cell which overlaps selection at bottom or right
        //      => set as end element
        //      => update selection and restart method

        // iterate current selection to find merge cells in bottom/right position of selection
        mergeCellLoop: for (let r = rowIndexes.min; r <= rowIndexes.max; r++) {
            for (let c = colIndexes.min; c <= colIndexes.max; c++) {
                if (alreadyProcessedIndexes.indexOf(c + '-' + r) !== -1) {
                    continue;
                }

                const mergeCell = this.tableWrapper.querySelector(
                    'td[data-col="' + c + '"][data-row="' + r + '"][colspan],' +
                    'td[data-col="' + c + '"][data-row="' + r + '"][rowspan]'
                );
                if (mergeCell === null) {
                    continue;
                }

                // get cell index and save as already processed
                const mergeCellIndex = calculateCellIndexes(mergeCell, false);
                alreadyProcessedIndexes.push(mergeCellIndex.colIndex + '-' + mergeCellIndex.rowIndex);

                // check if cell needs to be merged
                const mergeCellSpanIndex = calculateCellIndexes(mergeCell, true);
                if (mergeCellSpanIndex.colIndex > colIndexes.max || mergeCellSpanIndex.rowIndex > rowIndexes.max) {
                    // 1. extend existing selection by cell
                    this.selection = [this.selection.elements.start, this.selection.elements.end, mergeCell];
                    // 2. add merge cell index to already processed indexes and re-calculate
                    this.calculateMergeCells(alreadyProcessedIndexes);
                    // 3. break current loop cause new one is started
                    break mergeCellLoop;
                }
            }
        }

        //   2. iterate merged cells
        //   2a. find merge cell which overlaps selection at top or left
        //      => set as start element
        //      => update selection and restart method

        // iterate merged cells to find merged cells in top/left position of selection
        const mergedCells = this.tableWrapper.querySelectorAll('td[colspan], td[rowspan]');
        for (let i = 0; i < mergedCells.length; ++i) {
            const mergeCell = mergedCells[i];
            const mergeCellIndex = calculateCellIndexes(mergeCell, false);
            if (alreadyProcessedIndexes.indexOf(mergeCellIndex.colIndex + '-' + mergeCellIndex.rowIndex) !== -1) {
                continue;
            }

            // check if cell needs to be merged
            const mergeCellSpanIndex = calculateCellIndexes(mergeCell, true);
            if (
                (mergeCellIndex.colIndex < colIndexes.min || mergeCellIndex.rowIndex < rowIndexes.min) &&
                (mergeCellSpanIndex.colIndex >= colIndexes.min && mergeCellSpanIndex.rowIndex >= rowIndexes.min)
            ) {
                // 1. extend existing selection by cell
                this.selection = [this.selection.elements.start, this.selection.elements.end, mergeCell];
                // 2. add merge cell index to already processed indexes and re-calculate
                this.calculateMergeCells(alreadyProcessedIndexes);
                // 3. break current loop cause new one is started
                break;
            }
        }
    }

    highlightSelection() {
        // get col/row indexes from selection
        const colIndexes = this.selection.indexes.col;
        const rowIndexes = this.selection.indexes.row;

        // check if selected rows/columns have to be highlighted
        const nodeList = [];
        if (this.cursor.selectMode === 'row') {
            // iterate only rows
            for (let r = rowIndexes.min; r <= rowIndexes.max; r++) {
                nodeList.push(...this.tableWrapper.querySelectorAll('td[data-row="' + r + '"]'));
            }
        } else if (this.cursor.selectMode === 'column') {
            // iterate only columns
            for (let c = colIndexes.min; c <= colIndexes.max; c++) {
                nodeList.push(...this.tableWrapper.querySelectorAll('td[data-col="' + c + '"]'));
            }
        } else {
            // iterate all cells of selection
            for (let c = colIndexes.min; c <= colIndexes.max; c++) {
                for (let r = rowIndexes.min; r <= rowIndexes.max; r++) {
                    nodeList.push(this.tableWrapper.querySelector('td[data-col="' + c + '"][data-row="' + r + '"]'));
                }
            }
        }

        // deselect current highlighted cells
        Array.from(document.querySelectorAll('td.highlight'))
            .filter(x => x !== null)
            .forEach(x => x.classList.remove('highlight'));

        // apply highlight class to selected cells
        nodeList
            .filter(x => x !== null)
            .forEach(x => x.classList.add('highlight'));
    }
}
