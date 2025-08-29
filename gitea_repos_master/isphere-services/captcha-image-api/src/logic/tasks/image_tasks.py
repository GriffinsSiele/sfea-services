import asyncio
import hashlib

from sqlalchemy.ext.asyncio import AsyncSession

from src.common import constant, enums, exceptions, utils
from src.config.api_config import api_settings
from src.db.models import ImageTaskModel
from src.db.repositories import ImageTaskRepository
from src.logic.s3service import s3_service

from .base import BaseTaskService


class ImageTaskService(BaseTaskService[ImageTaskRepository, ImageTaskModel]):
    async def get_task_image(self, task: ImageTaskModel) -> bytes:
        if not task.s3_filename:
            raise exceptions.BadRequestException(
                "Task record does not contain related image"
            )

        s3_full_filename = s3_service.add_object_prefix(
            prefix=api_settings.S3_PREFIX_IMAGES, filename=task.s3_filename
        )

        obj = await s3_service.get_object(
            bucket=api_settings.S3_BUCKET_MAIN, key=s3_full_filename
        )
        return obj["Body"].read()

    async def upload_image_on_bucket(
        self,
        db: AsyncSession,
        task: ImageTaskModel,
        image: utils.ImageObject,
        source: str,
    ) -> None:
        if task.provider not in {None, constant.NNETWORKS_PROVIDER}:
            tags = {
                "solution": utils.S3Coder.encode(task.solution),
                "status": enums.TaskStatusEnum.InUse.value,
            }
            filename = (
                f"/{source}/{hashlib.sha256(image.content).hexdigest()}.{image.extension}"
            )

            await asyncio.gather(
                s3_service.upload_image(
                    bucket=api_settings.S3_BUCKET_MAIN,
                    file=image.content,
                    filename=filename,
                    tags_data=tags,
                ),
                self.repository.update(
                    db=db,
                    db_obj=task,
                    obj_in={self.repository.model.s3_filename.name: filename},
                ),
            )

            self.logger.info(
                f"Uploaded captcha image on S3. TASK: {task.id}, TAGS: {tags}"
            )

    async def add_solution(
        self,
        db: AsyncSession,
        task: ImageTaskModel,
        solution: str,
    ) -> ImageTaskModel:
        existing_task_solution = task.solution
        updated_task = await super().add_solution(db=db, task=task, solution=solution)
        if existing_task_solution is None:
            await s3_service.update_image_tags(
                bucket=api_settings.S3_BUCKET_MAIN,
                task=updated_task,
                update_data={"solution": utils.S3Coder.encode(solution)},
            )

        return updated_task


service: "ImageTaskService" = ImageTaskService(
    repository=ImageTaskRepository(ImageTaskModel)
)
