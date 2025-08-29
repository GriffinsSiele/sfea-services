from pydash.helpers import deprecated

from src.request_params.interfaces.host_resolved import HostResolvedParams


@deprecated
class SyncParams(HostResolvedParams):
    def __init__(self, access_token: str, phone_number: str, *args, **kwargs):
        super().__init__(
            path="/api/v4/profiles/sync",
            query={
                "access_token": access_token,
                "phoneNumbers[]": phone_number,
            },
            *args,
            **kwargs,
        )
