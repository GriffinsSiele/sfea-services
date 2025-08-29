from .base import BaseManager


class ForiegnManager(BaseManager):
    async def bulk_insert_by_ids(
        self, ids_field_name: str, ids: list[int], **extra_fields
    ) -> None:
        """
        Creates instances for all id in the ids list. The ID from the ids list will be
        passed to the field specified in the ids_field_name parameter. Adds additional
        values to instances from extra_fields dict.
        """
        if ids:
            objs = [{**{ids_field_name: obj}, **extra_fields} for obj in ids]
            await self.session.execute(self.model.__table__.insert().values(objs))
