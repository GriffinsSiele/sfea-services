from isphere_exceptions.source import SourceIncorrectDataDetected
from isphere_exceptions.worker import InternalWorkerError
from proxy_manager.utils import Singleton

from src.logger import context_logging
from src.request_params.interfaces import PhoneInfoRequestClient
from src.schemas import PhoneInfoDataSchema


class PhoneInfoRegion(metaclass=Singleton):

    def __validate_if_error(self, phone_data: PhoneInfoDataSchema) -> None:
        if phone_data.error:
            raise InternalWorkerError(
                f"Ошибка phoneinfo '{phone_data.phone}': {phone_data.error}."
            )
        if not phone_data.region and not phone_data.region_code and not phone_data.city:
            raise SourceIncorrectDataDetected(
                "Не удалось определить регион. Поиск без региона невозможен."
            )

    async def get_region_info(self, phone: str) -> PhoneInfoDataSchema:
        context_logging.info(f"Requesting region info...")
        payload = await PhoneInfoRequestClient(json={"phone": phone}).request()
        phone_data = PhoneInfoDataSchema.model_validate({**payload, "phone": phone})
        self.__validate_if_error(phone_data)
        context_logging.info(f"Region info - {phone_data}")
        return phone_data
