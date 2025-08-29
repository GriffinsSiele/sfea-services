from typing import Any, Callable, Optional

from fastapi import Depends, Query
from pydantic.alias_generators import to_camel

from src.logic.solvers import image_solver
from src.logic.token_config import token_config_manager
from src.schemas import TokenRequestWebsiteDataInput


class CommonQueryInput:
    # макс. возможное время таймаута получения задачи, 600 сек.
    TASK_TIMEOUT_MAX: int = 10 * 60

    @staticmethod
    def provider_query(
        default_value: Any = ..., enum: list[str] = image_solver.providers_list
    ) -> Callable:
        def _provider_query(
            provider: Optional[str] = Query(
                default=default_value,
                description="Наименование провайдера, предоставляющего решение капчи",
                enum=enum,
            ),
        ) -> Optional[str]:
            return provider

        return _provider_query

    @staticmethod
    def source_query(default_value: Any = ...) -> Callable:
        def _source_query(
            source: Optional[str] = Query(
                default=default_value,
                description="""Наименование источника, для которого решается капча.
                    Принимает любую строку, включая текущие варианты источников для нейросетей, например: fns, getcontact, vk.
                    Весь список доступных источников можно получить по /api/sources.""",
            )
        ) -> Optional[str]:
            return source

        return _source_query

    @staticmethod
    def website_query(default_value: Any = ...) -> Callable:
        def _website_query(
            website: Optional[str] = Query(
                default=default_value,
                description="""Наименование веб-страницы сайта, для которой запрашиваются токены.
                    Весь список доступных веб-сайтов можно получить по /api/websites.""",
            )
        ) -> Optional[str]:
            return website

        return _website_query

    @staticmethod
    def timeout_query(default_value: int = 0) -> Callable:
        def _timeout_query(
            timeout: int = Query(
                default=default_value,
                description=f"Время в секундах, в течение которого ожидается получить ответ с решением капчи от внешнего провайдера. Предельное значение: {CommonQueryInput.TASK_TIMEOUT_MAX} сек",
                ge=0,
            ),
        ) -> int:
            return min(timeout, CommonQueryInput.TASK_TIMEOUT_MAX)

        return _timeout_query


class DecoderInputQuery:
    def __init__(
        self,
        provider: str = Depends(CommonQueryInput.provider_query()),
        source: str = Depends(CommonQueryInput.source_query()),
        timeout: int = Depends(CommonQueryInput.timeout_query()),
    ):
        self.provider = provider
        self.source = source
        self.timeout = timeout


class TokenTaskInputQuery:
    def __init__(
        self,
        name: str = Query(
            description="Идентификатор сайта, для которого запрашивается токен."
        ),
        url: Optional[str] = Query(
            description="URL-адрес веб-страницы, на которой загружается капча.",
            default=None,
        ),
        token_type: Optional[str] = Query(
            description="Тип токена.",
            enum=token_config_manager.token_types,
            default=None,
        ),
        provider: Optional[str] = Depends(
            CommonQueryInput.provider_query(
                default_value=None, enum=image_solver.external_providers_list
            )
        ),
        timeout: int = Depends(CommonQueryInput.timeout_query()),
        website_config: Optional[TokenRequestWebsiteDataInput] = None,
    ):
        _website_config = (
            website_config.model_dump(exclude_unset=True)
            if website_config is not None
            else {}
        )
        self.url = url
        self.name = name
        self.timeout = timeout
        self.website_config = {
            **_website_config,
            "token_type": token_type,
            "provider": provider,
        }


class TaskStatisticInputQuery:
    def __init__(
        self,
        provider: Optional[str] = Depends(CommonQueryInput.provider_query(None)),
        period_days: Optional[int] = Query(
            default=None,
            description="Число дней, за которое производится расчет статистики, включая текущую дату.",
        ),
        period_hours: Optional[float] = Query(
            default=None,
            description="Число часов, за которое производится расчет статистики, включая текущую дату.",
        ),
    ):
        self.provider = provider
        if period_days is None and period_hours is None:
            self.period_days = 7
            self.period_hours = 0.0
        else:
            self.period_days = period_days or 0
            self.period_hours = period_hours or 0.0
