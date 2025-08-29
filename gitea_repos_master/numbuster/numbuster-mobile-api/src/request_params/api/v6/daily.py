from src.request_params.interfaces.authed import AuthedParams


class DailyParams(AuthedParams):
    def __init__(self, *args, **kwargs):
        super().__init__(path="/api/v6/dailyquest/calendar", *args, **kwargs)
