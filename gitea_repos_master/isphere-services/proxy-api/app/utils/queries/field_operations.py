from sqlalchemy import distinct
from sqlalchemy.dialects.postgresql import array_agg
from sqlalchemy.orm.attributes import InstrumentedAttribute
from sqlalchemy.sql.elements import BinaryExpression


class FieldOperations:
    @staticmethod
    def default(field: InstrumentedAttribute, value) -> BinaryExpression:
        return field.in_(value) if isinstance(value, list) else field == value

    @staticmethod
    def and_(field: InstrumentedAttribute, values: list) -> BinaryExpression:
        """Returns a condition using postgresql array_agg and the @> array operator"""
        return array_agg(distinct(field)).contains(values)

    @staticmethod
    def gte(field: InstrumentedAttribute, value) -> BinaryExpression:
        return field >= value

    @staticmethod
    def gt(field: InstrumentedAttribute, value) -> BinaryExpression:
        return field > value

    @staticmethod
    def lte(field: InstrumentedAttribute, value) -> BinaryExpression:
        return field <= value

    @staticmethod
    def lt(field: InstrumentedAttribute, value) -> BinaryExpression:
        return field < value


FIELD_OPERATORS = {
    "AND": FieldOperations.and_,
    "GTE": FieldOperations.gte,
    "GT": FieldOperations.gt,
    "LTE": FieldOperations.lte,
    "LT": FieldOperations.lt,
    "DEFAULT": FieldOperations.default,
}


# The list of operators for relationship models
HAVING_OPERATORS = (FieldOperations.and_,)
# SUPPORTED OPERATORS FOR STRING FIELD
STRING_OPERATORS = (FieldOperations.default,)
# SUPPORTED OPERATORS FOR LIST VALUE
LIST_OPERATORS = (
    FieldOperations.and_,
    FieldOperations.default,
)
