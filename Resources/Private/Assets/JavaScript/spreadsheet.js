import DSN from './dsn';

export default class Spreadsheet {
    constructor(dsn, data) {
        if (!(dsn instanceof DSN)) {
            throw new Error('Spreadsheet class expects dsn parameter to be type of a DSN class');
        }
        this.data = data;
        this.defaultFileUid = dsn.fileUid;
        this.defaultSheetIndex = dsn.index;
    }

    set dsn(dsn) {
        if (!(dsn instanceof DSN)) {
            throw new Error('Spreadsheet class setter "dsn" expects parameter to be type of a DSN class');
        }
        this.defaultFileUid = dsn.fileUid;
        this.defaultSheetIndex = dsn.index;
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

        // update meta data array and set class names for cells
        let cellMetaData = [];
        for (let [className, values] of Object.entries(metaData)) {
            for (let value of Object.values(values)) {
                value.className = className;
                cellMetaData.push(value);
            }
        }

        return cellMetaData;
    }
}
