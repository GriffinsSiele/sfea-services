from typing import Any

from fastapi import APIRouter, Body, Depends, Query
from sqlalchemy.ext.asyncio import AsyncSession
from starlette import status

from src.common import deps, enums
from src.logic.solvers import image_solver, token_solver
from src.schemas import ProviderBalance

router: APIRouter = APIRouter()


@router.get(
    "/balance",
    status_code=status.HTTP_200_OK,
    response_model=ProviderBalance,
    description="Получение баланса учетной записи заданного провайдера",
)
async def get_provider_balance(
    provider: str = Query(
        description="Наименование провайдера, предоставляющего решение капчи",
        enum=image_solver.external_providers_list,
    ),
) -> "ProviderBalance":
    result = await image_solver.get_provider_balance(provider=provider)
    return ProviderBalance(**result)


@router.post(
    "/callback",
    status_code=status.HTTP_200_OK,
    response_model=None,
)
async def callback(
    db: AsyncSession = Depends(deps.get_session),
    *,
    task_id: int = Query(
        description="Идентификатор задачи решения капчи.",
    ),
    task_type: enums.TaskTypeEnum = Query(
        description="Тип задачи.",
    ),
    input_data: Any = Body(...),
) -> None:
    if task_type == enums.TaskTypeEnum.image:
        await image_solver.process_callback(db=db, task_id=task_id, data=input_data)
    elif task_type == enums.TaskTypeEnum.token:
        await token_solver.process_callback(db=db, task_id=task_id, data=input_data)
