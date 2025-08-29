from sqlalchemy import event, orm, null, Column, DateTime
from sqlalchemy.orm import Session


# do_orm_execute event for all Session instances
@event.listens_for(Session, "do_orm_execute")
def __add_filter_by_deleted_field(execute_state):
    """
    Intercept all ORM queries. Add a with_loader_criteria option to all of them.
    The new option is named include_deleted. Perform default filter: deleted IS NULL.
    Use in query like: select(Model).execution_options(include_deleted=True)
    """
    if (
        not execute_state.is_column_load
        and not execute_state.is_relationship_load
        and not execute_state.execution_options.get("include_deleted", False)
    ):
        execute_state.statement = execute_state.statement.options(
            orm.with_loader_criteria(
                DeletedDateTime, lambda cls: cls.deleted == null(), include_aliases=True
            )
        )


class DeletedDateTime:
    """Mixin that identifies a class as having deleted entities"""

    deleted = Column(
        DateTime,
        nullable=True,
        comment="Optional reserved field for identifying permanently deleted proxy "
        "objects. Stores the date and time of proxy deletion. Required to save "
        "statistics of proxy.",
    )
