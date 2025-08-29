import logger from '../logger/winston';
import { KeyDBBuilderXML } from './builder_xml';
import { FieldDescription, Records } from 'keydb';

export class KeyDBAdapter {
  private builder_XML: KeyDBBuilderXML;
  public to_key_db(data: any[], builder_XML: KeyDBBuilderXML) {
    this.builder_XML = builder_XML;

    const output: Records = [];

    for (const row of data) {
      const row_data: FieldDescription[] = [];

      for (const [key, value] of Object.entries(row)) {
        const fields = this._get_fields(key, value);
        if (!fields) {
          continue;
        }
        if (Array.isArray(fields)) {
          row_data.push(...fields);
        } else if (fields) {
          row_data.push(fields);
        }
      }

      output.push(row_data);
    }

    return output;
  }

  private _get_fields(key: string, value: any): FieldDescription | null {
    if (!value) {
      return null;
    }

    const skip_field_mask = ['_'];
    const skip_field = skip_field_mask.find((f) => key.startsWith(f));

    if (skip_field) {
      return null;
    }

    const reserved_fields: any = {
      list__: (key: string, value: any[]) =>
        value.map((v) => this.builder_XML.create(key.replace('list__', ''), v))
    };
    const found_reserved = Object.keys(reserved_fields).find((f) =>
      key.startsWith(f)
    );

    if (found_reserved) {
      return reserved_fields[found_reserved](key, value);
    }

    if (!(key in this.builder_XML.description)) {
      logger.warn(
        `Field "${key}" is not described in XML Builder class. Value: ${value}`
      );
      return null;
    }

    return this.builder_XML.create(key, value);
  }
}
