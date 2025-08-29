from typing import Optional

from worker_classes.keydb.interfaces import (
    FieldXMLDescriptor,
    FieldDescription,
    XMLValueType,
)


class KeyDBBuilderXML:
    def __init__(self, description: FieldXMLDescriptor):
        self._description = description

    def create(self, field: str, value: XMLValueType) -> Optional[FieldDescription]:
        if field not in self.description:
            return None

        fields = {"field": field, "value": value, **self.description[field]}
        if "description" not in fields:
            fields["description"] = fields["title"]

        return FieldDescription(**fields)

    @property
    def description(self) -> FieldXMLDescriptor:
        return self._description
