import $ from 'jquery';
import DSN from './dsn';

export default class SheetData {
    constructor(dsn, data) {
        if (!(dsn instanceof DSN)) {
            throw new Error('SheetData class expects dsn parameter to be type of a DSN class');
        }
        this.data = data;
        this.defaultFileUid = dsn.getFileUid();
        this.defaultSheetIndex = dsn.getIndex();
    }

    set fileUid(fileUid) {
        this.defaultFileUid = fileUid;
    }

    set sheetIndex(fileUid) {
        this.defaultSheetIndex = fileUid;
    }

    getAllSheets(fileUid = this.defaultFileUid) {
        return this.data[fileUid] || [];
    }

    getSheet(index = this.defaultSheetIndex, fileUid = this.defaultFileUid) {
        return this.data[fileUid][index] || [];
    }

    getSheetName(index = this.defaultSheetIndex, fileUid = this.defaultFileUid) {
        return this.getSheet(index, fileUid)['name'] || '';
    }

    getSheetData(index = this.defaultSheetIndex, fileUid = this.defaultFileUid) {
        return this.getSheet(index, fileUid)['data'] || [];
    }

    getSheetMergeData(index = this.defaultSheetIndex, fileUid = this.defaultFileUid) {
        return this.getSheet(index, fileUid)['mergeData'] || [];
    }

    getSheetCellMetaData(index = this.defaultSheetIndex, fileUid = this.defaultFileUid) {
        const metaData = this.getSheet(index, fileUid)['metaData'] || [];
        if (metaData.length <= 0) {
            return metaData;
        }

        // update meta data array and set classnames for cells
        let cellMetaData = [];
        $.each(metaData, (i, values) => {
            $.each(values, (j, value) => {
                value.className = i;
                cellMetaData.push(value);
            });
        });

        return cellMetaData;
    }
}
