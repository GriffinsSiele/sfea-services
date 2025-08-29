from typing import AsyncGenerator, Optional

from fastapi import Body, Depends, File, Query, UploadFile
from sqlalchemy.ext.asyncio import AsyncSession

from src.common.utils import ImageObject, fetch_image_content
from src.db.models import ImageTaskModel, TokenTaskModel, WebsiteModel
from src.db.session import session_generator
from src.logic.tasks import image_tasks_service, token_tasks_service
from src.logic.websites import website_service

from .validators import validate_image


async def validate_upload_image_and_process_bytes(
    image: UploadFile = File(
        ..., description=f"Файл капчи, которую необходимо раскодировать."
    )
) -> ImageObject:
    validate_image(content_type=image.content_type, file_size=image.size)
    image_obj = ImageObject(content=await image.read(), extension=image.content_type)
    return image_obj


async def validate_url_image_and_process_bytes(
    file_url: dict = Body(
        {"file_url": "http://path/to/captcha/captcha.jpg"},
        description="URL-путь, по которому располагается файл капчи",
    )
) -> ImageObject:
    image, content_params = await fetch_image_content(file_url["file_url"])
    validate_image(
        content_type=content_params["type"],
        file_size=content_params["size"],
    )
    image_obj = ImageObject(content=image, extension=content_params["type"])
    return image_obj


async def get_session() -> AsyncGenerator:
    async with session_generator() as session:
        yield session


async def get_captcha_image_task(
    db: AsyncSession = Depends(get_session),
    task_id: int = Query(
        description="Идентификатор задачи решения капчи",
    ),
) -> Optional[ImageTaskModel]:
    db_task = await image_tasks_service.get_task(
        db=db,
        filter_kwargs={image_tasks_service.repository.model_pk_field_name: task_id},
    )
    return db_task


async def get_captcha_token_task(
    db: AsyncSession = Depends(get_session),
    task_id: int = Query(
        description="Идентификатор задачи решения капчи",
    ),
) -> Optional[TokenTaskModel]:
    db_task = await token_tasks_service.get_task(
        db=db,
        filter_kwargs={token_tasks_service.repository.model_pk_field_name: task_id},
    )
    return db_task


async def get_website(
    db: AsyncSession = Depends(get_session),
    website_id: int = Query(
        description="ID записи о странице веб-сайта, для которой решаются капчи.",
    ),
) -> Optional[WebsiteModel]:
    db_website = await website_service.get_website(
        db=db,
        filter_kwargs={token_tasks_service.repository.model_pk_field_name: website_id},
    )
    return db_website
