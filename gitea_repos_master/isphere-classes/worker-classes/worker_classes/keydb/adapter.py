import json
import logging
from typing import List, Any, Optional

from pydash import find, get

from worker_classes.keydb.builder_xml import KeyDBBuilderXML
from worker_classes.keydb.interfaces import (
    ExtraCastFields,
    FieldOrListField,
    Records,
)


class KeyDBAdapter:
    extra_cast_fields: ExtraCastFields = {
        "coordinates": lambda v, r: json.dumps(
            [{"coords": v, "text": get(r, "address", "Location")}]
        )
    }

    def __init__(self, cast_fields: Optional[ExtraCastFields] = None):
        self.extra_cast_fields = {
            **self.extra_cast_fields,
            **(cast_fields if cast_fields else {}),
        }

    def to_key_db(self, data: List[Any], builder_XML: KeyDBBuilderXML) -> Records:
        self.builder_XML = builder_XML

        output = []

        for row in data:
            row_data = []
            for key, value in row.items():
                fields = self._get_fields(key, value, row)
                if not fields:
                    continue
                if isinstance(fields, list):
                    row_data += fields
                elif fields:
                    row_data.append(fields)
            output.append(row_data)

        return output

    def _get_fields(self, key: str, value: Any, row: List[Any]) -> FieldOrListField:
        if not value:
            return None

        skip_field_mask = ["_"]
        skip_field = find(skip_field_mask, lambda f: key.startswith(f))
        if skip_field:
            return None

        reserved_fields = {"list__": self.__cast_list}
        found_reserved = find(list(reserved_fields.keys()), lambda f: key.startswith(f))
        if found_reserved:
            return reserved_fields[found_reserved](key, value)

        if key not in self.builder_XML.description:
            logging.info(
                f'Field "{key}" is not described in XML Builder class. Value: {value}'
            )
            return None

        if key in self.extra_cast_fields:
            return self.builder_XML.create(key, self.extra_cast_fields[key](value, row))

        return self.builder_XML.create(key, value)

    def __cast_list(self, key, value):
        return [self.builder_XML.create(key.replace("list__", ""), v) for v in value]
