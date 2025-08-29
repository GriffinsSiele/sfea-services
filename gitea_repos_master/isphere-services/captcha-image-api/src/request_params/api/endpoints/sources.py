from typing import Optional

from fastapi import APIRouter, Depends, Query
from sqlalchemy.ext.asyncio import AsyncSession
from starlette import status

from src.common import deps, exceptions
from src.logic.source import captcha_source_service
from src.schemas import Source, SourceConfigUpdate

router: APIRouter = APIRouter()


@router.get(
    "",
    status_code=status.HTTP_200_OK,
    response_model=list[Source] | Source,
    description="Получение данных об источнике по параметру 'name' или списка всех доступных источников",
)
async def get_sources(
    db: AsyncSession = Depends(deps.get_session),
    *,
    source_name: Optional[str] = Query(
        default=None, description="Идентификатор записи 'name' источника."
    ),
) -> list[Source] | Source:
    if source_name:
        db_source = await captcha_source_service.get_source(
            db=db,
            filter_kwargs={
                captcha_source_service.repository.model.name.name: source_name
            },
        )
        return Source.model_validate(db_source)
    return await captcha_source_service.get_sources(db=db)


@router.put(
    "/config/update",
    status_code=status.HTTP_200_OK,
    response_model=Source,
    description="Изменение данных поля 'solution_specification' источника.",
)
async def update_source(
    db: AsyncSession = Depends(deps.get_session),
    *,
    source_name: str = Query(description="Идентификатор записи 'name' источника."),
    input_data: SourceConfigUpdate,
) -> Source:
    db_source = await captcha_source_service.get_source(
        db=db,
        filter_kwargs={captcha_source_service.repository.model.name.name: source_name},
    )
    _input_data = input_data.model_dump(exclude_unset=True)
    _solution_specification_input = _input_data.get("solution_specification", {})
    _auto_mode_config_input = _input_data.get("auto_mode_config", {})

    updated_source = await captcha_source_service.repository.update(
        db=db,
        db_obj=db_source,
        obj_in={
            captcha_source_service.repository.model.solution_specification.name: {
                **db_source.solution_specification,  # type: ignore[union-attr]
                **_solution_specification_input,
            },
            captcha_source_service.repository.model.auto_mode_config.name: {
                **db_source.auto_mode_config,  # type: ignore[union-attr]
                **_auto_mode_config_input,
            },
        },
    )
    return Source.model_validate(updated_source)


@router.delete(
    "/delete",
    status_code=status.HTTP_200_OK,
    response_model=Source,
    description="Удаление записи источника",
)
async def delete_source(
    db: AsyncSession = Depends(deps.get_session),
    source_id: int = Query(
        description="ID записи источника",
    ),
) -> "Source":
    db_source = await captcha_source_service.repository.get(
        db=db,
        filter_kwargs={captcha_source_service.repository.model_pk_field_name: source_id},
    )
    if db_source is None:
        raise exceptions.NotFoundException(message="Source does not exist")
    await captcha_source_service.repository.remove(db=db, db_obj=db_source)
    captcha_source_service.logger.info(f"Deleted source '{db_source.name}'")
    return Source.model_validate(db_source)
