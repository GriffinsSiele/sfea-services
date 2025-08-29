from typing import Any, Union


class Constant:
    SERVER_STATUS_OK: str = "ok"
    SERVER_STATUS_PENDING: str = "pending_nn"

    FILE_SIZE_LIMIT: int = 10  # Размер в Mb
    FILE_CONTENT_TYPE_LIMIT: set[str] = {
        "image/jpeg",
        "image/png",
    }

    DEFAULT_SOLUTION_SPECIFICATION: dict[str, Union[bool, int, str]] = {
        "case": True,
        "math": False,
        "phrase": False,
        "numeric": 0,
        "minLength": 2,
        "maxLength": 20,
        "languagePool": "ru",
        "characters": "",
    }

    DEFAULT_AUTO_MODE_CONFIG: dict[str, Any] = {
        "min_acc": 0.0,
        "captcha_ttl": 180.0,
        "provider_priority": {
            "antigate": 1,
            "capmonster": 2,
            "rucaptcha": 3,
        },
    }
    NNETWORKS_PROVIDER: str = "nnetworks"
    AUTO_PROVIDER: str = "auto"

    SEND_REPORT_DISABLED: set[str] = {
        NNETWORKS_PROVIDER,
        "capmonster-local",
    }
    ANTIGATE_MIN_SCORE_VALIDS: set[float] = {0.3, 0.5, 0.7, 0.9}


constant: "Constant" = Constant()
