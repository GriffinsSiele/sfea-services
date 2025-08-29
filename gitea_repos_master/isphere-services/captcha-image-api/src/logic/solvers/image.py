import asyncio
import socket
from typing import Any

from sqlalchemy.ext.asyncio import AsyncSession

from src.common import constant, enums, exceptions, utils
from src.common.validators import CaptchaSolutionValidator
from src.config.api_config import api_settings
from src.db.models import ImageTaskModel, SourceModel
from src.logic.nnetworks import nnetwork_service
from src.logic.provider_api import provider_api_service
from src.logic.s3service import s3_service
from src.logic.solvers.base import BaseSolver
from src.logic.source import captcha_source_service
from src.logic.tasks import image_tasks_service
from src.logic.tasks.image_tasks import ImageTaskService


class ImageSolver(BaseSolver[ImageTaskService, ImageTaskModel]):
    def __init__(self, service: ImageTaskService):
        super().__init__(service=service)
        self.bucket = api_settings.S3_BUCKET_MAIN

    def normalize_solution(self, solution: dict[str, Any]) -> str:
        return solution["text"]

    async def _validate_image_text(
        self,
        db: AsyncSession,
        task: ImageTaskModel,
        source: SourceModel,
    ) -> None:
        try:
            await CaptchaSolutionValidator.validate_task_solution(
                task=task, source=source
            )
        except exceptions.ValidationError as exc:
            await self.update_task_status_and_report(
                db=db, task=task, status=enums.TaskStatusEnum.Fail
            )
            raise exc

    async def _decode_by_nnetworks(
        self,
        *,
        db: AsyncSession,
        file: bytes,
        task: ImageTaskModel,
        source: SourceModel,
        validate_accuracy: bool = False,
    ) -> utils.DecoderResult:
        self.logger.info(
            f"Decoding captcha by nnetwork decoder. TASK: {task.id}, SOURCE: {source.name}"
        )
        decoder_data = nnetwork_service.decode_captcha(file=file, nn_name=source.name)

        if validate_accuracy:
            valid_acc = source.auto_mode_config.get("min_acc", 0)
            if valid_acc > decoder_data.accuracy:
                raise exceptions.BadRequestException(
                    f"Solution accuracy is too low: {decoder_data.accuracy} ({valid_acc}). SOURCE: {source.name}, SOLUTION: {decoder_data.solution}"
                )

        updated_task = await self.service.repository.update(
            db=db,
            db_obj=task,
            obj_in=decoder_data.model_dump(exclude={"id", "accuracy"}),
        )
        decoder_data.id = updated_task.id
        self.logger.info(
            f"Image decoded. TASK: {task.id}, PROVIDER: {task.provider}, SOURCE: {source.name}, TIME: {decoder_data.decode_time}s, ACCURACY: {decoder_data.accuracy}, SOLUTION: {decoder_data.solution}"
        )
        return decoder_data

    async def _decode_by_provider(
        self,
        db: AsyncSession,
        file: bytes,
        task: ImageTaskModel,
        provider: str,
        source: SourceModel,
        timeout: int,
    ) -> utils.DecoderResult:
        self.logger.info(
            f"Requesting task submition. TASK: {task.id}, PROVIDER: {provider}, SOURCE: {source.name}"
        )
        submitted_task = await self.submit_task(
            db=db, task=task, provider=provider, source=source, file=file
        )
        checked_task = await self.wait_for_task_solution(
            db=db, timeout=timeout, task=submitted_task
        )
        return utils.DecoderResult(**checked_task.dict())

    async def _decode_by_auto(
        self,
        db: AsyncSession,
        task: ImageTaskModel,
        source: SourceModel,
        image: utils.ImageObject,
        timeout: int,
    ) -> utils.DecoderResult:
        try:
            nn_decoder_result = await self._decode_by_nnetworks(
                db=db,
                file=image.content,
                task=task,
                source=source,
                validate_accuracy=True,
            )
            return nn_decoder_result
        except Exception as exc:
            self.logger.info(
                f"Unable to decode with nnetwork. DETAIL: {exc.message if hasattr(exc, 'message') else exc.__str__()}"
            )
        for provider in source.provider_priority_queue:
            try:
                provider_decoder_result = await self._decode_by_provider(
                    db=db,
                    file=image.content,
                    provider=provider,
                    task=task,
                    source=source,
                    timeout=timeout,
                )
                return provider_decoder_result

            except Exception as exc:
                self.logger.info(
                    f"An exception occurred with provider '{provider}'. DETAIL: {exc.message if hasattr(exc, 'message') else exc.__str__()}"
                )
        if not task.provider:
            raise exceptions.BadRequestException(
                "Failed to solve task by nnetworks and/or external providers."
            )

        return utils.DecoderResult(**task.dict())

    async def submit_task(
        self,
        db: AsyncSession,
        task: ImageTaskModel,
        source: SourceModel,
        provider: str,
        file: bytes,
    ) -> ImageTaskModel:
        captcha_id = await provider_api_service.submit_image_task(
            provider=provider,
            file=file,
            callback_url=self.callback_url_tamplate.format(
                task_id=task.id, task_type=task.task_type
            ),
            solution_specification=dict(source.solution_specification),
        )

        updated_task = await self.service.repository.update(
            db=db,
            db_obj=task,
            obj_in={"task_id": captcha_id, "provider": provider},
        )
        return updated_task

    async def process_captcha_task(
        self,
        db: AsyncSession,
        provider: str,
        source: str,
        image: utils.ImageObject,
        timeout: int,
    ) -> utils.DecoderResult:
        try:
            db_source = await captcha_source_service.get_or_create_source(
                db=db, source=source
            )

            task = await self.service.repository.create(
                db=db,
                obj_in={
                    "source": db_source.id,
                    "status": enums.TaskStatusEnum.InUse,
                },
                with_commit=False,
            )
        except (socket.gaierror, TimeoutError):
            self.logger.info(
                f"Unable to initialize task instance due to database connection issues. Proceeding by solving captcha with nnetwork. SOURCE: {source}"
            )
            return nnetwork_service.decode_captcha(file=image.content, nn_name=source)

        captcha_ttl = db_source.auto_mode_config.get(
            "captcha_ttl"
        ) or captcha_source_service.repository.model.default_auto_mode_config.get(
            "captcha_ttl"
        )
        _decode_by_auto_with_timeout = utils.with_timeout(deadline=captcha_ttl)(
            self._decode_by_auto
        )
        decoder_result = (
            await _decode_by_auto_with_timeout(
                db=db, task=task, source=db_source, image=image, timeout=timeout
            )
            if provider == constant.AUTO_PROVIDER
            else (
                await self._decode_by_provider(
                    db=db,
                    task=task,
                    source=db_source,
                    provider=provider,
                    file=image.content,
                    timeout=timeout,
                )
                if provider in provider_api_service.clients_list
                else await self._decode_by_nnetworks(
                    db=db,
                    task=task,
                    source=db_source,
                    file=image.content,
                )
            )
        )

        await self.service.upload_image_on_bucket(
            db=db, task=task, image=image, source=source
        )
        await self._validate_image_text(db=db, task=task, source=db_source)

        return decoder_result

    async def update_task_status_and_report(
        self,
        db: AsyncSession,
        task: ImageTaskModel,
        status: enums.TaskStatusEnum,
    ) -> dict[str, Any]:
        self.logger.info(
            f"Updating task status. TASK: {task.id}, PROVIDER: {task.provider}, STATUS: {status.value}."
        )
        updated_task = await self.service.repository.update(
            db=db, db_obj=task, obj_in={"status": status}  # type: ignore[arg-type]
        )
        updated_task_dict = updated_task.dict()

        await asyncio.gather(
            s3_service.update_image_tags(
                bucket=self.bucket,
                task=task,
                update_data={"status": status.value},
            ),
            self.report_task(task_data=updated_task_dict, status=status),
        )
        return updated_task_dict

    async def report_task(
        self,
        task_data: dict[str, Any],
        status: enums.TaskStatusEnum,
    ) -> None:
        if task_data["provider"] not in constant.SEND_REPORT_DISABLED:
            report_status = await provider_api_service.send_image_task_report(
                task_data=task_data, status=status
            )
            sent_status = (
                "successfully sent" if report_status == "success" else "not sent"
            )
            self.logger.info(
                f"Report was {sent_status}. TASK: {task_data['id']}, PROVIDER: {task_data['provider']}, STATUS: {status.value}."
            )

    async def process_callback(
        self,
        db: AsyncSession,
        task_id: int,
        data: bytes | dict[str, Any],
    ) -> None:
        task = await self.service.get_task(
            db=db,
            filter_kwargs={self.service.repository.model_pk_field_name: task_id},
        )
        self.logger.info(
            f"Processing callback data. TASK: {task.id}, PROVIDER: {task.provider}."  # type: ignore[union-attr]
        )
        solution = provider_api_service.process_callback(
            provider=task.provider, data=data  # type: ignore[union-attr]
        )
        image_text = self.normalize_solution(solution=solution)
        await self.service.add_solution(
            db=db,
            task=task,  # type: ignore[arg-type]
            solution=image_text,
        )


solver: ImageSolver = ImageSolver(service=image_tasks_service)
