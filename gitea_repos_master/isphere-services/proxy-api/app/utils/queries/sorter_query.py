from functools import cached_property
from typing import Callable, Union

from sqlalchemy import asc, desc, nulls_first, nulls_last
from sqlalchemy.orm.attributes import InstrumentedAttribute
from sqlalchemy.sql.elements import UnaryExpression
from sqlalchemy.sql.selectable import Select

from .errors import FieldDoesNotExist, WrongSortingValue


# TYPE ALIASES
OPERATOR = Callable[
    [
        InstrumentedAttribute,
    ],
    UnaryExpression,
]
NULLS_OPERATOR = (
    Callable[
        [
            Union[InstrumentedAttribute, UnaryExpression],
        ],
        UnaryExpression,
    ]
    | None
)
FIELD_OPERATORS = tuple[InstrumentedAttribute, OPERATOR, NULLS_OPERATOR]


class SorterQuery:
    __DIRECTION = {"+": asc, "-": desc}

    __NULLS_OPERATORS = {"NF": nulls_first, "NL": nulls_last}

    def __init__(self, model, relation_models: dict | None = None):
        """
        The model parameter defines the database model for sorting.

        The relation_models parameter determines the correspondence between the relation
        name in the sort list and the database model. It should have
        the structure similar to: {"proxy_usage": "DatabaseModel"}.
        """
        self.__model = model
        self.__relation_models = relation_models if relation_models else {}
        self.__relation_names = list(self.__relation_models.keys())

    @cached_property
    def relation_models(self) -> tuple:
        return tuple(self.__relation_models.values())

    def __get_nulls_operator(self, field_name: str) -> tuple[str, NULLS_OPERATOR]:
        """
        Searches a nulls operator name in field_name. Returns the field_name without the
        nulls operator name and the nulls function or None.
        For example, "deleted$NF" -> ("deleted", nulls_first)
        """
        parts = field_name.split("$")
        len_parts = len(parts)
        if len_parts > 2 or len_parts == 0:
            raise WrongSortingValue("$".join(parts))
        nulls_operator = self.__NULLS_OPERATORS.get(
            parts[1] if len_parts == 2 else None, None
        )
        return parts[0], nulls_operator

    def __get_operator(self, field_name: str) -> tuple[str, OPERATOR]:
        """
        Searches for the sorting function by the first character in field_name. Deletes
        the first character if it is "+" or "-". Return the field_name without "+"/"-"
        character and the sorting function from sqlalchemy.
        For example: "+id" -> ("id", asc)
        """
        if len(field_name) < 1:
            raise WrongSortingValue(field_name)
        _field_name = field_name[1:] if field_name[0] in self.__DIRECTION else field_name
        operator = self.__DIRECTION.get(field_name[0], asc)
        return _field_name, operator

    def __get_model(self, field_parts: list[str]):
        if len(field_parts) == 2:
            if field_parts[0] in self.__relation_names:
                return self.__relation_models[field_parts[0]]
            raise WrongSortingValue(".".join(field_parts))
        return self.__model

    @staticmethod
    def __get_field(model, field_parts: list[str]) -> InstrumentedAttribute:
        field_name = field_parts[1] if len(field_parts) > 1 else field_parts[0]
        if not hasattr(model, field_name):
            raise FieldDoesNotExist(model, field_name)
        return getattr(model, field_name)

    def __parse_field_name(self, field_name: str) -> FIELD_OPERATORS:
        """
        Parses the field_name and returns field, asc/desc, nulls_first/nulls_last/None.
        """
        name, nulls_operator = self.__get_nulls_operator(field_name)
        name, operator = self.__get_operator(name)

        # split name by . "proxy_usage.worker_id" -> ["proxy_usage", "worker_id"]
        field_parts = name.split(".")
        parts_len = len(field_parts)
        if parts_len > 2:
            raise WrongSortingValue(field_name)

        model = self.__get_model(field_parts)
        field = self.__get_field(model, field_parts)
        return field, operator, nulls_operator

    def __get_fields_operators(self, sort_list: list[str]) -> list[FIELD_OPERATORS]:
        return [self.__parse_field_name(item) for item in sort_list]

    @staticmethod
    def __apply_filter(query: Select, fields_operators: list[FIELD_OPERATORS]) -> Select:
        return query.order_by(
            *[
                nulls(operator(field)) if nulls else operator(field)
                for field, operator, nulls in fields_operators
            ]
        )

    def __apply_group_by(
        self, query: Select, fields_operators: list[FIELD_OPERATORS]
    ) -> Select:
        """Add GROUP BY to SQL for relationship model fields"""
        fields = []
        for field, _, _ in fields_operators:
            if isinstance(field.class_(), self.relation_models):
                fields.append(field)
        return query.group_by(*fields)

    def sort(self, query: Select, sort_list: list[str], group_by: bool = False) -> Select:
        """
        Applies sorting to the query. Adds relationship models fields to GROUP BY if
        group_by is True.
        """
        fields_operators = self.__get_fields_operators(sort_list)
        if group_by:
            query = self.__apply_group_by(query, fields_operators)
        return self.__apply_filter(query, fields_operators)
