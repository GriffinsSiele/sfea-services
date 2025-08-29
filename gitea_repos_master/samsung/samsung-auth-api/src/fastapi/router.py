from typing import Type

from mongo_client.client import MongoSessions
from pydantic import ValidationError
from worker_classes.keydb.response_builder import KeyDBResponseBuilder
from worker_classes.logic.search_manager import SearchManager
from worker_classes.thread.timing import timing
from worker_classes.utils import short

from fastapi import APIRouter, BackgroundTasks, Body, Request, Response
from fastapi import status as code
from src.fastapi.schemas import (
    SamsungSearchDataAuth,
    SamsungSearchDataName,
    SamsungSearchDataPerson,
    SamsungSearchResponse,
    SamsungSearchResponseName,
    SamsungSearchResponseWithEmails,
    SamsungStatusResponse,
)
from src.fastapi.server import handler_name_contextvar
from src.logger.context_logger import logging
from src.logger.logger_adapter import request_id_contextvar
from src.logic.samsung.exception_handler import exception_wrapper
from src.logic.samsung.search_manager_auth import SamsungSearchManagerAuth
from src.logic.samsung.search_manager_name import SamsungSearchManagerName
from src.logic.samsung.search_manager_person import SamsungSearchManagerPerson

samsung_router = APIRouter(tags=["samsung"])


@samsung_router.get("/status", response_model=SamsungStatusResponse)
async def status() -> dict:
    """Проверка состояния сервиса."""
    return {"status": "ok"}


@samsung_router.post(
    "/search/auth",
    status_code=code.HTTP_200_OK,
    responses=SamsungSearchResponse.model_config["search_responses_examples"],
)
async def search_user_auth(
    request: Request,
    response: Response,
    background_tasks: BackgroundTasks,
    search_data: SamsungSearchDataAuth = Body(
        openapi_examples=SamsungSearchDataAuth.model_config["openapi_examples"]
    ),
) -> SamsungSearchResponse | None:
    """Поиск учетной записи пользователя по e-mail.

    - **email**: e-mail учетной записи пользователя, которую ищем
    - **starttime**: время поступления задачи на поиск
    - **timeout**: максимальная длительность выполнения задачи (от времени поступления задачи на поиск)
    """
    storage: MongoSessions = request.app.state.mongo_storage_auth
    handler_name_contextvar.set("auth")
    return await timing("Total processing task time")(_search_user)(
        request,
        response,
        background_tasks,
        SamsungSearchManagerAuth,
        storage,
        search_data,
    )


@samsung_router.post(
    "/search/person",
    status_code=code.HTTP_200_OK,
    responses=SamsungSearchResponseWithEmails.model_config["search_responses_examples"],
)
async def search_user_person(
    request: Request,
    response: Response,
    background_tasks: BackgroundTasks,
    search_data: SamsungSearchDataPerson = Body(
        openapi_examples=SamsungSearchDataPerson.model_config["openapi_examples"]
    ),
) -> SamsungSearchResponseWithEmails | None:
    """Поиск учетной записи пользователя по имени, фамилии и дате рождения.

    - **first_name**: имя пользователя
    - **last_name**: фамилия пользователя
    - **birthdate**: дата рождения в формате день.месяц.год (Например "10.01.1990")
    - **starttime**: время поступления задачи на поиск
    - **timeout**: максимальная длительность выполнения задачи (от времени поступления задачи на поиск)
    """
    storage: MongoSessions = request.app.state.mongo_storage_name_person
    handler_name_contextvar.set("person")
    return await timing("Total processing task time")(_search_user)(
        request,
        response,
        background_tasks,
        SamsungSearchManagerPerson,
        storage,
        search_data,
    )


@samsung_router.post(
    "/search/name",
    status_code=code.HTTP_200_OK,
    responses=SamsungSearchResponseName.model_config["search_responses_examples"],
)
async def search_user_name(
    request: Request,
    response: Response,
    background_tasks: BackgroundTasks,
    search_data: SamsungSearchDataName = Body(
        openapi_examples=SamsungSearchDataName.model_config["openapi_examples"]
    ),
) -> SamsungSearchResponseName | None:
    """Поиск учетной записи пользователя по телефону или e-mail, имени, фамилии и дате рождения.

    - **phone**: телефон учетной записи пользователя
    - **email**: e-mail учетной записи пользователя
    - **first_name**: имя пользователя
    - **last_name**: фамилия пользователя
    - **birthdate**: дата рождения в формате день.месяц.год (Например "10.01.1990")
    - **starttime**: время поступления задачи на поиск
    - **timeout**: максимальная длительность выполнения задачи (от времени поступления задачи на поиск)
    """
    storage: MongoSessions = request.app.state.mongo_storage_name_person
    handler_name_contextvar.set("name")
    return await timing("Total processing task time")(_search_user)(
        request,
        response,
        background_tasks,
        SamsungSearchManagerName,
        storage,
        search_data,
    )


async def _search_user(
    request: Request,
    response: Response,
    background_tasks: BackgroundTasks,
    search_strategy: Type[SearchManager],
    storage: MongoSessions,
    search_data: SamsungSearchDataPerson,
) -> SamsungSearchResponse | SamsungSearchResponseWithEmails | None:
    """Выполняет поиск информации о пользователе в соответствии с переданной стратегией.
    Код вынесен в отдельную функцию с целью применения декоратора timing.

    :param request: Запрос.
    :param response: Ответ.
    :param background_tasks: Фоновые задачи.
    :param search_strategy: Стратегия поиска (auth, person или name).
    :param storage: Подключение к MongoDB, которая хранит сессии.
    :param search_data: Входные данные для поиска.
    :return: Ответ в формате SamsungSearchResponse.
    """
    if search_data.payload:
        request_id_contextvar.set(search_data)
    logging.info(f"LPOP {search_data}")

    manager = search_strategy(session_storage=storage, logger=logging)
    search_result: SamsungSearchResponse = await _prepare_search_manager(manager)
    if not search_result:
        search_result = await manager.search(search_data)

    if not search_result:
        logging.error("Search result is empty")
        search_result = SamsungSearchResponse(
            **KeyDBResponseBuilder.error("Неизвестная ошибка", 599)
        )

    if search_result.code == code.HTTP_204_NO_CONTENT:
        response.status_code = code.HTTP_204_NO_CONTENT
        logging.info(f"KeyDB response: [{search_result.code}] ok")
        return None

    # Потеряно соединение с MongoDB, закрываем приложение
    # Использованы background_tasks, что бы отправить ответ перед закрытием
    if (
        search_result.code == 522
        and search_result.message == "При выполнении операции MongoDB возникла ошибка"
    ):
        logging.warning("Forced application crash")
        background_tasks.add_task(request.app.stop)

    response.status_code = search_result.code
    try:
        SamsungSearchResponse.model_validate(search_result.model_dump(), strict=True)
        logging.info(f"KeyDB response: [{search_result.code}] {search_result.message}")
    except ValidationError as e:
        logging.error(short(e))
    return search_result


@exception_wrapper
async def _prepare_search_manager(manager: SearchManager) -> SamsungSearchResponse | None:
    return await manager.prepare()
