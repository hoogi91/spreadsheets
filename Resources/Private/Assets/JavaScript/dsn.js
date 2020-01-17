export default class DSN {
    constructor(value) {
        const matches = value.match(/^spreadsheet:\/\/(\d+)(?:\?(.+))?/);
        if (matches === null) {
            throw new Error('DSN class expects value to be of type string and format "spreadsheet://index=0..."');
        }

        if (typeof matches[2] === 'undefined') {
            this.fileUid = matches[1];
        } else {
            const query = JSON.parse(
                '{"' + matches[2].replace(/&/g, '","').replace(/=/g, '":"') + '"}',
                function (key, value) {
                    return key === "" ? value : decodeURIComponent(value);
                }
            );

            this.fileUid = matches[1];
            this.index = query['index'] || 0;
            this.range = query['range'] || '';
            this.direction = query['direction'] || 'horizontal';
        }
    }

    getFileUid() {
        return this.fileUid;
    }

    getIndex() {
        return this.index || 0;
    }

    getRange() {
        return this.range || '';
    }

    getDirection() {
        return this.direction || 'horizontal';
    }
}
