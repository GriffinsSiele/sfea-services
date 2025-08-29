"""empty message

Revision ID: 4de7dcd68504
Revises: 
Create Date: 2023-11-20 11:48:31.489322

"""

from typing import Sequence, Union

import sqlalchemy as sa

from alembic import op  # type: ignore[attr-defined]

# revision identifiers, used by Alembic.
revision: str = "4de7dcd68504"
down_revision: Union[str, None] = None
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    op.create_table(
        "source",
        sa.Column("name", sa.String(), nullable=False),
        sa.Column("is_nnetwork_provider", sa.Boolean(), nullable=False),
        sa.Column("id", sa.Integer(), autoincrement=True, nullable=False),
        sa.Column("created_at", sa.DateTime(timezone=True), nullable=True),
        sa.Column("updated_at", sa.DateTime(timezone=True), nullable=True),
        sa.PrimaryKeyConstraint("id"),
        sa.UniqueConstraint("name"),
    )
    op.create_table(
        "captcha_task",
        sa.Column("task_id", sa.String(), nullable=True),
        sa.Column("provider", sa.String(length=20), nullable=True),
        sa.Column("solved_status", sa.Boolean(), nullable=True),
        sa.Column("source", sa.Integer(), nullable=False),
        sa.Column("decode_time", sa.Float(precision=5), nullable=True),
        sa.Column("id", sa.Integer(), autoincrement=True, nullable=False),
        sa.Column("created_at", sa.DateTime(timezone=True), nullable=True),
        sa.Column("updated_at", sa.DateTime(timezone=True), nullable=True),
        sa.ForeignKeyConstraint(["source"], ["source.id"], ondelete="CASCADE"),
        sa.PrimaryKeyConstraint("id"),
    )


def downgrade() -> None:
    op.drop_table("captcha_task")
    op.drop_table("source")
