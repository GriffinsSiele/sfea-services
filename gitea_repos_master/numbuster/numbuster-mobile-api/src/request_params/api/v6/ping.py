from datetime import datetime

from src.config.app import ConfigApp
from src.request_params.interfaces.authed import AuthedParams


class PingParams(AuthedParams):
    def __init__(self, fcm_token: str, *args, **kwargs):
        timestamp = str(int(round(datetime.now().timestamp())))
        super().__init__(
            method="POST",
            path="/api/v6/ping",
            timestamp=timestamp,
            headers={"Content-Type": "application/x-www-form-urlencoded"},
            data={
                "locale": "en_US",
                "platform": "android",
                "datetime": datetime.fromtimestamp(int(timestamp)).strftime(
                    "%Y-%m-%d+%H:%M:%S"
                ),
                "version": ConfigApp.APP_VERSION,
                "fcm_token": fcm_token,
            },
            *args,
            **kwargs,
        )
