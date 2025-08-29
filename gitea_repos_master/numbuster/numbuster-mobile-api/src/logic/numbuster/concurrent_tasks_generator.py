from typing import Any, List

from src.request_params.api.v6.daily import DailyParams
from src.request_params.api.v6.ping import PingParams
from src.request_params.api.v202203.history import HistoryParams
from src.request_params.api.v202206.version import VersionParams
from src.utils.utils import random_boolean


class ConcurrentTasksGenerator:
    @staticmethod
    def create(cls: Any):
        required_count = 2
        output: List[Any] = []

        tasks = [
            lambda: DailyParams(
                access_token=cls.access_token, domain=cls.host, proxy=cls.proxy
            ),
            lambda: HistoryParams(
                access_token=cls.access_token, domain=cls.host, proxy=cls.proxy
            ),
            lambda: VersionParams(
                access_token=cls.access_token, domain=cls.host, proxy=cls.proxy
            ),
            lambda: PingParams(
                access_token=cls.access_token,
                fcm_token=cls.fcm_token,
                domain=cls.host,
                proxy=cls.proxy,
            ),
        ]

        for request_params_lambda in tasks:
            if len(output) == required_count:
                break

            if random_boolean():
                output.append(request_params_lambda())

        return output
