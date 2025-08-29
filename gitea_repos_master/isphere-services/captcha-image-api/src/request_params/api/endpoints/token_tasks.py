from typing import Optional

from fastapi import APIRouter, Depends, Query
from sqlalchemy.ext.asyncio import AsyncSession
from starlette import status

from src.common import deps, enums
from src.db.models import TokenTaskModel
from src.logic.solvers import token_solver
from src.logic.websites import website_service
from src.request_params.input_queries import (
    CommonQueryInput,
    TaskStatisticInputQuery,
    TokenTaskInputQuery,
)
from src.schemas import TaskStatisticInfo, TaskStatusInfo, TokenTaskInfo

router: APIRouter = APIRouter()


@router.post(
    "",
    status_code=status.HTTP_200_OK,
    response_model=TokenTaskInfo,
    description="Получение токена для решения капч.",
)
async def get_token(
    db: AsyncSession = Depends(deps.get_session),
    *,
    input_data: TokenTaskInputQuery = Depends(TokenTaskInputQuery),
):
    token = await token_solver.process_captcha_task(
        db=db,
        website_data={
            "name": input_data.name,
            "url": input_data.url,
            "website_config": input_data.website_config,
        },
        timeout=input_data.timeout,
    )
    return TokenTaskInfo.model_validate(token)


@router.get(
    "/result",
    status_code=status.HTTP_200_OK,
    response_model=TokenTaskInfo,
    description="Получение результата решения капчи с помощью токена.",
)
async def get_task_result(
    db: AsyncSession = Depends(deps.get_session),
    *,
    task: TokenTaskModel = Depends(deps.get_captcha_token_task),
) -> TokenTaskInfo:
    solved_task = await token_solver.retrieve_task_solution(db=db, task=task)
    if solved_task.status == enums.TaskStatusEnum.Idle:
        solved_task = await token_solver.service.repository.update(
            db=db, db_obj=solved_task, obj_in={"status": enums.TaskStatusEnum.InUse}
        )
    return TokenTaskInfo.model_validate(solved_task)


@router.get(
    "/statistic",
    status_code=status.HTTP_200_OK,
    response_model=TaskStatisticInfo,
    description="""
    Получение статистики результатов решения капч за период по указанному источнику.
    При отсутствии параметров period_days и period_hours расчет статистики производится за 7 последних календарных дней.
    """,
)
async def get_token_tasks_statistic(
    db: AsyncSession = Depends(deps.get_session),
    *,
    input_data: TaskStatisticInputQuery = Depends(TaskStatisticInputQuery),
    website_name: Optional[str] = Depends(CommonQueryInput.website_query(None)),
) -> TaskStatisticInfo:
    website = (
        await website_service.get_website(
            db=db,
            filter_kwargs={website_service.repository.model.name.name: website_name},
        )
        if website_name is not None
        else None
    )

    result = await token_solver.service.repository.get_statistic(
        db=db,
        parent=website,
        provider=input_data.provider,
        period_days=input_data.period_days,
        period_hours=input_data.period_hours,
    )

    return result


@router.put(
    "/update",
    status_code=status.HTTP_200_OK,
    response_model=TaskStatusInfo,
    description="Обновление статуса решения задачи и отправка отчета.",
)
async def update_token_task(
    db: AsyncSession = Depends(deps.get_session),
    *,
    task: TokenTaskModel = Depends(deps.get_captcha_token_task),
    solved_status: bool = Query(
        description="Статус решения задачи верно/неверно",
    ),
) -> TaskStatusInfo:
    status = enums.TaskStatusEnum.Success if solved_status else enums.TaskStatusEnum.Fail
    task_data = await token_solver.update_task_status_and_report(
        db=db, task=task, status=status
    )
    return TaskStatusInfo(**task_data)
