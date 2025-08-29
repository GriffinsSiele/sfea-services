from pydash import filter_

from src.adapter.fields import HTMLtoXMLFields, fieldConverter, buildXML
from src.utils.utils import flatten_json


class KeyDBAdapter:
    @staticmethod
    def toKeyDB(data):
        output = []

        for car in data:
            row_data = []
            for [fieldHTML, value] in flatten_json(car).items():
                found_fields = filter_(HTMLtoXMLFields.items(), lambda v: fieldHTML in v[1] or v[1] in fieldHTML)
                if found_fields and len(found_fields):
                    for key in found_fields:
                        converter = fieldConverter[key[0]] if key[0] in fieldConverter else lambda v: v
                        row_data.append(buildXML(key[0], converter(value)))
            output.append(row_data)
        return output
