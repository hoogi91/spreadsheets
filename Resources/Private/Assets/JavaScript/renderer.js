import SheetBuilder from "./selector/sheet-builder";
import TableBuilder from "./selector/table-builder";
import DSN from "./dsn";

export default class Renderer {
    constructor(sheetWrapper, tableWrapper) {
        this.sheetBuilder = new SheetBuilder(sheetWrapper);
        if (tableWrapper !== null) {
            this.tableBuilder = new TableBuilder(tableWrapper);
        }
    }

    update(spreadsheetData, currentDSN) {
        if (!(currentDSN instanceof DSN)) {
            throw new Error('Renderer class "update" method expects parameter to be type of a DSN class');
        }

        // TODO: we need to update tabs and table only if required
        this.sheetBuilder.buildTabs(spreadsheetData, currentDSN.index);
        if (this.tableBuilder instanceof TableBuilder) {
            this.tableBuilder.buildTable(spreadsheetData, currentDSN.range.split(':'));
        }
    }
}
