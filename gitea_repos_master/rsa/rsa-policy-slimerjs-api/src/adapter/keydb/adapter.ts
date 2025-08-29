import { flatObject } from '../../utils';
import { buildXML, fieldConverter, HTMLtoXMLFields } from './fields';

export default class KeyDBAdapter {
  public static toKeyDB(data: any[]) {
    const output = [];
    for (const car of data) {
      const rowData = [];
      for (const [fieldHTML, value] of Object.entries(flatObject(car))) {
        const foundFields = Object.entries(HTMLtoXMLFields).filter(
          // eslint-disable-next-line @typescript-eslint/no-unused-vars
          ([_, v]) => fieldHTML.includes(v) || v.includes(fieldHTML)
        );
        if (foundFields && foundFields.length) {
          //eslint-disable-next-line @typescript-eslint/no-unused-vars
          for (const [key, _] of foundFields) {
            const converter =
              key in fieldConverter ? fieldConverter[key] : (v: any) => v;
            rowData.push(buildXML(key, converter(value)));
          }
        }
      }
      output.push(rowData);
    }
    return output;
  }
}
