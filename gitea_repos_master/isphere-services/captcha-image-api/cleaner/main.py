import asyncio
from datetime import datetime, timedelta
from typing import Any
from zoneinfo import ZoneInfo

from botocore import exceptions as boto_exception
from sqlalchemy.ext.asyncio import AsyncSession

from cleaner.repository import CleanerRepository
from cleaner.scheduler import CronJobScheduler
from src.common.enums import TaskStatusEnum
from src.common.utils import SingletonLogging
from src.config.cron_config import cron_settings
from src.db.models import ImageTaskModel, TokenTaskModel
from src.db.session import session_generator
from src.logic.s3service import s3_service
from src.logic.sentry import Sentry
from src.logic.telegram import TelegramService


class DBCleaner(SingletonLogging):
    def __init__(self):
        super().__init__()
        self.rows_limit = 10_000
        self.scheduler = CronJobScheduler()
        self.repository = CleanerRepository()
        self.telegram = TelegramService(
            token=cron_settings.TELEGRAM_TOKEN_BOT,
            chat_id=cron_settings.TELEGRAM_CHAT_ID,
        )
        self.bucket = cron_settings.S3_BUCKET_MAIN

    def _subtask_success_result(
        self, task_type: str, deleted_count: int, from_date: datetime
    ) -> dict[str, Any]:
        return {
            task_type: {
                "status": TaskStatusEnum.Success,
                "removed": deleted_count,
                "date_from": from_date.strftime("%d.%m.%Y-%H:%M:%S"),
            }
        }

    def _subtask_error_result(self, task_type: str, exc_msg: str) -> dict[str, Any]:
        self.logger.info(f"Error while handling '{task_type}'. {exc_msg}")
        return {task_type: {"status": TaskStatusEnum.Fail, "description": exc_msg}}

    async def _send_notification(self, *msg_data: dict[str, Any] | str) -> None:
        payload = await self.telegram.send(msg_data)
        sent_status = (
            "was sent."
            if payload and payload["ok"]
            else "was not sent"
            + (f". Error description:'{payload['description']}'" if payload else ".")
        )

        self.logger.info(f"Notification {sent_status}")

    async def _scan_s3_leftovers_and_clear(self, from_date: datetime) -> dict[str, Any]:
        deleted_count = 0
        task_type = "s3_images"

        self.logger.info(
            f"Scanning S3 for orphan images older than {from_date.strftime('%d.%m.%Y-%H:%M:%S')}..."
        )

        try:
            pages = s3_service.get_paginator_pages(
                bucket=self.bucket,
                prefix=cron_settings.S3_PREFIX_IMAGES,  # type: ignore[arg-type]
                page_size=1000,
            )

            for page in pages:
                objs_list = page.get("Contents", [])
                self.logger.info(f"Fetched info about {len(objs_list)} images...")
                s3_objs_to_delete = [
                    obj["Key"] for obj in objs_list if obj["LastModified"] < from_date
                ]
                if s3_objs_to_delete:
                    self.logger.info(
                        f"Removing {len(s3_objs_to_delete)} objects from S3 bucket."
                    )
                    await s3_service.delete_objects(
                        bucket=self.bucket, s3_objects=s3_objs_to_delete
                    )
                    deleted_count += len(s3_objs_to_delete)

            return self._subtask_success_result(
                task_type=task_type, deleted_count=deleted_count, from_date=from_date
            )

        except boto_exception.ConnectionError:
            msg_error = "Unable to establish connection to S3."
            return self._subtask_error_result(task_type=task_type, exc_msg=msg_error)

        except Exception as exc:
            msg_error = f"'Error type: {type(exc)}. Detail: {exc.message if hasattr(exc, 'message') else exc.__str__()}'"
            return self._subtask_error_result(task_type=task_type, exc_msg=msg_error)

    async def _clean_image_tasks(
        self, session: AsyncSession, from_date: datetime, provider: str
    ) -> dict[str, Any]:
        self.logger.info(
            f"Removing image task records older then '{from_date.strftime('%d.%m.%Y-%H:%M:%S')}'..."
        )
        task_type = f"image_task_{provider}"
        try:
            tasks_count = await self.repository.count_tasks_to_delete(
                session=session, model=ImageTaskModel, from_date=from_date, provider=provider  # type: ignore[arg-type]
            )
            success_data = self._subtask_success_result(
                task_type=task_type, deleted_count=tasks_count, from_date=from_date
            )
            while tasks_count > 0:
                tasks_count -= self.rows_limit
                (
                    ids_list,
                    s3_filenames_list,
                ) = await self.repository.get_image_task_id_and_s3filename_chunks(
                    session=session, from_date=from_date, provider=provider, limit=self.rows_limit  # type: ignore[arg-type]
                )

                await self.repository.remove_task_chunks(
                    session=session, ids_list=ids_list, model=ImageTaskModel  # type: ignore[arg-type]
                )
                if s3_filenames_list:
                    await s3_service.delete_objects(
                        bucket=self.bucket, s3_objects=s3_filenames_list
                    )
                    self.logger.info(f"Deleted {len(s3_filenames_list)} images.")
            return success_data

        except boto_exception.ConnectionError:
            msg_error = "Unable to establish connection to S3."
            return self._subtask_error_result(task_type=task_type, exc_msg=msg_error)
        except Exception as exc:
            msg_error = f"Error type: {type(exc)}. Detail: {exc.message if hasattr(exc, 'message') else exc.__str__()}"
            return self._subtask_error_result(task_type=task_type, exc_msg=msg_error)

    async def _clean_token_tasks(
        self, session: AsyncSession, from_date: datetime
    ) -> dict[str, Any]:
        task_type = "token_task"
        self.logger.info(
            f"Removing token task records older then '{from_date.strftime('%d.%m.%Y-%H:%M:%S')}'..."
        )
        try:
            tasks_count = await self.repository.count_tasks_to_delete(
                session=session, model=TokenTaskModel, from_date=from_date  # type: ignore[arg-type]
            )
            success_data = self._subtask_success_result(
                task_type=task_type, deleted_count=tasks_count, from_date=from_date
            )
            while tasks_count > 0:
                tasks_count -= self.rows_limit
                ids_list = await self.repository.fetch_token_task_chunks(
                    session=session, from_date=from_date, limit=self.rows_limit  # type: ignore[arg-type]
                )

                await self.repository.remove_task_chunks(
                    session=session, ids_list=ids_list, model=TokenTaskModel  # type: ignore[arg-type]
                )
            return success_data

        except Exception as exc:
            msg_error = f"Error type: {type(exc)}. Detail: {exc.message if hasattr(exc, 'message') else exc.__str__()}"
            return self._subtask_error_result(task_type=task_type, exc_msg=msg_error)

    async def cleanup_and_notify(self) -> None:
        start_time = datetime.now(tz=ZoneInfo("Europe/Moscow"))
        self.logger.info(f"Removing task records and dangling s3 images...")

        async with session_generator() as session:
            token_tasks_status = await self._clean_token_tasks(
                session=session,
                from_date=start_time
                - timedelta(days=cron_settings.DB_CLEANUP_TOKEN_DAYS),
            )
            image_tasks_providers_status = await self._clean_image_tasks(
                session=session,
                from_date=start_time
                - timedelta(days=cron_settings.DB_CLEANUP_IMAGE_EXTERNAL_DAYS),
                provider="!nnetworks",
            )
            image_tasks_nnetworks_status = await self._clean_image_tasks(
                session=session,
                from_date=start_time
                - timedelta(days=cron_settings.DB_CLEANUP_IMAGE_NNETWORK_DAYS),
                provider="nnetworks",
            )
            image_leftovers_s3_status = await self._scan_s3_leftovers_and_clear(
                from_date=start_time
                - timedelta(days=cron_settings.DB_CLEANUP_IMAGE_EXTERNAL_DAYS)
            )
            self.logger.info("Deletion process completed.")

        perf_time = (datetime.now(tz=ZoneInfo("Europe/Moscow")) - start_time).seconds
        prefix_text = f"captcha-image-api-cleaner\n\nЗавершена работа по очистке капча-задач.\nВыполнено за: {perf_time} c.\n\n"

        await self._send_notification(
            prefix_text,
            token_tasks_status,
            image_tasks_nnetworks_status,
            image_tasks_providers_status,
            image_leftovers_s3_status,
        )

    async def run_scheduler(self) -> None:
        await self.scheduler.run_job(self.cleanup_and_notify)


if __name__ == "__main__":
    Sentry().create(dsn=cron_settings.SENTRY_URL_CLEANER, mode=cron_settings.MODE)
    cleaner = DBCleaner()
    asyncio.run(cleaner.run_scheduler())
