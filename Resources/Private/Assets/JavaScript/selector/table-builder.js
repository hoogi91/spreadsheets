import Helper from "../helper";

export default class TableBuilder {
    constructor(element) {
        this.tableWrapper = element;
    }

    buildTable(spreadsheetData) {
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
                    cell.setAttribute('class', column.css.split('-').map(x => 'align-' + x).join(' '));
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
