import { FieldDescription, FieldXMLDescriptor } from 'keydb';

export class KeyDBBuilderXML {
  public readonly description: FieldXMLDescriptor;
  constructor(description: FieldXMLDescriptor) {
    this.description = description;
  }
  public create(field: string, value: any) {
    if (!(field in this.description)) {
      return null;
    }

    const fields: FieldDescription = {
      field,
      value,
      ...this.description[field]
    };
    if (!('description' in fields)) {
      fields.description = fields.title;
    }
    return fields;
  }
}
