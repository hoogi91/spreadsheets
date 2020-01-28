export default class SheetBuilder {
    constructor(element) {
        this.sheetWrapper = element;

        // bind click on another sheet
        this.sheetWrapper.addEventListener('click', (event) => {
            if (event.target.tagName === 'BUTTON') {
                for (let node of event.target.parentNode.childNodes) {
                    node.classList.replace('btn-primary', 'btn-default');
                }
                event.target.classList.replace('btn-default', 'btn-primary');

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
                const button = document.createElement('button');
                button.setAttribute('type', 'button');
                button.setAttribute('data-value', index);
                button.classList.add('btn', (index === parseInt(selectedSheetIndex) ? 'btn-primary' : 'btn-default'));
                button.innerText = spreadsheetData.getSheetName(index);
                this.sheetWrapper.appendChild(button);
            }
            this.sheetWrapper.style.display = 'block';
        }
    }
}
