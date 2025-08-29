import logging
import time

import requests
from pydash import get


class RegionLocateAPI:
    global_regions = ["Российская Федерация"]

    @staticmethod
    def locate(phone):
        start_time = time.time()
        logging.info(f"Locating region for {phone}")

        response = RegionLocateAPI._locate(phone)
        end_time = time.time()
        logging.info(
            f"Region found: {response}. Elapsed time: {round(end_time - start_time, 2)} sec"
        )

        return response

    @staticmethod
    def _locate(phone):
        query = {"num": phone[1:]}
        try:
            response = requests.get(
                "https://opendata.digital.gov.ru/api/v1/abcdef/phone",
                params=query,
                verify=False,
            ).json()
            region = get(response, "data.0.region")
            logging.info(f"Region response: {region}")
            return RegionLocateAPI._adapter(region)
        except Exception as e:
            logging.error(e)

        return None

    @staticmethod
    def _adapter(region):
        if region in RegionLocateAPI.global_regions:
            return None
        return region
