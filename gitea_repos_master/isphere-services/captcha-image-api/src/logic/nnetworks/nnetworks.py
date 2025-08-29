import asyncio
from typing import Any

from putils_logic.putils import PUtils
from sqlalchemy import Column

from src.common import constant, exceptions, logger, utils
from src.config.api_config import api_settings
from src.logic.decoder import NNetworkDecoder
from src.logic.s3service import s3_service


class NNetworks:
    def __init__(self):
        self.nnetworks_data: dict[str, dict[str, Any]] = {}
        self.storage_path = NNetworks.initialize_storage_dir(
            api_settings.NNETWORKS_LOCAL_STORE_PATH
        )
        self.stored_files = self.get_stored_files()
        self.logger = logger.Logger("NNetworks").get_logger()

    @staticmethod
    def initialize_storage_dir(storage_path: str) -> str:
        if not PUtils.is_dir_exists(storage_path):
            PUtils.mkdir(storage_path)
        return storage_path

    def get_stored_files(self):
        return {
            PUtils.get_filename_from_path(filepath)
            for filepath in PUtils.get_files(self.storage_path)
        }

    async def _download_and_process_tags_data(self, s3_object: str) -> None:
        nnetwork_name = s3_service.clear_object_prefix(s3_object)
        if nnetwork_name not in self.stored_files:
            self.logger.info(f"Downloading onnx {nnetwork_name}")
            await s3_service.download_file(
                bucket=api_settings.S3_BUCKET_MAIN,
                s3_object=s3_object,
                store_path=f"{self.storage_path}/{nnetwork_name}",
            )
        if nnetwork_name not in self.nnetworks_data:
            await self.update_nnetworks_data(nnetwork_name, s3_object)

    def get_nnetwork_data(self, name: str | Column[str]) -> dict[str, Any]:
        nnetwork_data = self.nnetworks_data.get(name)  # type: ignore[arg-type]
        if nnetwork_data is None:
            raise exceptions.NotFoundException(
                message=f"There is no nnetwork decoder associated with '{name}'"
            )
        return nnetwork_data

    async def update_nnetworks_data(self, nnetwork_name: str, s3_object: str) -> None:
        self.logger.info(f"Updating {nnetwork_name} nnetwork info")
        nnetwork_params = await s3_service.get_object_tags(
            bucket=api_settings.S3_BUCKET_MAIN,
            s3_object=s3_object,
        )
        self.nnetworks_data[nnetwork_name] = {
            "networkname": nnetwork_name,
            "network_params": nnetwork_params,
        }

    def decode_captcha(
        self,
        file: bytes,
        nn_name: str | Column[str],
    ) -> utils.DecoderResult:
        nnetwork_data = self.get_nnetwork_data(name=nn_name)
        decoder = NNetworkDecoder(nnetwork_data=nnetwork_data)
        result, time = utils.timer(decoder.solve)(file=file)
        return utils.DecoderResult(
            **result, decode_time=time, provider=constant.NNETWORKS_PROVIDER
        )

    async def load_s3_nnetworks(self) -> None:
        s3_objects = s3_service.get_bucket_objects(
            bucket=api_settings.S3_BUCKET_MAIN,
            prefix=api_settings.S3_PREFIX_NNETWORKS,
        )

        await asyncio.gather(
            *[self._download_and_process_tags_data(s3_object) for s3_object in s3_objects]
        )
        self.logger.info("Downloaded all models")


service: "NNetworks" = NNetworks()
