"""empty message

Revision ID: 8e495423137c
Revises: 5d629272be15
Create Date: 2024-03-05 21:48:20.976490

"""

import asyncio
from typing import Sequence, Union

import sqlalchemy as sa
from botocore import exceptions as botocore_exceptions

from alembic import op  # type: ignore[attr-defined]
from src.common import enums, logger, utils
from src.config.s3_config import s3_settings
from src.logic.s3service import s3_service

# revision identifiers, used by Alembic.
revision: str = "8e495423137c"
down_revision: Union[str, None] = "5d629272be15"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


class CaptchaTaskStatusMigrator:
    STATUS_COMPATIBILITY_DATA = {
        None: enums.TaskStatusEnum.InUse.name,
        True: enums.TaskStatusEnum.Success.name,
        False: enums.TaskStatusEnum.Fail.name,
        enums.TaskStatusEnum.InUse.name: None,
        enums.TaskStatusEnum.Success.name: True,
        enums.TaskStatusEnum.Fail.name: False,
    }

    def __init__(self):
        self.task_status_enum_postgresql = enums.task_status_enum_postgresql
        self.logger = logger.Logger(self.__class__.__name__).get_logger()
        self.current_offset = 0

    def _get_table(self) -> sa.Table:
        table_task = sa.Table(
            "captcha_task",
            sa.MetaData(),
            sa.Column("id", sa.Integer(), autoincrement=True, nullable=False),
            sa.Column("created_at", sa.DateTime(timezone=True), nullable=True),
            sa.Column("updated_at", sa.DateTime(timezone=True), nullable=True),
            sa.Column("task_id", sa.String(), nullable=True),
            sa.Column("solution", sa.String(length=10), nullable=True),
            sa.Column("provider", sa.String(length=20), nullable=True),
            sa.Column("solved_status", sa.Boolean(), nullable=True),
            sa.Column("source", sa.Integer(), nullable=False),
            sa.Column("decode_time", sa.Float(precision=5), nullable=True),
            sa.Column("s3_filename", sa.String(length=120), nullable=True),
            sa.Column("status", self.task_status_enum_postgresql),
            sa.ForeignKeyConstraint(["source"], ["source.id"], ondelete="CASCADE"),
            sa.PrimaryKeyConstraint("id"),
        )
        return table_task

    async def _update_s3_object_tags(
        self, task: sa.engine.row.Row, mode: str = "upgrade"
    ):
        try:
            if task.s3_filename is not None:
                s3_object = s3_service.add_object_prefix(prefix=s3_service.images_prefix, filename=task.s3_filename)  # type: ignore[arg-type]
                tags_data = {"solution": utils.S3Coder.encode(task.solution)}

                (
                    tags_data.update(
                        {"solved_status": self.STATUS_COMPATIBILITY_DATA[task.status]}  # type: ignore[dict-item, index]
                    )
                    if mode == "downgrade"
                    else tags_data.update(
                        {"status": self.STATUS_COMPATIBILITY_DATA[task.solved_status]}  # type: ignore[dict-item, attr-defined]
                    )
                )
                await s3_service.add_object_tags(
                    bucket=s3_settings.S3_BUCKET_MAIN,
                    s3_object=s3_object,
                    tags_data=tags_data,
                )
        except botocore_exceptions.ClientError:
            self.logger.info(
                f"An error occurred while managing object '{task.s3_filename}' for task {task.id}"
            )

    async def _migrate_s3_obj_status_tags(
        self, tasks: Sequence[sa.engine.row.Row], mode: str = "upgrade"
    ):
        await asyncio.gather(
            *[self._update_s3_object_tags(task=task, mode=mode) for task in tasks]
        )

    def _migrate_tasks_statuses(self, mode: str = "upgrade"):
        table_task = self._get_table()
        connection = op.get_bind()

        task_count = connection.execute(
            sa.select(sa.func.count()).select_from(table_task)
        ).scalar()
        self.logger.info(f"Total rows to update: {task_count}")

        limit, offset = 100, 0

        while task_count and task_count > 0:
            tasks = connection.execute(
                sa.select(table_task)
                .limit(limit)
                .offset(offset)
                .order_by(table_task.c.created_at)
            ).all()

            offset += min(limit, task_count)

            task_ids = [task.id for task in tasks]

            if mode == "upgrade":
                connection.execute(
                    table_task.update()
                    .where(table_task.c.id.in_(task_ids))
                    .values(
                        status=sa.case(
                            (
                                table_task.c.solved_status.is_(None),
                                enums.TaskStatusEnum.InUse,
                            ),
                            (
                                table_task.c.solved_status.is_(True),
                                enums.TaskStatusEnum.Success,
                            ),
                            (
                                table_task.c.solved_status.is_(False),
                                enums.TaskStatusEnum.Fail,
                            ),
                        ).cast(table_task.c.status.type)
                    )
                )

            else:
                connection.execute(
                    table_task.update()
                    .where(table_task.c.id.in_(task_ids))
                    .values(
                        solved_status=sa.case(
                            (
                                table_task.c.status == enums.TaskStatusEnum.InUse.name,
                                None,
                            ),
                            (
                                table_task.c.status == enums.TaskStatusEnum.Success.name,
                                True,
                            ),
                            (
                                table_task.c.status == enums.TaskStatusEnum.Fail.name,
                                False,
                            ),
                        )
                    )
                )

            event_loop = asyncio.get_event_loop()
            asyncio.run_coroutine_threadsafe(
                self._migrate_s3_obj_status_tags(tasks=tasks, mode=mode), event_loop
            )

            self.logger.info(f"Updated {offset} rows")
            task_count -= limit

    def upgrade_status(self):
        self.task_status_enum_postgresql.create(op.get_bind(), checkfirst=True)
        op.add_column(
            "captcha_task", sa.Column("status", self.task_status_enum_postgresql)
        )
        self._migrate_tasks_statuses()
        op.drop_column("captcha_task", "solved_status")

    def downgrade_status(self):
        op.add_column(
            "captcha_task", sa.Column("solved_status", sa.Boolean(), nullable=True)
        )
        self._migrate_tasks_statuses(mode="downgrade")
        op.drop_column("captcha_task", "status")
        self.task_status_enum_postgresql.drop(op.get_bind())


def upgrade():
    CaptchaTaskStatusMigrator().upgrade_status()


def downgrade() -> None:
    CaptchaTaskStatusMigrator().downgrade_status()
