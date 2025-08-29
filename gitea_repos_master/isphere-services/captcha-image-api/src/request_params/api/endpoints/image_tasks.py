from typing import Optional

from fastapi import APIRouter, Depends, Query, Response
from sqlalchemy.ext.asyncio import AsyncSession
from starlette import status

from src.common import deps, enums
from src.db.models import ImageTaskModel
from src.logic.solvers import image_solver
from src.logic.source import captcha_source_service
from src.request_params.input_queries import CommonQueryInput, TaskStatisticInputQuery
from src.schemas import ImageTaskInfo, TaskStatisticInfo, TaskStatusInfo

router: APIRouter = APIRouter()


@router.get(
    "/result",
    status_code=status.HTTP_200_OK,
    response_model=ImageTaskInfo,
    description="Получение результата решения капчи.",
)
async def get_task_result(
    db: AsyncSession = Depends(deps.get_session),
    *,
    task: ImageTaskModel = Depends(deps.get_captcha_image_task),
) -> ImageTaskInfo:
    solved_task = await image_solver.retrieve_task_solution(
        db=db,
        task=task,
    )
    return ImageTaskInfo.model_validate(solved_task)


@router.get(
    "/image",
    status_code=status.HTTP_200_OK,
    description="Получение привязанного к задаче изображения капчи",
)
async def fetch_task_image(
    task: ImageTaskModel = Depends(deps.get_captcha_image_task),
) -> Response:
    image_content = await image_solver.service.get_task_image(task=task)
    return Response(
        content=image_content,
        headers={"Content-Disposition": f'attachment; filename="captcha_{task.id}.jpeg"'},
        media_type="application/octet-stream",
    )


@router.get(
    "/statistic",
    status_code=status.HTTP_200_OK,
    response_model=TaskStatisticInfo,
    description="""
    Получение статистики результатов решения капч за период по указанному источнику.
    При отсутствии параметров period_days и period_hours расчет статистики производится за 7 последних календарных дней.
    """,
)
async def get_image_tasks_statistic(
    db: AsyncSession = Depends(deps.get_session),
    *,
    input_data: TaskStatisticInputQuery = Depends(TaskStatisticInputQuery),
    source_name: Optional[str] = Depends(CommonQueryInput.source_query(None)),
) -> TaskStatisticInfo:
    source = (
        await captcha_source_service.get_source(
            db=db,
            filter_kwargs={
                captcha_source_service.repository.model.name.name: source_name
            },
        )
        if source_name is not None
        else None
    )

    result = await image_solver.service.repository.get_statistic(
        db=db,
        parent=source,
        provider=input_data.provider,
        period_days=input_data.period_days,
        period_hours=input_data.period_hours,
    )

    return result


@router.put(
    "/update",
    status_code=status.HTTP_200_OK,
    response_model=TaskStatusInfo,
    description="Обновление статуса решения задачи и отправка отчета, если задача привязана к внешнему провайдеру",
)
async def update_task_status(
    db: AsyncSession = Depends(deps.get_session),
    *,
    task: ImageTaskModel = Depends(deps.get_captcha_image_task),
    solved_status: bool = Query(
        description="Статус решения задачи верно/неверно",
    ),
) -> TaskStatusInfo:
    status = enums.TaskStatusEnum.Success if solved_status else enums.TaskStatusEnum.Fail
    task_data = await image_solver.update_task_status_and_report(
        db=db, task=task, status=status
    )
    return TaskStatusInfo(**task_data)
