from lib.src.config.app import ConfigApp
from lib.src.request_params.interfaces.authed import AuthedParams


class GRPCParams(AuthedParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.headers = {
            **self.headers,
            "User-Agent": f"truecaller-android/{ConfigApp.APP_VERSION_GRPC} (grpc-java-okhttp) grpc-java-okhttp/1.37.0",
            "Content-Type": "application/grpc",
            "te": "trailers",
            "grpc-accept-encoding": "gzip",
            "grpc-timeout": "9999363u",
        }
