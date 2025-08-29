from lib.src.request_params.interfaces.grpc import GRPCParams
from src.logic.adapter.grpc import GRPCAdapter


class PresenceParams(GRPCParams):
    def __init__(self, phone_number, *args, **kwargs):
        super().__init__(
            url="https://presence-grpc-noneu.truecaller.com/truecaller.presence.v1.Presence/GetPresence",
            *args,
            **kwargs,
        )
        self.method = "POST"
        self.headers = {
            **self.headers,
            "Host": "presence-grpc-noneu.truecaller.com",
        }
        self.payload = GRPCAdapter.phone_to_request(phone_number)
        self.payload_type = "data"
        self.timeout = 2
