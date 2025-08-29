"""
Provides error classes for query utils
"""


def get_operator_name(operator: str):
    return operator.replace("_", "").upper()


class ConnectorDoesNotExist(Exception):
    def __init__(self, connector: str):
        super().__init__(f"Connector with name '{connector}' is not supported!")


class FieldValueError(Exception):
    def __init__(self, field: str, value):
        super().__init__(f"The field '{field}' contains unsupported value '{value}'!")


class FieldDoesNotExist(Exception):
    def __init__(self, model, field: str):
        super().__init__(
            f"The relation '{model.__name__}' does not have a field '{field}'!"
        )


class ManyFieldOperationError(Exception):
    def __init__(self, field_name: str, operations: list):
        super().__init__(
            f"The field '{field_name}' contains many operations '{operations}'!"
        )


class RelationshipOperatorError(Exception):
    def __init__(self, field_name: str, operator_name: str):
        operator = get_operator_name(operator_name)
        super().__init__(
            f"The operator '{operator}' can only be used for the relationship model! "
            f"Check the field '{field_name}${operator}' in filter."
        )


class UnregisteredFieldOperationError(Exception):
    def __init__(self, field_name: str, operations: list):
        super().__init__(
            f"The field '{field_name}' contains an unregistered operation '{operations}'!"
        )


class UnsupportedFieldOperationError(Exception):
    def __init__(self, field_name: str, operator: str):
        super().__init__(
            f"The field '{field_name}' contains an unsupported field operator "
            f"'{get_operator_name(operator)}'!"
        )


class WrongSortingValue(Exception):
    def __init__(self, value: str):
        super().__init__(f"The value '{value}' in the sort list is incorrect!")


QUERY_UTILS_EXCEPTIONS = (
    ConnectorDoesNotExist,
    FieldValueError,
    FieldDoesNotExist,
    ManyFieldOperationError,
    RelationshipOperatorError,
    UnregisteredFieldOperationError,
    UnsupportedFieldOperationError,
    WrongSortingValue,
)
