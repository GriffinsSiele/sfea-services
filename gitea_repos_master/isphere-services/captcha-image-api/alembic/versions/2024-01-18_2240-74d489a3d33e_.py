"""empty message

Revision ID: 74d489a3d33e
Revises: e4f5f900c877
Create Date: 2024-01-18 22:40:25.601545

"""

from typing import Sequence, Union

import sqlalchemy as sa
from sqlalchemy.orm import Session

from src.common.utils import SolutionSpecificationFormatter
from src.config.db_config import db_settings
from src.db.models import SourceModel

# revision identifiers, used by Alembic.
revision: str = "74d489a3d33e"
down_revision: Union[str, None] = "e4f5f900c877"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


class SolutionSpecificationMigrator:
    def __init__(self):
        self.formatter = SolutionSpecificationFormatter
        self.engine = sa.create_engine(
            url=db_settings.POSTGRES_URL_ASYNC.replace(
                "postgresql+asyncpg://", "postgresql://"
            )
        )

    def _get_db_sources(self, session: Session) -> Sequence["SourceModel"]:
        return session.execute(sa.select(SourceModel)).scalars().all()

    def format_specification(self, type_: str = ""):
        with Session(self.engine) as session:
            try:
                db_sources = self._get_db_sources(session)
                for source in db_sources:
                    if source.solution_specification is not None:
                        (
                            self.formatter.downgrade_spec(source.solution_specification)
                            if type_ == "downgrade"
                            else self.formatter.update_spec(source.solution_specification)
                        )
                        session.add(source)
                        session.flush()
                session.commit()
            except Exception:
                session.rollback()


def upgrade() -> None:
    SolutionSpecificationMigrator().format_specification()


def downgrade() -> None:
    SolutionSpecificationMigrator().format_specification("downgrade")
