/**
 * Module: TYPO3/CMS/Spreadsheets/SpreadsheetDataInput
 * adds an input field to select data from spreadsheet files
 */
define(['jquery', 'TYPO3/CMS/Backend/Modal', 'Handsontable'], function ($, Modal, Handsontable) {
    'use strict';

    var SpreadsheetDataInput = function () {
        this.$inputWrapper = null;
        this.spreadsheetData = {};
        this.selectedFileUid = 0;
        this.selectedSheetIndex = 0;
        this.selectedSheetName = '';
        this.selectedSheetCells = '';
        this.originalSelectedSheetFileUid = 0;
        this.originalSelectedSheetIndex = 0;
        this.originalSelectedSheetCells = '';
        this.handsOnTableInstance = null;
    };

    SpreadsheetDataInput.prototype.initialize = function (element) {
        var _this = this;
        this.$inputWrapper = $(element);
        this.spreadsheetData = this.$inputWrapper.data('spreadsheet');

        // explode current value to object properties
        var originalDatabaseValue = this.$inputWrapper.find('.spreadsheet-input-original').val(),
            currentDatabaseValue = this.$inputWrapper.find('.spreadsheet-input-database').val();
        originalDatabaseValue = this.splitDatabaseSpreadsheetValue(originalDatabaseValue);
        currentDatabaseValue = this.splitDatabaseSpreadsheetValue(currentDatabaseValue);

        this.originalSelectedSheetFileUid = originalDatabaseValue['file'];
        this.originalSelectedSheetIndex = originalDatabaseValue['sheet'];
        this.originalSelectedSheetCells = originalDatabaseValue['selection'];
        this.selectedSheetCells = currentDatabaseValue['selection'];
        this.setFileUid(currentDatabaseValue['file']);
        this.setSheetIndex(currentDatabaseValue['sheet']);

        // hide select button if current file sheet has only one sheet
        if (this.getCurrentFileSheets().length <= 0) {
            this.$inputWrapper.find('.spreadsheet-select-button').hide();
            this.setSheetIndex(0);
        }

        // bind change of file selection
        this.$inputWrapper.on('change', '.spreadsheet-file-select', function () {
            _this.triggerOpenEditButton(function () {
                var sheetData = _this.getCurrentFileSheets();
                _this.setSheetIndex(0);
                _this.buildSheetTabs(sheetData);
                _this.buildHandsOnTable(sheetData);
                _this.updateInputValues();
            });
        });

        // bind click on edit button
        this.$inputWrapper.on('click', '.spreadsheet-select-button', function () {
            _this.triggerToggleEditButton(function () {
                var sheetData = _this.getCurrentFileSheets();
                _this.buildSheetTabs(sheetData);
                _this.buildHandsOnTable(sheetData);
            }, function () {
                _this.$inputWrapper.find('.spreadsheet-sheets').hide();
                _this.$inputWrapper.find('.spreadsheet-table').hide();
            });
        });

        // bind click on reset button
        this.$inputWrapper.on('click', '.spreadsheet-reset-button', function () {
            _this.triggerCloseEditButton(function () {
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
            });
        });

        // bind click on another sheet
        this.$inputWrapper.on('click', '.spreadsheet-sheets > button', function () {
            var $buttonGroup = $(this).parent();
            _this.setSheetIndex($(this).data('value'));

            $buttonGroup.find('button').addClass('btn-default').removeClass('btn-primary');
            $(this).addClass('btn-primary').removeClass('btn-default');

            _this.triggerOpenEditButton(function () {
                var sheetData = _this.getCurrentFileSheets();
                _this.buildHandsOnTable(sheetData);
                _this.updateInputValues();
            });
        });
    };

    SpreadsheetDataInput.prototype.splitDatabaseSpreadsheetValue = function (value) {
        var result = [],
            fileAndFullSelection = value.split('|', 2),
            sheetAndCellSelection = fileAndFullSelection[1].split('!', 2);

        result['file'] = fileAndFullSelection[0].substr(5);
        result['sheet'] = sheetAndCellSelection[0];
        result['selection'] = sheetAndCellSelection[1] || '';
        return result;
    };

    SpreadsheetDataInput.prototype.triggerOpenEditButton = function (openCallback) {
        var $button = this.$inputWrapper.find('.spreadsheet-select-button'),
            $openIcon = $button.find('.open-icon'),
            $closeIcon = $button.find('.close-icon');

        $openIcon.hide();
        $closeIcon.show();
        if (typeof openCallback === 'function') {
            openCallback();
        }
    };

    SpreadsheetDataInput.prototype.triggerCloseEditButton = function (closeCallback) {
        var $button = this.$inputWrapper.find('.spreadsheet-select-button'),
            $openIcon = $button.find('.open-icon'),
            $closeIcon = $button.find('.close-icon');

        $closeIcon.hide();
        $openIcon.show();
        if (typeof closeCallback === 'function') {
            closeCallback();
        }
    };

    SpreadsheetDataInput.prototype.triggerToggleEditButton = function (openCallback, closeCallback) {
        var $button = this.$inputWrapper.find('.spreadsheet-select-button'),
            $openIcon = $button.find('.open-icon'),
            $closeIcon = $button.find('.close-icon');

        if ($openIcon.is(":visible")) {
            $openIcon.hide();
            $closeIcon.show();
            if (typeof openCallback === 'function') {
                openCallback();
            }
        } else {
            $closeIcon.hide();
            $openIcon.show();
            if (typeof closeCallback === 'function') {
                closeCallback();
            }
        }
    };

    SpreadsheetDataInput.prototype.setFileUid = function (fileUid) {
        if (fileUid > 0) {
            this.selectedFileUid = fileUid;
            var $select = this.$inputWrapper.find('.spreadsheet-file-select');
            $select.val('file:' + fileUid);
            $select.find('option').removeAttr('selected');
            $select.find('option[value="file:' + fileUid + '"]').attr('selected', 'selected');
        }
    };

    SpreadsheetDataInput.prototype.setSheetIndex = function (index) {
        this.selectedSheetIndex = index;

        var sheetData = this.getCurrentFileSheets();
        this.selectedSheetName = this.getSheetName(sheetData, index);
    };

    SpreadsheetDataInput.prototype.getCurrentFileSheets = function () {
        if (typeof this.spreadsheetData === 'undefined' || this.spreadsheetData.length <= 0) {
            return [];
        }

        var currentSelectedFile = this.$inputWrapper.find('select.spreadsheet-file-select').val();
        if (currentSelectedFile === null || currentSelectedFile.length <= 0) {
            return [];
        }

        this.selectedFileUid = currentSelectedFile.substr(5);
        if (typeof this.spreadsheetData[this.selectedFileUid] === 'undefined') {
            return [];
        }
        return this.spreadsheetData[this.selectedFileUid];
    };

    SpreadsheetDataInput.prototype.buildSheetTabs = function (sheetData) {
        var $sheetGroup = this.$inputWrapper.find('.spreadsheet-sheets').empty();
        if (sheetData.length <= 0) {
            $sheetGroup.hide();
        } else {
            var sheetTemplate = '<button type="button" class="btn btn-default" data-value="0">Table1</button>';
            for (var i = 0; i < sheetData.length; i++) {
                var $btn = $(sheetTemplate).attr('data-value', i).text(this.getSheetName(sheetData, i));
                if (parseInt(i) === parseInt(this.selectedSheetIndex)) {
                    $btn.addClass('btn-primary').removeClass('btn-default');
                }
                $btn.appendTo($sheetGroup)
            }
            $sheetGroup.show();
        }

        var $container = this.$inputWrapper.find('.spreadsheet-table > .handsontable');
        if (sheetData.length <= 0 && $container.length === 0) {
            // hide edit button if only one or less sheets are available and we shouldn't show table content
            this.$inputWrapper.find('.spreadsheet-select-button').hide();
        } else {
            this.$inputWrapper.find('.spreadsheet-select-button').show();
        }
    };

    SpreadsheetDataInput.prototype.getSheetName = function (sheetData, index) {
        if (typeof sheetData === 'undefined' || typeof sheetData[index] === 'undefined') {
            return [];
        }
        return sheetData[index]['name'];
    };

    SpreadsheetDataInput.prototype.getSheetData = function (sheetData, index) {
        if (typeof sheetData === 'undefined' || typeof sheetData[index] === 'undefined') {
            return [];
        }
        return sheetData[index]['data'];
    };

    SpreadsheetDataInput.prototype.getSheetCellMetaData = function (sheetData, index) {
        if (typeof sheetData === 'undefined' || typeof sheetData[index] === 'undefined') {
            return [];
        }

        // update meta data array and set classnames for cells
        var cellMetaData = [];
        $.each(sheetData[index]['metaData'], function (i, values) {
            $.each(values, function (j, value) {
                value.className = i;
                cellMetaData.push(value);
            });
        });

        return cellMetaData;
    };

    SpreadsheetDataInput.prototype.getSheetMergeData = function (sheetData, index) {
        if (typeof sheetData === 'undefined' || typeof sheetData[index] === 'undefined') {
            return [];
        }
        return sheetData[index]['mergeData'];
    };

    SpreadsheetDataInput.prototype.highlightCurrentSelectedCell = function (instance, td, row, col, prop, value, cellProperties) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);
        td.style["background"] = '#fce7a1';
    };

    SpreadsheetDataInput.prototype.getCellCoords = function (namedCoordinate) {
        if (typeof namedCoordinate === 'undefined' || namedCoordinate === null) {
            return [];
        }

        if (!isNaN(parseFloat(namedCoordinate)) && isFinite(namedCoordinate)) {
            return [null, parseInt(namedCoordinate) - 1]
        }

        var result = namedCoordinate.split(/([0-9])/),
            column = this.getColFromName(result.shift()),
            row = parseInt(result.join('')) - 1;

        column = isNaN(column) ? null : column;
        row = isNaN(row) ? null : row;
        return [column, row];
    };

    SpreadsheetDataInput.prototype.getColFromName = function (name) {
        if (typeof this.handsOnTableInstance !== 'object' || this.handsOnTableInstance === null) {
            return -1; //return -1 if nothing can be found
        }

        var colCount = this.handsOnTableInstance.countCols();
        for (var i = 0; i < colCount; i++) {
            if (name.toLowerCase() === this.handsOnTableInstance.getColHeader(i).toLowerCase()) {
                return i;
            }
        }
        return -1; //return -1 if nothing can be found
    };

    SpreadsheetDataInput.prototype.buildHandsOnTable = function (sheetData) {
        var _this = this;
        var $container = this.$inputWrapper.find('.spreadsheet-table > .handsontable');
        if ($container.length === 0) {
            // table cell selection is disabled => sheets only
            return;
        }

        if (typeof this.handsOnTableInstance === 'object' && this.handsOnTableInstance !== null) {
            // this.handsOnTableInstance.destroy();
            $container.empty();
        }

        var data = this.getSheetData(sheetData, this.selectedSheetIndex),
            cells = this.getSheetCellMetaData(sheetData, this.selectedSheetIndex),
            mergeCells = this.getSheetMergeData(sheetData, this.selectedSheetIndex);

        this.handsOnTableInstance = new Handsontable($container.get(0), {
            data: data,
            cell: cells,
            mergeCells: mergeCells,
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
                    startPoint = this.getColHeader(cols.start);
                    endPoint = this.getColHeader(cols.end);
                }
                // one or multiple rows are selected
                else if (cols.start === 0 && (cols.end + 1) === maxCols) {
                    startPoint = (rows.start + 1);
                    endPoint = (rows.end + 1);
                }
                // on default columns with rows (range) is selected
                else {
                    startPoint = this.getColHeader(cols.start) + (rows.start + 1);
                    endPoint = this.getColHeader(cols.end) + (rows.end + 1);
                }

                _this.selectedSheetCells = startPoint + ':' + endPoint;
                _this.updateInputValues();
            }
        });

        // show spreadsheet
        this.$inputWrapper.find('.spreadsheet-table').show();
    };

    SpreadsheetDataInput.prototype.switchValues = function (object, prop1, prop2) {
        var temp = object[prop1];
        object[prop1] = object[prop2];
        object[prop2] = temp;
    };

    SpreadsheetDataInput.prototype.updateInputValues = function () {
        if (this.$inputWrapper.find('.spreadsheet-table > .handsontable').length === 0) {
            // table cell selection is disabled => sheets only
            this.$inputWrapper.find('input.spreadsheet-input-database').val('file:' + this.selectedFileUid + '|' + this.selectedSheetIndex);
            this.$inputWrapper.find('input.spreadsheet-input-formatted').val(this.selectedSheetName);
        } else {
            this.$inputWrapper.find('input.spreadsheet-input-database').val('file:' + this.selectedFileUid + '|' + this.selectedSheetIndex + '!' + this.selectedSheetCells);
            this.$inputWrapper.find('input.spreadsheet-input-formatted').val(this.selectedSheetName + '!' + this.selectedSheetCells);
        }
    };

    $('.spreadsheet-input-wrap').each(function () {
        var input = new SpreadsheetDataInput();
        input.initialize(this);
    });

    return SpreadsheetDataInput;
});
