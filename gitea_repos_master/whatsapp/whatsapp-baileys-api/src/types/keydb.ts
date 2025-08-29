type XMLValueType = string | number;

export declare type FieldDescription = {
  field?: string;
  description?: string;
  title: string;
  type: string;
  value?: XMLValueType;
};

export declare type Records = FieldDescription[][];

export declare type FieldXMLDescriptor = {
  [key: string]: FieldDescription;
};
