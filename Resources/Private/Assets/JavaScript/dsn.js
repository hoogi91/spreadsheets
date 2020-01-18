export default class DSN {
    constructor(value) {
        const matches = value.match(/^spreadsheet:\/\/(\d+)(?:\?(.+))?/);
        if (matches === null) {
            throw new Error('DSN class expects value to be of type string and format "spreadsheet://index=0..."');
        }

        this.properties = {};
        if (typeof matches[2] === 'undefined') {
            this.properties.fileUid = matches[1];
        } else {
            const query = JSON.parse(
                '{"' + matches[2].replace(/&/g, '","').replace(/=/g, '":"') + '"}',
                function (key, value) {
                    return key === "" ? value : decodeURIComponent(value);
                }
            );

            this.properties.fileUid = matches[1];
            this.properties.index = query['index'] || 0;
            this.properties.range = query['range'] || '';
            this.properties.direction = query['direction'] || 'horizontal';
        }
    }

    /**
     * @returns {int}
     */
    get fileUid() {
        return this.properties.fileUid;
    }

    /**
     * @param {int} fileUid
     */
    set fileUid(fileUid) {
        this.properties.fileUid = fileUid;
    }

    /**
     * @returns {int}
     */
    get index() {
        return this.properties.index;
    }

    /**
     * @param {int} index
     */
    set index(index) {
        this.properties.index = index;
    }

    /**
     * @returns {string}
     */
    get range() {
        return this.properties.range;
    }

    /**
     * @param {string} range
     */
    set range(range) {
        this.properties.range = range;
    }

    /**
     * @returns {string}
     */
    get direction() {
        return this.properties.direction;
    }

    /**
     * @param {string} direction
     */
    set direction(direction) {
        this.properties.direction = direction;
    }
}
