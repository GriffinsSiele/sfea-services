from typing import Callable, Dict, Optional, TypedDict, Union, List

from typing_extensions import NotRequired

XMLValueType = str | int | float


class FieldDescription(TypedDict):
    field: NotRequired[str]
    description: NotRequired[str]
    title: str
    type: str
    value: NotRequired[XMLValueType]


FieldOrListField = Union[Optional[FieldDescription], List[Optional[FieldDescription]]]

Records = List[List[FieldDescription]]

FieldXMLDescriptor = Dict[str, FieldDescription]

ExtraCastFields = Optional[Dict[str, Callable]]


class KeyDBResponse(TypedDict):
    status: str
    code: int
    message: str
    records: Records
    timestamp: int
