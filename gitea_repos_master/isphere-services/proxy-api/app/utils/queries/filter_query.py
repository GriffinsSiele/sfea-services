import datetime
from functools import cached_property
from typing import Any, Callable, Union

from sqlalchemy import and_, Enum, or_, String
from sqlalchemy.orm.attributes import InstrumentedAttribute
from sqlalchemy.sql.elements import BinaryExpression, BooleanClauseList
from sqlalchemy.sql.selectable import Select

from .errors import (
    ConnectorDoesNotExist,
    FieldDoesNotExist,
    FieldValueError,
    ManyFieldOperationError,
    RelationshipOperatorError,
    UnregisteredFieldOperationError,
    UnsupportedFieldOperationError,
)
from .field_operations import (
    FIELD_OPERATORS,
    HAVING_OPERATORS,
    STRING_OPERATORS,
    LIST_OPERATORS,
)
from .field_types import F_DATE, F_DATETIME, F_INTEGER, F_STRING, F_TIME


# TYPE ALIASES
Operator = Callable[
    [InstrumentedAttribute, Any], Union[BinaryExpression, BooleanClauseList]
]
Condition = Union[BinaryExpression, BooleanClauseList]


class FilterQuery:
    """
    Perform filtering by using and_, or_ function from sqlalchemy.
    """

    __CONNECTORS = {"$AND": and_, "$OR": or_}

    # DEFAULT VALUES FOR RELATIONSHIPS
    __DEFAULT_AVAILABLE = True
    __DEFAULT_JOIN = {"isouter": False}

    def __init__(
        self,
        model,
        relation_models: dict | None = None,
        root_connector: str = "$AND",
    ):
        """
        The model parameter defines the database model for filtering.

        The relation_models parameter determines the correspondence between the relation
        name in the filtering dictionary and the database model. It should have
        the structure similar to:
        {
            "proxy_tag": {
                "model": app.models.ProxyTag,
                "available": False,
                "join": {"isouter": True}
            }
            "tags": {"model": app.models.Tag}
        }.
        The relation name in dictionary stores dictionary with extra data:
            - "model" (REQUIRED): database model class
            - "available" (OPTIONAL): availability of the relationship for user filtering,
                the default value is True
            - "join" (OPTIONAL): additional parameters for the join function from
                the sqlalchemy library, the default value is {"isouter": False}
        If there is a model with deep relationships, for example:
            Company.employee.human.education.
        There are the Company entity, the Employee entity, the Human entity, the Education
        entity. In this case you must specify in the relation_models parameter ALL the
        models involved in such a relationship, for example:
        {
            "employees": {"model": Employee},
            "human": {"model": Human},
            "human_educations": {"model": Education}
        }
        The above models will be added to SQL as JOIN operations.

        The root_connector parameter defines the default selection condition for the
        root in filtering dictionary (passed to the filtering method) if it does not
        have a connection condition in the root.
        """
        self.__model = model
        self.__relation_models: dict = relation_models if relation_models else {}
        self.__validate_relation_models()
        names, models = self.__get_available_relations()
        self.__available_relations: list[str] = names
        self.__available_relation_models: list = models

        if not self.__is_connector(root_connector):
            raise ConnectorDoesNotExist(root_connector)
        self.__root_connector: str = root_connector

    @cached_property
    def __connector_names(self):
        return list(self.__CONNECTORS.keys())

    def __validate_relation_models(self):
        # empty relation_models dictionary is a valid dictionary
        if len(self.__relation_models.keys()) == 0:
            return

        for key, value in self.__relation_models.items():
            if not isinstance(value, dict):
                raise ValueError(
                    f"relation_models({key}): relation name value must be a dictionary"
                )
            if value.get("model", None) is None:
                raise ValueError(
                    f"relation_models({key}): model key value cannot be undefined"
                )

    def __get_available_relations(self) -> tuple[list[str], list]:
        """
        Returns a list of available relationships names and relationships models from
        self.__relation_models. The available relationships names are available for
        filtering.
        """
        names = []
        models = []
        for key, value in self.__relation_models.items():
            if value.get("available", self.__DEFAULT_AVAILABLE):
                names.append(key)
                models.append(value["model"])
        return names, models

    def __is_connector(self, name: str) -> bool:
        return name in self.__connector_names

    def __get_connector_func(self, connector_name: str) -> Callable:
        if not self.__is_connector(connector_name):
            raise ConnectorDoesNotExist(connector_name)
        return self.__CONNECTORS[connector_name]

    def __get_connector_from_dict(self, filter_dict: dict) -> Callable:
        """
        Returns the found connector function from the dictionary.
        For example (source_data -> return value):
            - {"$AND": {"id": 1}} -> sqlalchemy.and_
            - {"$OR": {"id": 1}} -> sqlalchemy.or_
        """
        keys = list(filter_dict.keys())
        connector_name = keys[0] if len(keys) else self.__root_connector
        return self.__get_connector_func(connector_name)

    def __get_prepared_dict(self, filter_dict: dict) -> dict:
        """
        Checks the root dictionary connector exists, adds if it doesn't.
        Converts a dictionary of the type
        {
            "country": "ru",
            "protocol": ["http", "https"]
        }
        into a dictionary of the type
        {
            "$AND": {
                "country": "ru",
                "protocol": ["http", "https"]
            }
        }
        The connector name depends on self.__root_connector, by default "$AND".
        """
        keys = list(filter_dict.keys())
        len_keys = len(keys)
        new_dict = filter_dict
        # add connector string to filter_dict if it does not exist
        if len_keys > 1 or (len_keys == 1 and not self.__is_connector(keys[0])):
            new_dict = {self.__root_connector: filter_dict}
        return new_dict

    @staticmethod
    def __is_date_time_field(field: InstrumentedAttribute) -> bool:
        return (
            isinstance(field.type, F_DATETIME)
            or isinstance(field.type, F_DATE)
            or isinstance(field.type, F_TIME)
        )

    @staticmethod
    def __check_value_type(field: InstrumentedAttribute, value, exp_type: type):
        value_type = type(value)
        if value_type is list:
            if not all(map(lambda x: isinstance(x, exp_type), value)):
                raise FieldValueError(field.key, value)
        elif value_type is not exp_type:
            raise FieldValueError(field.key, value)

    def __validate_field_value(self, field: InstrumentedAttribute, value):
        """
        Checks that the field type and the value type are compatible. Checks each item
        type if the value is a list.
        """
        # The Enum validator is before the String validators
        # because the Enum type is the String type
        if isinstance(field.type, Enum):
            # checks that the value is in the enum
            enums = field.type.enums
            if isinstance(value, list):
                if not all(map(lambda x: x in enums, value)):
                    raise FieldValueError(field.key, value)
            elif value not in enums:
                raise FieldValueError(field.key, value)
        elif isinstance(field.type, F_INTEGER):
            self.__check_value_type(field, value, int)
        elif isinstance(field.type, F_STRING):
            self.__check_value_type(field, value, str)
        elif self.__is_date_time_field(field) and not isinstance(value, str):
            raise FieldValueError(field.key, value)

    def __validate_field_operator(
        self, model, field: InstrumentedAttribute, value, operator: Operator
    ):
        """Checks that the field operator and the model field are compatible"""
        if operator in HAVING_OPERATORS and model not in self.__available_relation_models:
            raise RelationshipOperatorError(field.key, operator.__name__)

        if isinstance(value, list) and operator not in LIST_OPERATORS:
            raise UnsupportedFieldOperationError(field.key, operator.__name__)
        if isinstance(field.type, String):
            if isinstance(value, str) and operator not in STRING_OPERATORS:
                raise UnsupportedFieldOperationError(field.key, operator.__name__)

    @staticmethod
    def __get_date_time_condition(
        field: InstrumentedAttribute, value: str, operator: Operator
    ) -> Condition:
        try:
            if isinstance(field.type, F_DATE):
                return operator(field, datetime.date.fromisoformat(value))
            if isinstance(field.type, F_DATETIME):
                return operator(field, datetime.datetime.fromisoformat(value))
            if isinstance(field.type, F_TIME):
                return operator(field, datetime.time.fromisoformat(value))
        finally:
            raise FieldValueError(field.key, value)

    @staticmethod
    def __parse_field_name(field_name: str) -> tuple[str, Operator]:
        """
        Splits the string with the $ character and searches the field operator function.
        Returns the field name and the field operator function. For example:
        "id$GTE" -> ("id", .field_operations.FIELD_OPERATORS["GTE"])
        """
        result = field_name.split("$")
        if len(result) > 2:
            raise ManyFieldOperationError(result[0], result[1:])
        operator_name = result[1] if len(result) == 2 else "DEFAULT"
        operator: Callable = FIELD_OPERATORS.get(operator_name)
        if not operator:
            raise UnregisteredFieldOperationError(result[0], result[1:])
        return result[0], operator

    def __get_attribute_condition(
        self, model, field_name: str, filter_dict: dict
    ) -> tuple[BinaryExpression | None, BinaryExpression | None]:
        """
        Validates field_name, operator, field type and value type.
        Returns a where condition or a having condition: [where, None] or [None, having].
        """
        _field_name, operator = self.__parse_field_name(field_name)
        if not hasattr(model, _field_name):
            raise FieldDoesNotExist(model, _field_name)

        filter_value = filter_dict[field_name]
        field: InstrumentedAttribute = getattr(model, _field_name)
        self.__validate_field_value(field, filter_value)
        self.__validate_field_operator(model, field, filter_value, operator)

        if self.__is_date_time_field(field):
            # special condition for the date//datetime//time field
            condition = self.__get_date_time_condition(field, filter_value, operator)
        else:
            condition = operator(field, filter_value)
        return (None, condition) if operator in HAVING_OPERATORS else (condition, None)

    @staticmethod
    def __call_filter(
        where_clauses: list, having_clauses: list, func: Callable, func_arguments: list
    ):
        """
        Calls the __recursive_filter or __get_attribute_condition. Add conditions to the
        where_clauses list and the having_clauses list.
        """
        where, having = func(*func_arguments)
        if where is not None:
            where_clauses.append(where)
        if having is not None:
            having_clauses.append(having)
        return where_clauses, having_clauses

    def __recursive_filter(
        self, model, filter_dict: dict, connector_func: Callable
    ) -> tuple[Condition, Condition]:
        """
        Recursively collects where clauses and having clauses from filtering dict in the
        two lists and returns tuple of Condition using the connector function for lists.
        """
        where_clauses = []
        having_clauses = []
        for key in filter_dict.keys():
            # check connector
            if self.__is_connector(key):
                where_clauses, having_clauses = self.__call_filter(
                    where_clauses,
                    having_clauses,
                    self.__recursive_filter,
                    [model, filter_dict[key], self.__get_connector_func(key)],
                )
            # check relation name
            elif key in self.__available_relations:
                relation_filter = self.__get_prepared_dict(filter_dict[key])
                where_clauses, having_clauses = self.__call_filter(
                    where_clauses,
                    having_clauses,
                    self.__recursive_filter,
                    [
                        self.__relation_models[key]["model"],
                        relation_filter,
                        self.__get_connector_from_dict(relation_filter),
                    ],
                )
            # get condition for field
            else:
                where_clauses, having_clauses = self.__call_filter(
                    where_clauses,
                    having_clauses,
                    self.__get_attribute_condition,
                    [model, key, filter_dict],
                )
        return connector_func(*where_clauses), connector_func(*having_clauses)

    def __join(self, query: Select) -> Select:
        """
        Applies the join function for each relationship in the self.__relation_models
        dictionary to the query.
        """
        for relation, value in self.__relation_models.items():
            query = query.join(value["model"], **value.get("join", self.__DEFAULT_JOIN))
        return query

    def filter(self, query: Select, filter_dict: dict) -> Select:
        """
        Applies filtering to the query.
        NOTE:
            - this function add JOIN to SQL, but it will not add DISTINCT to SQL;
            - this function can add HAVING to SQL, but it will not add GROUP BY to SQL.
        """
        query = self.__join(query)
        new_dict = self.__get_prepared_dict(filter_dict)
        connector = self.__get_connector_from_dict(new_dict)
        where, having = self.__recursive_filter(self.__model, new_dict, connector)
        return query.where(where).having(having)
