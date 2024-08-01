import {colHeaderToIndex} from "./helper.js";

export default class DSN {
    constructor(value) {
        this.properties = {};
        if (value.length === 0) {
            return;
        }

        const matches = value.match(/^spreadsheet:\/\/(\d+)(?:\?(.+))?/);
        if (matches === null) {
            throw new Error('DSN class expects value to be of type string and format "spreadsheet://index=0..."');
        }

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
            this.properties.direction = query['direction'] || 'horizontal';

            // set range via setter
            this.range = query['range'] || '';
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
     * @returns {{startCol: number, startRow: number, endCol: number, endRow: number} | null}
     */
    get coordinates() {
        return this.properties.coordinates || null;
    }

    /**
     * @returns {string}
     */
    get range() {
        return this.properties.range || '';
    }

    /**
     * @param {string} range
     */
    set range(range) {
        this.properties.range = range;

        let matches = range.match(/^([A-Z]+|\d+)(\d+)?:([A-Z]+|\d+)(\d+)?$/);
        if (matches === null) {
            return;
        }

        matches = Array.from(matches).slice(1);
        if (!Number.isNaN(parseInt(matches[0]))) {
            matches[1] = parseInt(matches[0]);
            matches[0] = null;
        }
        if (!Number.isNaN(parseInt(matches[2]))) {
            matches[3] = parseInt(matches[2]);
            matches[2] = null;
        }

        // equalize col indexes
        matches[0] = matches[0] || (matches[2] || null);
        matches[2] = matches[2] || matches[0];
        // equalize row indexes
        matches[1] = matches[1] || (matches[3] || null);
        matches[3] = matches[3] || matches[1];

        this.properties.coordinates = {
            startCol: matches[0] !== null ? colHeaderToIndex(matches[0]) : null,
            startRow: matches[1] !== null ? parseInt(matches[1]) : null,
            endCol: matches[2] !== null ? colHeaderToIndex(matches[2]) : null,
            endRow: matches[3] !== null ? parseInt(matches[3]) : null,
        };
    }

    /**
     * @returns {string}
     */
    get direction() {
        return this.properties.direction || '';
    }

    /**
     * @param {string} direction
     */
    set direction(direction) {
        this.properties.direction = direction;
    }
}
