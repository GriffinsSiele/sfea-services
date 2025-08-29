from typing import Optional

from fastapi import APIRouter, Depends, Query
from sqlalchemy.ext.asyncio import AsyncSession
from starlette import status

from src.common import deps
from src.db.models import WebsiteModel
from src.logic.websites import website_service
from src.schemas import Website, WebsiteUpdate

router: APIRouter = APIRouter()


@router.get(
    "",
    status_code=status.HTTP_200_OK,
    response_model=list[Website] | Website,
    response_model_exclude_none=True,
    description="Получение списка данных о сайтах, для которых решается капча",
)
async def get_websites(
    db: AsyncSession = Depends(deps.get_session),
    *,
    website_name: Optional[str] = Query(
        default=None,
        description="Передайте идентификатор 'name' записи веб-сайта, чтобы получить запись данных о конкретном веб-сайте.",
    ),
) -> list[Website] | Website:
    if website_name:
        website = await website_service.get_website(
            db=db,
            filter_kwargs={website_service.repository.model.name.name: website_name},
        )
        return Website.model_validate(website)
    websites = await website_service.get_websites(db=db)
    return [Website.model_validate(website) for website in websites]


@router.put(
    "/update",
    status_code=status.HTTP_200_OK,
    response_model=Website,
    response_model_exclude_none=True,
    description="""Обновление параметров записи о веб-странице сайта и спецификации токенов для капч.\t
        Все поля являются опциональными за одним исключением: при попытке произвести перезапись параметра 'website_config' необходимо задать значения полей 'provider', 'website_key' и 'token_type'.
        """,
)
async def update_website(
    db: AsyncSession = Depends(deps.get_session),
    *,
    website_name: str = Query(
        description="Идентификатор 'name' записи веб-сайта.",
    ),
    input_data: WebsiteUpdate,
) -> Website:
    db_website = await website_service.get_website(
        db=db,
        filter_kwargs={website_service.repository.model.name.name: website_name},
    )
    _input_data = input_data.model_dump(exclude_unset=True)
    new_url = _input_data.pop("url", None)
    new_name = _input_data.pop("name", None)
    new_max_token_pool = _input_data.pop("max_token_pool", None)
    new_min_token_pool = _input_data.pop("min_token_pool", None)
    new_website_config = _input_data.pop("website_config", {})
    updated_website = await website_service.repository.update(
        db=db,
        db_obj=db_website,
        obj_in={
            website_service.repository.model.url.name: (
                new_url if new_url is not None else db_website.url
            ),
            website_service.repository.model.name.name: (
                new_name if new_name is not None else db_website.name
            ),
            website_service.repository.model.max_token_pool.name: (
                new_max_token_pool
                if new_max_token_pool is not None
                else db_website.max_token_pool
            ),
            website_service.repository.model.min_token_pool.name: (
                new_min_token_pool
                if new_min_token_pool is not None
                else db_website.min_token_pool
            ),
            website_service.repository.model.website_config.name: (
                new_website_config if new_website_config else db_website.website_config
            ),
        },
    )
    return Website.model_validate(updated_website)


@router.delete(
    "/delete",
    status_code=status.HTTP_200_OK,
    response_model=Website,
    response_model_exclude_none=True,
    description="Удаление данных о сайте, для которого решается рекапча",
)
async def delete_website(
    db: AsyncSession = Depends(deps.get_session),
    website: WebsiteModel = Depends(deps.get_website),
) -> Website:
    await website_service.repository.remove(db=db, db_obj=website)
    website_service.logger.info(f"Deleted '{website.name}' website")
    return Website.model_validate(website)
