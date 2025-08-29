from enum import Enum

from sqlalchemy.dialects import postgresql


class BaseEnum(Enum):
    @classmethod
    def has_value(cls, value) -> bool:
        return value in cls._value2member_map_

    @classmethod
    def as_list(cls) -> list[str]:
        return [x._value_ for x in cls]


class ExternalProviderEnum(str, BaseEnum):
    antigate = "antigate"
    capmonster = "capmonster"
    capmonster_local = "capmonster-local"
    rucaptcha = "rucaptcha"


class TaskTypeEnum(str, BaseEnum):
    image = "image"
    token = "token"


class TaskStatusEnum(str, BaseEnum):
    Idle = "Idle"
    InUse = "InUse"
    Success = "Success"
    Fail = "Fail"


class LanguagePoolEnum(str, Enum):
    ru = "ru"
    en = "en"


task_status_enum_postgresql = postgresql.ENUM(
    *TaskStatusEnum.as_list(),
    name="task_status_enum",
    create_type=False,
)
