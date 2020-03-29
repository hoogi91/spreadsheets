export default class SheetBuilder {
    constructor(element) {
        this.sheetWrapper = element;

        // bind click on another sheet
        this.sheetWrapper.addEventListener('click', (event) => {
            if (event.target.tagName === 'A') {
                for (let node of event.target.parentNode.childNodes) {
                    node.classList.remove('active');
                }
                event.target.classList.add('active');

                // trigger index change event
                this.sheetWrapper.dispatchEvent(new CustomEvent("changeIndex", {
                    detail: {
                        index: event.target.getAttribute('data-value')
                    }
                }));
            }
        });
    }

    buildTabs(spreadsheetData, selectedSheetIndex) {
        this.sheetWrapper.textContent = "";
        if (spreadsheetData.getAllSheets().length <= 0) {
            this.sheetWrapper.style.display = 'none';
        } else {
            for (let index = 0; index < spreadsheetData.getAllSheets().length; index++) {
                // create list items and append to sheet wrapper
                this.sheetWrapper.appendChild(this.createListItem(
                    index,
                    spreadsheetData.getSheetName(index),
                    index === parseInt(selectedSheetIndex)
                ));
            }
            this.sheetWrapper.style.display = 'block';
        }
    }

    /**
     *
     * @param {int} index
     * @param {string} name
     * @param {boolean} activeItem
     * @returns {HTMLLIElement}
     */
    createListItem(index, name, activeItem = false) {
        const link = document.createElement('a');
        link.setAttribute('href', '#');
        // link.setAttribute('onclick', 'return false;');
        link.setAttribute('data-value', index);
        link.innerText = name;

        const listItem = document.createElement('li');
        if (activeItem === true) {
            listItem.classList.add('active');
        }
        listItem.appendChild(link);

        return listItem;
    }
}
