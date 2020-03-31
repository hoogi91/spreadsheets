import DSN from "./dsn";
import Helper from "./helper";

export default class Renderer {
    constructor(sheetWrapper, tableWrapper) {
        // bind click on another sheet
        this.sheetWrapper = sheetWrapper;
        this.sheetWrapper.addEventListener('click', (event) => {
            if (event.target.tagName === 'A') {
                for (let node of event.target.parentNode.childNodes) {
                    node.classList.remove('active');
                }
                event.target.classList.add('active');

                // trigger index change event
                this.sheetWrapper.dispatchEvent(new CustomEvent("changeIndex", {
                    detail: {
                        index: event.target.getAttribute('data-value')
                    }
                }));
            }
        });

        if (tableWrapper !== null) {
            this.tableWrapper = tableWrapper;
        }
    }

    update(spreadsheetData, currentDSN) {
        if (!(currentDSN instanceof DSN)) {
            throw new Error('Renderer class "update" method expects parameter to be type of a DSN class');
        }

        // TODO: we need to update tabs and table only if required
        this.buildTabs(spreadsheetData, currentDSN.index);
        this.buildTable(spreadsheetData, currentDSN.range.split(':'));
    }

    buildTabs(spreadsheetData, selectedSheetIndex) {
        this.sheetWrapper.textContent = "";
        if (spreadsheetData.getAllSheets().length <= 0) {
            this.sheetWrapper.style.display = 'none';
        } else {
            for (let index = 0; index < spreadsheetData.getAllSheets().length; index++) {
                // create list item and append to sheet wrapper
                const link = document.createElement('a');
                link.setAttribute('href', '#');
                link.setAttribute('data-value', index);
                link.innerText = spreadsheetData.getSheetName(index);

                const listItem = document.createElement('li');
                if (index === parseInt(selectedSheetIndex)) {
                    listItem.classList.add('active');
                }
                listItem.appendChild(link);

                this.sheetWrapper.appendChild(listItem);
            }
            this.sheetWrapper.style.display = 'block';
        }
    }

    buildTable(spreadsheetData) {
        if (typeof this.tableWrapper === 'undefined' || this.tableWrapper === null) {
            return;
        }

        // convert sheet data from object to array values
        const sheetData = Object.values(spreadsheetData.getSheetData()).map(x => Object.values(x));
        const table = document.createElement('table');
        this.buildTableHeader(table, Math.max(...sheetData.map(x => x.length)));
        this.buildTableBody(table, sheetData);

        // empty table wrapper and append new table element
        this.tableWrapper.textContent = '';
        this.tableWrapper.appendChild(table);
    }

    buildTableHeader(table, columnCount) {
        // create header before adding row
        const header = table.createTHead();
        const headerRow = header.insertRow();
        for (let colIndex = 0; colIndex <= columnCount; colIndex++) {
            if (colIndex > 0) {
                // insert new cell with column header naming
                headerRow.insertCell().innerText = Helper.getColHeader(colIndex);
            } else {
                // left/top corner is the row number column and has no text
                headerRow.insertCell();
            }
        }
    }

    buildTableBody(table, data) {
        // build tbody before adding rows
        const body = table.createTBody();
        data.forEach((row, rowIndex) => {
            // build new table row
            const tableRow = body.insertRow();
            tableRow.insertCell().innerText = rowIndex + 1;

            // column object => {val: "value", css: "style classes", row: "rowspan", col: "rowspan"}
            row.forEach((column) => {
                // build new table cell and set inner text
                const cell = tableRow.insertCell();
                cell.innerText = column.val;

                // set alignment styles if set
                if (typeof column.css !== 'undefined') {
                    cell.setAttribute('class', column.css.split('-').filter(x => x.length > 0).map(x => 'align-' + x).join(' '));
                }

                // check if cell index needs a row- or col-span
                if (typeof column.row !== 'undefined') {
                    cell.setAttribute('rowspan', column.row);
                }
                if (typeof column.col !== 'undefined') {
                    cell.setAttribute('colspan', column.col);
                }
            });
        });
    }
}
