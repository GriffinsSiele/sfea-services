from sqlalchemy import (
    BigInteger,
    CHAR,
    Date,
    DATE,
    DateTime,
    DATETIME,
    Integer,
    NVARCHAR,
    String,
    Time,
    TIME,
    VARCHAR,
)


# FIELD TYPES
F_DATE = (
    Date,
    DATE,
)
F_DATETIME = (
    DateTime,
    DATETIME,
)
F_TIME = (
    Time,
    TIME,
)
F_INTEGER = (
    BigInteger,
    Integer,
)
F_STRING = (
    CHAR,
    String,
    NVARCHAR,
    VARCHAR,
)
