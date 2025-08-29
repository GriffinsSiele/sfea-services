from isphere_exceptions.worker import InternalWorkerTimeout
from starlette import status as starlette_status
from worker_classes.keydb.response_builder import KeyDBResponseBuilder
from worker_classes.thread.timing import TimeoutHandler

from fastapi import APIRouter, Body
from src.common.utils import now
from src.config import settings
from src.logger import context_logging, request_id_contextvar
from src.logic.worker import SearchDomruManager
from src.schemas import DomruSearchInputSchema, DomruSearchResponseSchema

router: APIRouter = APIRouter()


@router.post(
    "",
    responses=DomruSearchResponseSchema.Config.search_responses_examples,
    status_code=starlette_status.HTTP_200_OK,
    description="Поиск информации о клиенте dom.ru по мобильному номеру телефона",
)
async def search(
    *,
    input_data: DomruSearchInputSchema = Body(
        openapi_examples=DomruSearchInputSchema.model_config["openapi_examples"]  # type: ignore[typeddict-item]
    ),
) -> DomruSearchResponseSchema:
    if (
        input_data.timeout
        and input_data.starttime
        and input_data.timeout + input_data.starttime < now()
    ):
        raise InternalWorkerTimeout()
    request_id_contextvar.set(input_data.phone)
    context_logging.info(f"LPOP {input_data.phone}")
    search_manager = SearchDomruManager(None)
    await search_manager.prepare()
    timeout_handler = TimeoutHandler(timeout=settings.SEARCH_TASK_TIMEOUT)
    result = await timeout_handler.execute(search_manager.search, input_data.phone)
    return DomruSearchResponseSchema(**KeyDBResponseBuilder.ok(result))
