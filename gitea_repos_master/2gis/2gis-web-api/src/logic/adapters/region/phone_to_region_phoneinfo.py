import logging
import time

import requests
from pydash import get

from src.config.settings import PHONEINFO_URL
from src.logic.adapters.region.phone_to_region_sqlite import RegionLocateSQLite


class RegionLocatePhoneInfo:
    global_regions = ["Российская Федерация"]

    @staticmethod
    def locate(phone):
        start_time = time.time()
        logging.info(f"Locating region for {phone}")

        response = RegionLocatePhoneInfo._locate(phone)
        end_time = time.time()
        logging.info(
            f"Region found: {response}. Elapsed time: {round(end_time - start_time, 2)} sec"
        )

        return response

    @staticmethod
    def _locate(phone):
        try:
            response = requests.get(
                PHONEINFO_URL,
                params={"source": "rossvyaz"},
                json={"phone": f"+{phone}" if not phone.startswith("+") else phone},
                verify=False,
                timeout=3,
            ).json()
            region = get(response, "region.0")
            logging.info(f"Region response: {region}")
            return RegionLocatePhoneInfo._adapter(region)
        except Exception as e:
            logging.warning(f"Error while connecting to phoneinfo service: {e}")
            return RegionLocateSQLite().locate(phone)

    @staticmethod
    def _adapter(region):
        if region in RegionLocatePhoneInfo.global_regions:
            return None
        return region
