import $ from 'jquery';
import Handsontable from 'handsontable';
import DSN from './dsn';
import SheetData from './sheetData';
import Helper from './helper';

class SpreadsheetDataInput {
    constructor(element) {
        this.$inputWrapper = $(element);
        this.selectedFileUid = 0;
        this.selectedSheetIndex = 0;
        this.selectedSheetName = '';
        this.selectedSheetCells = '';
        this.directionOfSelection = '';
        this.originalSelectedSheetFileUid = 0;
        this.originalSelectedSheetIndex = 0;
        this.originalSelectedSheetCells = '';
        this.originalDirectionOfSelection = '';
        this.handsOnTableInstance = null;
        this.initialize();
    }

    initialize() {
        const _this = this;

        // explode current value to object properties
        let originalDatabaseValue = new DSN(this.$inputWrapper.find('.spreadsheet-input-original').val()),
            currentDatabaseValue = new DSN(this.$inputWrapper.find('.spreadsheet-input-database').val());

        this.spreadsheetData = new SheetData(currentDatabaseValue, this.$inputWrapper.data('spreadsheet'));

        this.originalSelectedSheetFileUid = originalDatabaseValue.getFileUid();
        this.originalSelectedSheetIndex = originalDatabaseValue.getIndex();
        this.originalSelectedSheetCells = originalDatabaseValue.getRange();
        this.originalDirectionOfSelection = originalDatabaseValue.getDirection();
        this.selectedSheetCells = currentDatabaseValue.getRange();
        this.setFileUid(currentDatabaseValue.getFileUid());
        this.setDirectionOfSelection(currentDatabaseValue.getDirection());

        // hide select button if current file sheet has only one sheet
        if (this.spreadsheetData.getAllSheets().length <= 0) {
            this.$inputWrapper.find('.spreadsheet-select-button').hide();
            this.setSheetIndex(0);
        } else {
            this.setSheetIndex(currentDatabaseValue.getIndex());
        }

        this.updateInputValues();

        // bind change of file selection
        this.$inputWrapper.on('change', '.spreadsheet-file-select', function () {
            const $button = _this.$inputWrapper.find('.spreadsheet-select-button');
            $button.find('.open-icon').hide();
            $button.find('.close-icon').show();

            _this.setFileUid(this.value);
            _this.setSheetIndex(0);
            _this.setDirectionOfSelection('');
            _this.buildSheetTabs();
            _this.buildHandsOnTable();
            _this.updateInputValues();
        });

        // bind click on edit button
        this.$inputWrapper.on('click', '.spreadsheet-select-button', function () {
            const $button = _this.$inputWrapper.find('.spreadsheet-select-button'),
                $openIcon = $button.find('.open-icon'),
                $closeIcon = $button.find('.close-icon');

            if ($openIcon.is(":visible")) {
                $openIcon.hide();
                $closeIcon.show();

                _this.buildSheetTabs();
                _this.buildHandsOnTable();
            } else {
                $closeIcon.hide();
                $openIcon.show();

                _this.$inputWrapper.find('.spreadsheet-sheets').hide();
                _this.$inputWrapper.find('.spreadsheet-table').hide();
            }
        });

        // bind click on reset button
        this.$inputWrapper.on('click', '.spreadsheet-reset-button', function () {
            const $button = _this.$inputWrapper.find('.spreadsheet-select-button');
            $button.find('.open-icon').show();
            $button.find('.close-icon').hide();

            // hide sheets and table
            _this.$inputWrapper.find('.spreadsheet-sheets').hide();
            _this.$inputWrapper.find('.spreadsheet-table').hide();

            // reset hidden and text input values
            _this.$inputWrapper.find('.spreadsheet-input-database').val(
                _this.$inputWrapper.find('.spreadsheet-input-original').val()
            );
            _this.$inputWrapper.find('.spreadsheet-input-formatted').val(
                _this.$inputWrapper.find('.spreadsheet-input-original-formatted').val()
            );

            // reset object properties
            _this.selectedSheetCells = _this.originalSelectedSheetCells;
            _this.setFileUid(_this.originalSelectedSheetFileUid); // includes update of select
            _this.setSheetIndex(_this.originalSelectedSheetIndex);
            _this.setDirectionOfSelection(_this.originalDirectionOfSelection);
            _this.updateInputValues();
        });

        // bind click on another sheet
        this.$inputWrapper.on('click', '.spreadsheet-sheets > button', function () {
            const $buttonGroup = $(this).parent();
            _this.setSheetIndex($(this).data('value'));

            $buttonGroup.find('button').addClass('btn-default').removeClass('btn-primary');
            $(this).addClass('btn-primary').removeClass('btn-default');

            const $button = _this.$inputWrapper.find('.spreadsheet-select-button');
            $button.find('.open-icon').hide();
            $button.find('.close-icon').show();

            _this.buildHandsOnTable();
            _this.updateInputValues();
        });

        // bind click on column based extraction toggle
        this.$inputWrapper.on('click', '.spreadsheet-input-direction', function () {
            _this.setDirectionOfSelection(this.value === 'horizontal' ? 'vertical' : 'horizontal');
            _this.updateInputValues();
        });
    }

    setFileUid(fileUid) {
        if (fileUid > 0) {
            this.selectedFileUid = fileUid;
            this.spreadsheetData.fileUid = this.selectedFileUid;

            const $select = this.$inputWrapper.find('.spreadsheet-file-select');
            $select.val(fileUid);
            $select.find('option').removeAttr('selected');
            $select.find('option[value="' + fileUid + '"]').attr('selected', 'selected');
        }
    }

    setSheetIndex(index) {
        this.selectedSheetIndex = index;
        this.spreadsheetData.sheetIndex = this.selectedSheetIndex;
        this.selectedSheetName = this.spreadsheetData.getSheetName();
    }

    setDirectionOfSelection(direction) {
        const checkbox = this.$inputWrapper.find('.spreadsheet-input-direction').get(0);
        if (typeof checkbox !== 'undefined') {
            this.directionOfSelection = checkbox.value = direction;
            if (this.directionOfSelection !== 'horizontal') {
                checkbox.checked = true;
                this.$inputWrapper.find('.spreadsheet-label-direction-row').hide();
                this.$inputWrapper.find('.spreadsheet-label-direction-column').show();
            } else {
                checkbox.checked = false;
                this.$inputWrapper.find('.spreadsheet-label-direction-column').hide();
                this.$inputWrapper.find('.spreadsheet-label-direction-row').show();
            }
        } else {
            this.directionOfSelection = '';
        }
    }

    buildSheetTabs() {
        const $sheetGroup = this.$inputWrapper.find('.spreadsheet-sheets').empty();
        if (this.spreadsheetData.getAllSheets().length <= 0) {
            $sheetGroup.hide();
        } else {
            const sheetTemplate = '<button type="button" class="btn btn-default" data-value="0">Table1</button>';
            for (let i = 0; i < this.spreadsheetData.getAllSheets().length; i++) {
                const $btn = $(sheetTemplate).attr('data-value', i).text(this.spreadsheetData.getSheetName(i));
                if (parseInt(i) === parseInt(this.selectedSheetIndex)) {
                    $btn.addClass('btn-primary').removeClass('btn-default');
                }
                $btn.appendTo($sheetGroup);
            }
            $sheetGroup.show();
        }

        const $container = this.$inputWrapper.find('.spreadsheet-table > .handsontable');
        if (this.spreadsheetData.getAllSheets().length <= 0 && $container.length === 0) {
            // hide edit button if only one or less sheets are available and we shouldn't show table content
            this.$inputWrapper.find('.spreadsheet-select-button').hide();
        } else {
            this.$inputWrapper.find('.spreadsheet-select-button').show();
        }
    }

    /**
     * @deprecated Will be removed with Handsontable migration
     */
    highlightCurrentSelectedCell(instance, td) {
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

    /**
     * @deprecated Will be removed with Handsontable migration
     */
    buildHandsOnTable() {
        const _this = this;
        const $container = this.$inputWrapper.find('.spreadsheet-table > .handsontable');
        if ($container.length === 0) {
            // table cell selection is disabled => sheets only
            return;
        }

        if (typeof this.handsOnTableInstance === 'object' && this.handsOnTableInstance !== null) {
            // this.handsOnTableInstance.destroy();
            $container.empty();
        }

        const data = this.spreadsheetData.getSheetData();
        this.handsOnTableInstance = new Handsontable($container.get(0), {
            data: data,
            cell: this.spreadsheetData.getSheetCellMetaData(),
            mergeCells: this.spreadsheetData.getSheetMergeData(),
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
            cells: function (row, col) {
                var cellProperties = {};
                var currentSelectedRange = _this.originalSelectedSheetCells.split(':'),
                    startCoords = _this.getCellCoords(currentSelectedRange[0]),
                    endCoords = _this.getCellCoords(currentSelectedRange[1]);

                cellProperties.renderer = 'text';
                if (col >= startCoords[0] && row >= startCoords[1] && col <= endCoords[0] && row <= endCoords[1]) {
                    // a range or single cell will be highlighted
                    cellProperties.renderer = SpreadsheetDataInput.prototype.highlightCurrentSelectedCell;
                } else if (startCoords[1] === null && endCoords[1] === null && col >= startCoords[0] && col <= endCoords[0]) {
                    // only one or more columns are selected and will be highlighted (no specific rows)
                    cellProperties.renderer = SpreadsheetDataInput.prototype.highlightCurrentSelectedCell;
                } else if (startCoords[0] === null && endCoords[0] === null && row >= startCoords[1] && row <= endCoords[1]) {
                    // only one or more rows are selected and will be highlighted (no specific columns)
                    cellProperties.renderer = SpreadsheetDataInput.prototype.highlightCurrentSelectedCell;
                }
                return cellProperties;
            },
            afterSelectionEnd: function (rowStart, columnStart, rowEnd, columnEnd) {
                var rows = {start: rowStart, end: rowEnd},
                    cols = {start: columnStart, end: columnEnd};

                // fix ordering of rows and columns
                if (rows.start > rows.end) {
                    _this.switchValues(rows, 'start', 'end');
                }
                if (cols.start > cols.end) {
                    _this.switchValues(cols, 'start', 'end');
                }

                var maxRows = this.countRows(),
                    maxCols = this.countCols(),
                    startPoint = '',
                    endPoint = '';

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

                _this.selectedSheetCells = startPoint + ':' + endPoint;
                _this.updateInputValues();
            }
        });

        // show spreadsheet
        this.$inputWrapper.find('.spreadsheet-table').show();
    }

    switchValues(object, prop1, prop2) {
        var temp = object[prop1];
        object[prop1] = object[prop2];
        object[prop2] = temp;
    }

    updateInputValues() {
        if (this.$inputWrapper.find('.spreadsheet-table > .handsontable').length === 0) {
            // table cell selection is disabled => sheets only
            this.$inputWrapper.find('input.spreadsheet-input-database').val('spreadsheet://' + this.selectedFileUid + '?index=' + this.selectedSheetIndex);
            this.$inputWrapper.find('input.spreadsheet-input-formatted').val(this.selectedSheetName);
        } else if (this.$inputWrapper.find('.spreadsheet-table .spreadsheet-input-direction').length !== 0) {
            // cell selection and direction selction are enabled
            this.$inputWrapper.find('input.spreadsheet-input-database').val('spreadsheet://' + this.selectedFileUid + '?index=' + this.selectedSheetIndex + '&range=' + this.selectedSheetCells + '&direction=' + this.directionOfSelection);
            this.$inputWrapper.find('input.spreadsheet-input-formatted').val(this.selectedSheetName + ' - ' + this.selectedSheetCells + ' - ' + this.directionOfSelection);
        } else {
            // only cell selection is enabled
            this.$inputWrapper.find('input.spreadsheet-input-database').val('spreadsheet://' + this.selectedFileUid + '?index=' + this.selectedSheetIndex + '&range=' + this.selectedSheetCells);
            this.$inputWrapper.find('input.spreadsheet-input-formatted').val(this.selectedSheetName + ' - ' + this.selectedSheetCells);
        }
    }
}

// initialize all spreadsheet data inputs
$('.spreadsheet-input-wrap').each((i, element) => {
    new SpreadsheetDataInput(element);
});

