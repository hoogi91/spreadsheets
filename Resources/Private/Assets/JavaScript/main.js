import $ from 'jquery';
import DSN from './dsn';
import Selector from './selector';
import Spreadsheet from './spreadsheet';

class SpreadsheetDataInput {
    constructor(element) {
        this.$inputWrapper = $(element);

        const sheetWrapper = this.$inputWrapper.find('.spreadsheet-sheets').get(0),
            tableWrapper = this.$inputWrapper.find('.spreadsheet-table > .handsontable').get(0);

        this.dsn = new DSN(this.$inputWrapper.find('.spreadsheet-input-database').val());
        this.spreadsheet = new Spreadsheet(this.dsn, this.$inputWrapper.data('spreadsheet'));
        this.selector = new Selector(sheetWrapper, tableWrapper);

        // build sheet tabs and table for current selection
        this.updateSpreadsheet();
        this.updateInputValues();

        this.initializeEvents(sheetWrapper, tableWrapper);
    }

    initializeEvents(sheetWrapper, tableWrapper) {
        // add events on other wrappers
        sheetWrapper.addEventListener("changeIndex", (event) => {
            // update sheet index and rebuild table
            this.dsn.index = event.detail.index;
            this.updateSpreadsheet(false);
            this.updateInputValues();
        });
        tableWrapper.addEventListener("changeSelection", (event) => {
            // update selected range
            this.dsn.range = event.detail.start + ':' + event.detail.end;
            this.updateInputValues();
        });

        // bind change of file selection
        this.$inputWrapper.on('change', '.spreadsheet-file-select', (event) => {
            this.dsn.fileUid = event.target.value;
            this.dsn.index = 0;
            this.dsn.range = '';
            this.updateSpreadsheet();
            this.updateInputValues();
        });

        // bind click on reset button
        this.$inputWrapper.on('click', '.spreadsheet-reset-button', () => {
            this.dsn = new DSN(this.$inputWrapper.find('.spreadsheet-input-original').val());
            this.updateSpreadsheet();
            this.updateInputValues();
        });

        // bind click on column based extraction toggle
        // TODO: maybe create a simple toggle button for this behaviour
        this.$inputWrapper.on('click', '.spreadsheet-input-direction', (event) => {
            this.dsn.direction = (event.target.value === 'horizontal' ? 'vertical' : 'horizontal');
            if (this.dsn.direction !== 'horizontal') {
                event.target.checked = true;
                this.$inputWrapper.find('.spreadsheet-label-direction-row').hide();
                this.$inputWrapper.find('.spreadsheet-label-direction-column').show();
            } else {
                event.target.checked = false;
                this.$inputWrapper.find('.spreadsheet-label-direction-column').hide();
                this.$inputWrapper.find('.spreadsheet-label-direction-row').show();
            }

            this.updateInputValues();
        });
    }

    updateSpreadsheet() {
        this.spreadsheet.dsn = this.dsn;
        this.selector.buildSheetTabs(this.spreadsheet, this.dsn.index);
        this.selector.buildTable(this.spreadsheet, this.dsn.range.split(':'));
    }

    updateInputValues() {
        let formatted = this.spreadsheet.getSheetName();
        let database = 'spreadsheet://' + this.dsn.fileUid + '?index=' + this.dsn.index;

        // check if table selection is enabled
        if (this.$inputWrapper.find('.spreadsheet-table > .handsontable').length > 0 && this.dsn.range.length > 0) {
            formatted += ' - ' + this.dsn.range;
            database += '&range=' + this.dsn.range;
        }
        // check if direction selection is enabled
        if (this.$inputWrapper.find('.spreadsheet-table .spreadsheet-input-direction').length > 0) {
            formatted += ' - ' + this.dsn.direction;
            database += '&direction=' + this.dsn.direction;
        }

        this.$inputWrapper.find('input.spreadsheet-input-formatted').val(formatted);
        this.$inputWrapper.find('input.spreadsheet-input-database').val(database);
    }
}

// initialize all spreadsheet data inputs
$('.spreadsheet-input-wrap').each((i, element) => {
    new SpreadsheetDataInput(element);
});

