from src.request_params.interfaces.host_resolved import HostResolvedParams


class ResolveHostParams(HostResolvedParams):
    def __init__(self, *args, **kwargs):
        super().__init__(path="/api/v6/api_domain", *args, **kwargs)
