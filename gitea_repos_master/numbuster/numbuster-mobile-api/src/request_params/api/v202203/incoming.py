from src.request_params.interfaces.authed import AuthedParams


class IncomingParams(AuthedParams):
    def __init__(self, phone_number: str, *args, **kwargs):
        super().__init__(
            path="/api/v202203/call/incoming",
            query={"phone": phone_number},
            *args,
            **kwargs,
        )
