import*as e from"@typo3/backend/document-service.js";var t={};function s(e){const t=e.toString(24);let s="";for(let e=0;e<t.length;e++){let i=t[e],r=(1*i).toString()===i?i:i.charCodeAt(0)-97+10;0===e&&(r-=1),s+=String.fromCharCode(97+r)}return s.toUpperCase()}function i(e){const t="ABCDEFGHIJKLMNOPQRSTUVWXYZ";let s=0;for(let i=0,r=e.length-1;i<e.length;i+=1,r-=1)s+=Math.pow(26,r)*(t.indexOf(e[i])+1);return s}function r(e,t){let i=arguments.length>2&&void 0!==arguments[2]?arguments[2]:null;return"row"===i?t:"column"===i?s(e):s(e)+t}function n(e){let t=!(arguments.length>1&&void 0!==arguments[1])||arguments[1],s=parseInt(e.getAttribute("data-col"));!0===t&&!0===e.hasAttribute("colspan")&&(s+=parseInt(e.getAttribute("colspan"))-1);let i=parseInt(e.getAttribute("data-row"));return!0===t&&!0===e.hasAttribute("rowspan")&&(i+=parseInt(e.getAttribute("rowspan"))-1),{colIndex:s,rowIndex:i}}t.d=(e,s)=>{for(var i in s)t.o(s,i)&&!t.o(e,i)&&Object.defineProperty(e,i,{enumerable:!0,get:s[i]})},t.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t);class l{constructor(e){if(this.properties={},0===e.length)return;const t=e.match(/^spreadsheet:\/\/(\d+)(?:\?(.+))?/);if(null===t)throw new Error('DSN class expects value to be of type string and format "spreadsheet://index=0..."');if(void 0===t[2])this.properties.fileUid=t[1];else{const e=JSON.parse('{"'+t[2].replace(/&/g,'","').replace(/=/g,'":"')+'"}',(function(e,t){return""===e?t:decodeURIComponent(t)}));this.properties.fileUid=t[1],this.properties.index=e.index||0,this.properties.direction=e.direction||"horizontal",this.range=e.range||""}}get fileUid(){return this.properties.fileUid}set fileUid(e){this.properties.fileUid=e}get index(){return this.properties.index}set index(e){this.properties.index=e}get coordinates(){return this.properties.coordinates||null}get range(){return this.properties.range||""}set range(e){this.properties.range=e;let t=e.match(/^([A-Z]+|\d+)(\d+)?:([A-Z]+|\d+)(\d+)?$/);null!==t&&(t=Array.from(t).slice(1),Number.isNaN(parseInt(t[0]))||(t[1]=parseInt(t[0]),t[0]=null),Number.isNaN(parseInt(t[2]))||(t[3]=parseInt(t[2]),t[2]=null),t[0]=t[0]||t[2]||null,t[2]=t[2]||t[0],t[1]=t[1]||t[3]||null,t[3]=t[3]||t[1],this.properties.coordinates={startCol:null!==t[0]?i(t[0]):null,startRow:null!==t[1]?parseInt(t[1]):null,endCol:null!==t[2]?i(t[2]):null,endRow:null!==t[3]?parseInt(t[3]):null})}get direction(){return this.properties.direction||""}set direction(e){this.properties.direction=e}}class a{constructor(e,t){this.sheetWrapper=e,this.sheetWrapper.addEventListener("click",(e=>{if("A"===e.target.tagName){for(let t of e.target.parentNode.childNodes)t.classList.remove("active");e.target.classList.add("active"),this.sheetWrapper.dispatchEvent(new CustomEvent("changeIndex",{detail:{index:e.target.getAttribute("data-value")}}))}})),null!==t&&(this.tableWrapper=t)}update(e,t){if(!(t instanceof l))throw new Error('Renderer class "update" method expects parameter to be type of a DSN class');this.buildTabs(e,t.index),this.buildTable(e,t.coordinates)}buildTabs(e,t){if(this.sheetWrapper.textContent="",e.getAllSheets().length<=0)this.sheetWrapper.style.display="none";else{for(let s=0;s<e.getAllSheets().length;s++){const i=document.createElement("a");i.setAttribute("href","#"),i.setAttribute("data-value",s),i.classList.add("nav-link"),i.innerText=e.getSheetName(s);const r=document.createElement("li");r.classList.add("nav-item"),s===parseInt(t)&&(i.classList.add("active"),r.classList.add("active")),r.appendChild(i),this.sheetWrapper.appendChild(r)}this.sheetWrapper.style.display=""}}buildTable(e){let t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:null;if(void 0===this.tableWrapper||null===this.tableWrapper)return;const s=Object.values(e.getSheetData()).map((e=>Object.values(e)));if(s.length<=0)return;const i=document.createElement("table");this.buildTableHeader(i,Math.max(...s.map((e=>e.length)))),this.buildTableBody(i,s,t),this.tableWrapper.textContent="",this.tableWrapper.appendChild(i),this.tableWrapper.style.display="block"}buildTableHeader(e,t){const i=e.createTHead().insertRow();for(let e=0;e<=t;e++)if(e>0){const t=i.insertCell();t.innerText=s(e),t.setAttribute("data-col",e)}else i.insertCell()}buildTableBody(e,t){let s=arguments.length>2&&void 0!==arguments[2]?arguments[2]:null;const i=e.createTBody();let r=[];t.forEach(((e,t)=>{const n=i.insertRow(),l=n.insertCell();l.innerText=t+1,l.setAttribute("data-row",t+1);let a=0;e.forEach((e=>{const i=n.insertCell();if(i.innerText=e.val,void 0!==e.css&&i.setAttribute("class",e.css.split("-").filter((e=>e.length>0)).map((e=>"align-"+e)).join(" ")),void 0!==e.col){i.setAttribute("colspan",e.col);for(let s=1;s<e.col;s++)r.push(t+"-"+(a+s))}if(void 0!==e.row){i.setAttribute("rowspan",e.row);for(let s=1;s<e.row;s++)if(r.push(t+s+"-"+a),void 0!==e.col)for(let i=1;i<e.col;i++)r.push(t+s+"-"+(a+i))}for(;-1!==r.indexOf(t+"-"+a);)a++;i.setAttribute("data-col",a+1),i.setAttribute("data-row",t+1),null!==s&&s.startRow<=t+1&&s.endRow>=t+1&&s.startCol<=a+1&&s.endCol>=a+1&&i.classList.add("highlight"),a++}))}))}}class o{constructor(e,t){if(!(e instanceof l))throw new Error("Spreadsheet class expects dsn parameter to be type of a DSN class");this.data=t,this.defaultFileUid=e.fileUid,this.defaultSheetIndex=e.index}set dsn(e){if(!(e instanceof l))throw new Error('Spreadsheet class setter "dsn" expects parameter to be type of a DSN class');this.defaultFileUid=e.fileUid,this.defaultSheetIndex=e.index}getAllSheets(){let e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:this.defaultFileUid;return this.data[e]||[]}getSheet(){let e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:this.defaultSheetIndex,t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:this.defaultFileUid;return void 0===this.data[t]?[]:this.data[t][e]||[]}getSheetName(){let e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:this.defaultSheetIndex,t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:this.defaultFileUid;return this.getSheet(e,t).name||""}getSheetData(){let e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:this.defaultSheetIndex,t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:this.defaultFileUid;return this.getSheet(e,t).cells||[]}}class d{constructor(e){if(this.cursor={isSelecting:!1,selectMode:null},this.properties={},this.tableWrapper=e,null!==this.tableWrapper){this.tableWrapper.addEventListener("mousedown",(e=>{const t=document.elementFromPoint(e.x,e.y);this.cursor.isSelecting=!0,this.cursor.start=t,this.cursor.selectMode=null,this.reachedColumnHeader(t)?this.cursor.selectMode="column":this.reachedRowHeader(t)&&(this.cursor.selectMode="row")}));const e=e=>{if(!0!==this.cursor.isSelecting)return!1;"mouseup"===e.type&&(this.cursor.isSelecting=!1,window.getSelection?window.getSelection().empty?window.getSelection().empty():window.getSelection().removeAllRanges&&window.getSelection().removeAllRanges():document.selection&&document.selection.empty());const t=document.elementFromPoint(e.x,e.y);return!1!==this.isInsideTable(t)&&((!this.reachedColumnHeader(t)||!this.reachedRowHeader(t))&&(("column"!==this.cursor.selectMode||!this.reachedRowHeader(t))&&(("row"!==this.cursor.selectMode||!this.reachedColumnHeader(t))&&((null!==this.cursor.selectMode||!this.reachedColumnHeader(t)&&!this.reachedRowHeader(t))&&(t!==this.cursor.start?(this.cursor.end=t,this.selection=[this.cursor.start,this.cursor.end]):this.selection=[this.cursor.start],this.calculateMergeCells(),this.highlightSelection(),void this.tableWrapper.dispatchEvent(new CustomEvent("changeSelection",{detail:{start:this.selection.start,end:this.selection.end}})))))))};this.tableWrapper.addEventListener("mousemove",function(e,t){let s,i;return function(){const r=this,n=arguments;i?(clearTimeout(s),s=setTimeout((function(){Date.now()-i>=e&&(t.apply(r,n),i=Date.now())}),e-(Date.now()-i))):(t.apply(r,n),i=Date.now())}}(60,e)),this.tableWrapper.addEventListener("mouseup",e)}}get selection(){return this.properties.selection}set selection(e){if(e.length<=0)return;let t={min:null,max:null},s={min:null,max:null};e.forEach((e=>{const i=n(e,!1);(null===t.min||t.min>i.colIndex)&&(t.min=i.colIndex),(null===s.min||s.min>i.rowIndex)&&(s.min=i.rowIndex);const r=n(e,!0);(null===t.max||t.max<r.colIndex)&&(t.max=r.colIndex),(null===s.max||s.max<r.rowIndex)&&(s.max=r.rowIndex)})),this.properties.selection={elements:e,indexes:{col:t,row:s}},this.properties.selection.start=r(t.min,s.min,this.cursor.selectMode),1===e.length?this.properties.selection.end=this.properties.selection.start:this.properties.selection.end=r(t.max,s.max,this.cursor.selectMode)}isInsideTable(e){return null!==e&&null!==e.closest("table")}reachedColumnHeader(e){return null!==e&&null!==e.closest("thead")}reachedRowHeader(e){return null!==e&&e.closest("tr").querySelector("td")===e}calculateMergeCells(){let e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:[],t=this.selection.indexes.col,s=this.selection.indexes.row;"row"===this.cursor.selectMode?t={min:1,max:this.tableWrapper.querySelector("table").rows[0].cells.length}:"column"===this.cursor.selectMode&&(s={min:1,max:this.tableWrapper.querySelector("table").rows.length});e:for(let i=s.min;i<=s.max;i++)for(let r=t.min;r<=t.max;r++){if(-1!==e.indexOf(r+"-"+i))continue;const l=this.tableWrapper.querySelector('td[data-col="'+r+'"][data-row="'+i+'"][colspan],td[data-col="'+r+'"][data-row="'+i+'"][rowspan]');if(null===l)continue;const a=n(l,!1);e.push(a.colIndex+"-"+a.rowIndex);const o=n(l,!0);if(o.colIndex>t.max||o.rowIndex>s.max){this.selection=[...this.selection.elements,l],this.calculateMergeCells(e);break e}}const i=this.tableWrapper.querySelectorAll("td[colspan], td[rowspan]");for(let r=0;r<i.length;++r){const l=i[r],a=n(l,!1);if(-1!==e.indexOf(a.colIndex+"-"+a.rowIndex))continue;const o=n(l,!0);if((a.colIndex<t.min&&"column"===this.cursor.selectMode||a.rowIndex<s.min&&"row"===this.cursor.selectMode)&&o.colIndex>=t.min&&o.rowIndex>=s.min){this.selection=[...this.selection.elements,l],this.calculateMergeCells(e);break}}}highlightSelection(){const e=this.selection.indexes.col,t=this.selection.indexes.row,s=[];if("row"===this.cursor.selectMode)for(let e=t.min;e<=t.max;e++)s.push(...this.tableWrapper.querySelectorAll('td[data-row="'+e+'"]'));else if("column"===this.cursor.selectMode)for(let t=e.min;t<=e.max;t++)s.push(...this.tableWrapper.querySelectorAll('td[data-col="'+t+'"]'));else for(let i=e.min;i<=e.max;i++)for(let e=t.min;e<=t.max;e++)s.push(this.tableWrapper.querySelector('td[data-col="'+i+'"][data-row="'+e+'"]'));Array.from(this.tableWrapper.querySelectorAll("td.highlight")).filter((e=>null!==e)).forEach((e=>e.classList.remove("highlight"))),s.filter((e=>null!==e)).forEach((e=>e.classList.add("highlight")))}}const h=(e=>{var s={};return t.d(s,e),s})({default:()=>e.default});class c{constructor(e){this.element=e,this.sheetWrapper=this.element.querySelector(".spreadsheet-sheets"),this.tableWrapper=this.element.querySelector(".spreadsheet-table"),this.fileInput=this.element.querySelector(".spreadsheet-file-select"),this.directionInput=this.element.querySelector(".spreadsheet-input-direction"),this.resetInput=this.element.querySelector(".spreadsheet-reset-button"),this.unsetInput=this.element.querySelector(".spreadsheet-unset-button"),this.originalDataInput=this.element.querySelector("input.spreadsheet-input-original"),this.databaseDataInput=this.element.querySelector("input.spreadsheet-input-database"),this.formattedDataInput=this.element.querySelector("input.spreadsheet-input-formatted"),this.dsn=new l(this.databaseDataInput.getAttribute("value")),this.spreadsheet=new o(this.dsn,JSON.parse(this.element.getAttribute("data-spreadsheet"))),this.renderer=new a(this.sheetWrapper,this.tableWrapper),this.selector=new d(this.tableWrapper),this.updateSpreadsheet(!0),this.initializeEvents()}initializeEvents(){this.fileInput.addEventListener("change",(e=>{this.dsn.fileUid=e.currentTarget.value,this.dsn.index=0,this.dsn.range="",this.updateSpreadsheet(!0)})),this.sheetWrapper.addEventListener("changeIndex",(e=>{this.dsn.index=e.detail.index,this.updateSpreadsheet(!0)})),this.resetInput.addEventListener("click",(()=>{this.dsn=new l(this.originalDataInput.getAttribute("value")),this.updateSpreadsheet(!0)})),this.unsetInput.addEventListener("click",(()=>{this.dsn=new l(""),this.sheetWrapper.style.display="none",null!==this.tableWrapper&&(this.tableWrapper.style.display="none"),null!==this.directionInput&&(this.directionInput.disabled=!0),this.updateSpreadsheet()})),null!==this.tableWrapper&&this.tableWrapper.addEventListener("changeSelection",(e=>{"string"==typeof e.detail.start&&e.detail.start===e.detail.end&&e.detail.start.match(/^(?=.*\d)(?=.*[A-Z]).+$/)?this.dsn.range=e.detail.start:this.dsn.range=e.detail.start+":"+e.detail.end,this.updateSpreadsheet()})),null!==this.tableWrapper&&null!==this.directionInput&&this.directionInput.addEventListener("click",(()=>{this.dsn.direction="horizontal"===(this.dsn.direction||"horizontal")?"vertical":"horizontal",this.updateSpreadsheet()}))}updateSpreadsheet(){let e=arguments.length>0&&void 0!==arguments[0]&&arguments[0];this.spreadsheet.dsn=this.dsn,!0===e&&this.renderer.update(this.spreadsheet,this.dsn),this.fileInput.value=this.dsn.fileUid,null!==this.directionInput&&("vertical"===this.dsn.direction?(this.directionInput.querySelector(".direction-row").style.display="none",this.directionInput.querySelector(".direction-column").style.display="block"):(this.directionInput.querySelector(".direction-column").style.display="none",this.directionInput.querySelector(".direction-row").style.display="block"));let t=this.spreadsheet.getSheetName(),s="";void 0!==this.dsn.fileUid&&void 0!==this.dsn.index&&(s+="spreadsheet://"+this.dsn.fileUid+"?index="+this.dsn.index),null!==this.tableWrapper&&this.dsn.range.length>0&&(t+=" - "+this.dsn.range,s+="&range="+this.dsn.range),null!==this.tableWrapper&&null!==this.directionInput&&this.dsn.direction.length>0&&(s+="&direction="+this.dsn.direction),this.formattedDataInput.setAttribute("value",t),this.databaseDataInput.setAttribute("value",s),""!==s&&(this.sheetWrapper.style.display="",null!==this.tableWrapper&&(this.tableWrapper.style.display=""),null!==this.directionInput&&(this.directionInput.disabled=!1))}}h.default.ready().then((()=>{document.querySelectorAll(".spreadsheet-input-wrap").forEach((e=>{new c(e)}))})).catch((()=>{console.error("Failed to load DOM for processing spreadsheet inputs!")}));