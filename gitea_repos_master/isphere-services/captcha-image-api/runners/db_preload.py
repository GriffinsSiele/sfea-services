# Run this script with a none or list of arguments. Valid arguments values:
# 1. revision - autogenerate migration file for current state of models;
# 2. upgrade - apply migration to update current state of database;
# 3. populate - upload data to db (currently upload available sources from S3) if not exist in db.
# Example: python3 init_db.py revision upgrade

# If no args provided, assuming that there is already a single migration file in /alembic/versions, "upgrade" and "populate" commands will run


import asyncio
import sys

from putils_logic.putils import PUtils

from alembic import command  # type: ignore[attr-defined]
from alembic.config import Config
from src.common import logger
from src.config.api_config import api_settings
from src.db.models import SourceModel
from src.db.session import session_generator
from src.logic.s3service import s3_service
from src.logic.source import captcha_source_service

VALID_ARGS = {
    "upgrade",
    "revision",
    "populate",
}

LOGGER = logger.Logger("DBLoader").get_logger()


def validate_input_args(input_argv: list[str]):
    _argv = []
    for arg in input_argv[1:]:
        if arg.lower() not in VALID_ARGS:
            LOGGER.error(f'Argument "{arg.lower()}" is unknown')
            sys.exit(3)
        _argv.append(arg.lower())

    return _argv


class DatabasePreload:
    __SOURCES_S3_OBJECTS = s3_service.get_bucket_objects(
        bucket=api_settings.S3_BUCKET_MAIN, prefix=api_settings.S3_PREFIX_NNETWORKS
    )

    def __init__(self):
        # Set alembic main directories
        root_dir = api_settings.ROOT_PATH
        alembic_dir = PUtils.bp(root_dir, "alembic")
        versions_dir = PUtils.bp(alembic_dir, "versions")

        # Set alembic config
        self.config = Config(file_=PUtils.bp(root_dir, "alembic.ini"))
        self.config.set_main_option(name="script_location", value=alembic_dir)

        # Set input sys argv
        self.input_argv = validate_input_args(input_argv=sys.argv)

        # Create alembic /versions dir if not exist
        if not PUtils.is_dir_exists(versions_dir):
            PUtils.mkdir(versions_dir)

    async def __prepare_nnetwork_sources(self) -> list[SourceModel]:
        sources = []
        specification = (
            captcha_source_service.repository.model.default_solution_specification
        )
        for source_object in self.__SOURCES_S3_OBJECTS:
            characters_pool = await self.__get_source_characters_pool(source_object)
            source = SourceModel(
                name=s3_service.clear_object_prefix(source_object),  # type: ignore[call-arg]
                is_nnetwork_provider=True,  # type: ignore[call-arg]
                solution_specification={**specification, **characters_pool},  # type: ignore[call-arg]
            )
            sources.append(source)
        return sources

    async def __get_source_characters_pool(self, source_object: str) -> dict[str, str]:
        obj_tags = await s3_service.get_object_tags(
            bucket=api_settings.S3_BUCKET_MAIN, s3_object=source_object
        )
        return {"characters": obj_tags.get("characters", "")}

    def _revision(self):
        command.revision(self.config, autogenerate=True, head="head")

    def _upgrade(self):
        command.upgrade(self.config, revision="head")

    async def preload_database(self):
        preload_sources = await self.__prepare_nnetwork_sources()
        async with session_generator() as session:
            for source in preload_sources:
                db_source = await captcha_source_service.repository.get(
                    db=session,
                    filter_kwargs={
                        captcha_source_service.repository.model.name.name: source.name
                    },
                )
                if db_source is None:
                    session.add(source)
                    await session.commit()
                elif db_source.solution_specification is None:
                    await captcha_source_service.repository.update(
                        db=session,
                        db_obj=db_source,
                        obj_in={"solution_specification": source.solution_specification},
                    )


async def main():
    loader = DatabasePreload()
    args = loader.input_argv or ["upgrade", "populate"]
    if "revision" in args:
        loader._revision()
    if "upgrade" in args:
        loader._upgrade()
    if "populate" in args:
        await loader.preload_database()


if __name__ == "__main__":
    asyncio.run(main())
