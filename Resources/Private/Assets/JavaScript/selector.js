import Helper, {throttle} from "./helper";

export default class Selector {
    constructor(tableWrapper) {
        this.cursor = {};
        this.properties = {};

        if (tableWrapper !== null) {
            tableWrapper.addEventListener('mousedown', (mouseEvent) => {
                this.isSelecting = true;
                this.cursor.startX = mouseEvent.x;
                this.cursor.startY = mouseEvent.y;

                this.cursor.selectMode = null;
                if (this.isTableColumnHeaderPosition(mouseEvent.x, mouseEvent.y)) {
                    this.cursor.selectMode = 'column';
                } else if (this.isTableRowHeaderPosition(mouseEvent.x, mouseEvent.y)) {
                    this.cursor.selectMode = 'row';
                }
            });
            tableWrapper.addEventListener('mouseleave', () => {
                this.isSelecting = false; // just end selecting and use last selection
            });

            const moveEvent = (mouseEvent) => {
                if (this.isSelecting !== true) {
                    // stop if we are not selecting
                    return false;
                }

                const reachedTableColumnHeaders = this.isTableColumnHeaderPosition(mouseEvent.x, mouseEvent.y);
                const reachedTableRowHeaders = this.isTableRowHeaderPosition(mouseEvent.x, mouseEvent.y);
                if (reachedTableColumnHeaders && reachedTableRowHeaders) {
                    this.isSelecting = false; // stop selection immediately if left/top corner is hovered
                    return false;
                }

                if (this.cursor.selectMode === 'column' && reachedTableRowHeaders === true) {
                    // stop if column select reaches table row headers
                    return false;
                } else if (this.cursor.selectMode === 'row' && reachedTableColumnHeaders === true) {
                    // stop if row select reaches table column headers
                    return false;
                } else if (this.cursor.selectMode === null && (reachedTableColumnHeaders === true || reachedTableRowHeaders === true)) {
                    // stop if default select mode reaches row or column headers
                    return false;
                }

                // stop selecting on mouse up
                if (mouseEvent.type === 'mouseup') {
                    this.isSelecting = false;
                }

                // update cursor position for calculation
                this.cursor.endX = mouseEvent.x;
                this.cursor.endY = mouseEvent.y;

                // always re-render overlay
                this.calculateSelection(tableWrapper);
                this.renderOverlay(tableWrapper);

                // dispatch change selection event with start/end point value
                tableWrapper.dispatchEvent(new CustomEvent("changeSelection", {
                    detail: {
                        start: this.selection.start,
                        end: this.selection.end,
                    }
                }));
            };
            tableWrapper.addEventListener('mousemove', throttle(60, moveEvent));
            tableWrapper.addEventListener('mouseup', moveEvent);
        }
    }

    get selection() {
        return this.properties.selection;
    }

    set selection(data) {
        // find starting and ending elements by offset values
        const startElement = document.elementFromPoint(
            Math.min(...data.offsetsX),
            Math.min(...data.offsetsY),
        );
        const endElement = document.elementFromPoint(
            Math.max(...data.offsetsX),
            Math.max(...data.offsetsY),
        );

        this.properties.selection = {
            start: Helper.getCellRepresentation(startElement, this.cursor.selectMode, false),
            end: Helper.getCellRepresentation(endElement, this.cursor.selectMode, startElement !== endElement),
            elements: {
                start: startElement,
                end: endElement,
            },
            offsets: {
                top: startElement.offsetTop,
                left: startElement.offsetLeft,
                bottom: this.cursor.selectMode === 'column'
                    ? Number.MAX_SAFE_INTEGER
                    : (endElement.offsetTop + endElement.clientHeight),
                right: this.cursor.selectMode === 'row'
                    ? Number.MAX_SAFE_INTEGER
                    : (endElement.offsetLeft + endElement.clientWidth),
            }
        };
    }

    set mergeCell(element) {
        const rect = element.getBoundingClientRect();
        const startElement = this.selection.elements.start;
        const startRect = startElement.getBoundingClientRect();
        const endElement = this.selection.elements.end;
        const endRect = endElement.getBoundingClientRect();

        this.selection = {
            offsetsX: [
                startRect.left,
                endRect.left + endElement.clientWidth,
                rect.left,
                rect.left + element.clientWidth
            ],
            offsetsY: [
                startRect.top,
                endRect.top + endElement.clientHeight,
                rect.top,
                rect.top + element.clientHeight
            ]
        };
    }

    isTableColumnHeaderPosition(x, y) {
        const target = document.elementFromPoint(x, y);
        return target.parentNode.parentNode.nodeName.toLowerCase() === 'thead';
    }

    isTableRowHeaderPosition(x, y) {
        const target = document.elementFromPoint(x, y);
        return target.nodeName.toLowerCase() === 'td' && target.parentNode.firstChild === target;
    }

    calculateSelection(tableWrapper) {
        // set selection by cursor values
        this.selection = {
            offsetsX: [this.cursor.startX, this.cursor.endX],
            offsetsY: [this.cursor.startY, this.cursor.endY]
        };

        // process merged cells to get final selection
        const mergedCells = tableWrapper.querySelectorAll('td[colspan], td[rowspan]');
        if (mergedCells.length > 0) {
            mergedCells.forEach((element) => {
                if (this.isElementInSelection(element) === true) {
                    this.mergeCell = element;
                }
            });
        }
    }

    isElementInSelection(element) {
        const current = {
            top: element.offsetTop,
            left: element.offsetLeft,
            right: element.offsetLeft + element.clientWidth,
            bottom: element.offsetTop + element.clientHeight
        };

        // if one or more expressions in the parentheses are true, there's no overlapping
        // if all are false, there must be an overlapping
        return !(
            this.selection.offsets.top >= current.bottom ||
            this.selection.offsets.left >= current.right ||
            this.selection.offsets.right <= current.left ||
            this.selection.offsets.bottom <= current.top
        );
    }

    renderOverlay(tableWrapper) {
        let overlay = tableWrapper.querySelector('#table-overlay');
        if (overlay === null) {
            // create new overlay
            overlay = document.createElement('div');
            overlay.id = 'table-overlay';
            overlay.style.position = 'absolute';
            overlay.style.background = 'rgba(255, 0, 0, 0.5)';
            overlay.style.pointerEvents = 'none';
            tableWrapper.appendChild(overlay);
        }

        // update overlay
        overlay.style.top = this.selection.offsets.top + 'px';
        overlay.style.left = this.selection.offsets.left + 'px';
        if (this.cursor.selectMode === 'row') {
            overlay.style.width = '100%';
        } else {
            overlay.style.width = (this.selection.offsets.right - this.selection.offsets.left) + 'px';
        }
        if (this.cursor.selectMode === 'column') {
            overlay.style.height = '100%';
        } else {
            overlay.style.height = (this.selection.offsets.bottom - this.selection.offsets.top) + 'px';
        }
    }
}
