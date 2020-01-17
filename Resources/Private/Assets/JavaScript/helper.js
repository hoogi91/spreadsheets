export default class Helper {
    static getColHeader(index) {
        // charcode of "a" == 97
        // charcode of "z" == 122
        const base24Str = (index + 1).toString(24); // string base (("z" == 122) - ("a" == 97) - 1)
        let excelStr = "";
        for (let i = 0; i < base24Str.length; i++) {
            let base24Char = base24Str[i];
            let alphabetIndex = ((base24Char * 1).toString() === base24Char) ? base24Char : (base24Char.charCodeAt(0) - 97 + 10);
            // bizarre thing, A==1 in first digit, A==0 in other digits
            if (i === 0) {
                alphabetIndex -= 1;
            }
            excelStr += String.fromCharCode(97 + alphabetIndex);
        }
        return excelStr.toUpperCase();
    }
}
