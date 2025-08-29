from copy import deepcopy

from pydash import set_

found = {
    200: {
        "description": "Normal",
        "content": {
            "application/json": {
                "example": {
                    "status": "ok",
                    "code": 200,
                    "message": "ok",
                    "records": [
                        {
                            "result": "Найден",
                            "result_code": "FOUND",
                            # "emails": [],
                        }
                    ],
                    "timestamp": 1705084465,
                }
            }
        },
    }
}

found_with_email = deepcopy(found)
found_with_email = set_(
    found_with_email,
    "[200].content.application/json.example.records.0.emails",
    ["email1", "email2", "..."],
)

found_name = deepcopy(found)
found_name = set_(
    found_name,
    "[200].content.application/json.example.records.0.result",
    "Найден, телефон/e-mail соответствует фамилии и имени",
)
found_name = set_(
    found_name,
    "[200].content.application/json.example.records.0.result_code",
    "MATCHED",
)

not_found = {
    204: {
        "description": "No Content",
        "content": {"application/json": {"example": "not have a body"}},
    },
}

errors = {
    500: {
        "description": "InternalWorkerError",
        "content": {
            "application/json": {
                "example": {
                    "status": "error",
                    "code": 500,
                    "message": "Внутренняя ошибка обработчика",
                    "records": [],
                    "timestamp": 1705084465,
                }
            }
        },
    },
    502: {
        "description": "ProxyServerConnection",
        "content": {
            "application/json": {
                "example": {
                    "status": "error",
                    "code": 502,
                    "message": "Ошибка подключения к сервису proxy",
                    "records": [],
                    "timestamp": 1705084465,
                }
            }
        },
    },
    504: {
        "description": "TimeoutError",
        "content": {
            "application/json": {
                "example": {
                    "status": "error",
                    "code": 504,
                    "message": "Превышен таймаут запроса к источнику",
                    "records": [],
                    "timestamp": 1705084465,
                }
            }
        },
    },
    505: {
        "description": "SourceIncorrectDataDetected",
        "content": {
            "application/json": {
                "example": {
                    "status": "error",
                    "code": 505,
                    "message": "Источник не может выполнить запрос по указанным данным",
                    "records": [],
                    "timestamp": 1705084465,
                }
            }
        },
    },
    510: {
        "description": "InternalWorkerTimeout",
        "content": {
            "application/json": {
                "example": {
                    "status": "error",
                    "code": 510,
                    "message": "Превышен таймаут ответа обработчика",
                    "records": [],
                    "timestamp": 1705084465,
                }
            }
        },
    },
    521: {
        "description": "ProxyTemporaryUnavailable",
        "content": {
            "application/json": {
                "example": {
                    "status": "error",
                    "code": 521,
                    "message": "Proxy временно недоступен",
                    "records": [],
                    "timestamp": 1705084465,
                }
            }
        },
    },
    522: {
        "description": "MongoConnection",
        "content": {
            "application/json": {
                "example": {
                    "status": "error",
                    "code": 521,
                    "message": "Ошибка подключения к MongoDB",
                    "records": [],
                    "timestamp": 1705084465,
                }
            }
        },
    },
    526: {
        "description": "SessionError",
        "content": {
            "application/json": {
                "example": {
                    "status": "error",
                    "code": 526,
                    "message": "'Превышено количество запросов для данной сессии', 'Истекло время жизни сессии' или 'Ошибка получения сессии'",
                    "records": [],
                    "timestamp": 1705084465,
                }
            }
        },
    },
    530: {
        "description": "SourceError",
        "content": {
            "application/json": {
                "example": {
                    "status": "error",
                    "code": 530,
                    "message": "Ошибка со стороны источника",
                    "records": [],
                    "timestamp": 1705084465,
                }
            }
        },
    },
    599: {
        "description": "SourceParseError",
        "content": {
            "application/json": {
                "example": {
                    "status": "error",
                    "code": 599,
                    "message": "Неизвестная ошибка",
                    "records": [],
                    "timestamp": 1705084465,
                }
            }
        },
    },
}

search_responses_examples = {**found, **not_found, **errors}
search_responses_examples_with_email = {**found_with_email, **not_found, **errors}
search_responses_examples_name = {**found_name, **not_found, **errors}
