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
            });

            const moveEvent = (mouseEvent) => {
                if (this.isSelecting === true) {
                    this.cursor.endX = mouseEvent.x;
                    this.cursor.endY = mouseEvent.y;
                    if (mouseEvent.type === 'mouseup') {
                        this.isSelecting = false;
                    }

                    this.calculateAndTriggerSelectionChange(tableWrapper);
                }
            };
            tableWrapper.addEventListener('mousemove', throttle(100, moveEvent));
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
            start: Helper.getCellRepresentation(startElement),
            end: Helper.getCellRepresentation(endElement),
            startElement: startElement,
            endElement: endElement,
            top: startElement.offsetTop,
            left: startElement.offsetLeft,
            bottom: endElement.offsetTop + endElement.clientHeight,
            right: endElement.offsetLeft + endElement.clientWidth,
        };
    }

    set mergeCell(element) {
        const rect = element.getBoundingClientRect();
        const startElement = this.selection.startElement;
        const startRect = startElement.getBoundingClientRect();
        const endElement = this.selection.endElement;
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

    calculateAndTriggerSelectionChange(tableWrapper) {
        // calculate selection before rendering overlay
        this.calculateSelection(tableWrapper);
        this.renderOverlay(tableWrapper);

        // dispatch change selection event with start/end point value
        tableWrapper.dispatchEvent(new CustomEvent("changeSelection", {
            detail: {
                start: this.selection.start,
                end: this.selection.end,
            }
        }));
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
            this.selection.top >= current.bottom ||
            this.selection.left >= current.right ||
            this.selection.right <= current.left ||
            this.selection.bottom <= current.top
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
        overlay.style.top = this.selection.top + 'px';
        overlay.style.left = this.selection.left + 'px';
        overlay.style.width = (this.selection.right - this.selection.left) + 'px';
        overlay.style.height = (this.selection.bottom - this.selection.top) + 'px';
    }
}
